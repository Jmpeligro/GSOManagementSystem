<?php
session_start();
require_once '../db_connection.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$approval_filter = isset($_GET['approval_status']) ? sanitize($_GET['approval_status']) : '';
$equipment_filter = isset($_GET['equipment_id']) ? (int)$_GET['equipment_id'] : 0;
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';
$condition_filter = isset($_GET['condition']) ? sanitize($_GET['condition']) : '';

// Base SQL query
$sql = "SELECT 
    b.borrowing_id,
    b.equipment_id,
    b.user_id,
    b.borrow_date,
    b.due_date,
    b.return_date,
    b.admin_issued_id,
    b.admin_received_id,
    b.status,
    b.condition_on_return,
    b.notes,
    b.created_at,
    b.updated_at,
    b.approval_status,
    b.approved_by,
    b.approval_date,
    b.admin_notes,
    b.return_notes,
    b.returned_by,
    e.name as equipment_name, 
    e.equipment_code,
    u.first_name, 
    u.last_name, 
    u.email
    FROM borrowings b
    JOIN equipment e ON b.equipment_id = e.equipment_id
    JOIN users u ON b.user_id = u.user_id
    WHERE 1=1";

if (!isAdmin()) {
    $sql .= " AND b.user_id = " . $_SESSION['user_id'];
    $user_filter = $_SESSION['user_id']; 
} else {
    $user_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    if ($user_filter > 0) {
        $sql .= " AND b.user_id = $user_filter";
    }
}

if (!empty($status_filter)) {
    $sql .= " AND b.status = '$status_filter'";
}

if (!empty($approval_filter)) {
    $sql .= " AND b.approval_status = '$approval_filter'";
}

if ($equipment_filter > 0) {
    $sql .= " AND b.equipment_id = $equipment_filter";
}

if (!empty($date_from)) {
    $sql .= " AND b.borrow_date >= '$date_from'";
}

if (!empty($date_to)) {
    $sql .= " AND b.borrow_date <= '$date_to 23:59:59'";
}

if (!empty($search_query)) {
    $sql .= " AND (e.name LIKE '%$search_query%' OR e.equipment_code LIKE '%$search_query%' OR 
              u.first_name LIKE '%$search_query%' OR u.last_name LIKE '%$search_query%' OR 
              u.email LIKE '%$search_query%')";
}

if (!empty($condition_filter)) {
    $sql .= " AND b.condition_on_return = '$condition_filter'";
}

$sql .= " ORDER BY b.borrow_date DESC";

// Add error handling for the main query
$result = $conn->query($sql);
if ($result === false) {
    $_SESSION['error'] = "Database error: " . $conn->error . " in query: " . $sql;
    $result = $conn->query("SELECT 1 LIMIT 0"); // Create an empty result set
}

// Error handling for users query
if (isAdmin()) {
    $users_sql = "SELECT user_id, first_name, last_name FROM users ORDER BY last_name, first_name";
    $users_result = $conn->query($users_sql);
    if ($users_result === false) {
        $_SESSION['error'] = "Users query error: " . $conn->error;
        $users_result = $conn->query("SELECT 1 LIMIT 0"); // Create an empty result set
    }
}

// Error handling for equipment query
$equipment_sql = "SELECT equipment_id, name FROM equipment ORDER BY name";
$equipment_result = $conn->query($equipment_sql);
if ($equipment_result === false) {
    $_SESSION['error'] = "Equipment query error: " . $conn->error;
    $equipment_result = $conn->query("SELECT 1 LIMIT 0"); // Create an empty result set
}

// Return equipment handling
if (isset($_GET['return']) && isLoggedIn()) {
    $borrowing_id = (int)$_GET['return'];
    
    $check_sql = "SELECT b.user_id, b.equipment_id, b.quantity, e.quantity as current_equipment_quantity
                 FROM borrowings b
                 JOIN equipment e ON b.equipment_id = e.equipment_id
                 WHERE b.borrowing_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $borrowing_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $borrow_data = $check_result->fetch_assoc();
        $borrower_id = $borrow_data['user_id'];
        $equipment_id = $borrow_data['equipment_id'];
        $borrowed_quantity = isset($borrow_data['quantity']) ? $borrow_data['quantity'] : 1; // Default to 1 for backward compatibility

        if ($borrower_id == $_SESSION['user_id'] || isAdmin()) {
            $conn->begin_transaction();

            try {
                $update_borrowing_sql = "UPDATE borrowings SET 
                    status = 'returned',
                    return_date = NOW(),
                    return_notes = 'Returned via system',
                    returned_by = ?,
                    condition_on_return = 'good'
                    WHERE borrowing_id = ?";

                $update_stmt = $conn->prepare($update_borrowing_sql);
                if ($update_stmt === false) {
                    throw new Exception("Failed to prepare update statement: " . $conn->error);
                }

                if (!$update_stmt->bind_param("ii", $_SESSION['user_id'], $borrowing_id)) {
                    throw new Exception("Failed to bind parameters: " . $update_stmt->error);
                }

                if (!$update_stmt->execute()) {
                    throw new Exception("Failed to execute update: " . $update_stmt->error);
                }

                // Update equipment quantity
                $new_equipment_quantity = $borrow_data['current_equipment_quantity'] + $borrowed_quantity;
                $update_equipment_sql = "UPDATE equipment 
                                        SET status = 'available', 
                                            quantity = ? 
                                        WHERE equipment_id = ?";
                $update_equipment_stmt = $conn->prepare($update_equipment_sql);
                if ($update_equipment_stmt === false) {
                    throw new Exception("Failed to prepare equipment update: " . $conn->error);
                }

                if (!$update_equipment_stmt->bind_param("ii", $new_equipment_quantity, $equipment_id)) {
                    throw new Exception("Failed to bind equipment parameters: " . $update_equipment_stmt->error);
                }

                if (!$update_equipment_stmt->execute()) {
                    throw new Exception("Failed to update equipment status: " . $update_equipment_stmt->error);
                }

                $conn->commit();
                $_SESSION['success'] = "Equipment returned successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error'] = "Error returning equipment: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "You can only return equipment that you borrowed.";
        }
    } else {
        $_SESSION['error'] = "Borrowing record not found.";
    }

    header("Location: borrowings.php");
    exit();
}

// Cancel request handling
if (isset($_GET['cancel']) && !empty($_GET['cancel'])) {
    $borrowing_id = (int)$_GET['cancel'];

    $check_sql = "SELECT status, approval_status 
                 FROM borrowings 
                 WHERE borrowing_id = ? 
                 AND user_id = ? 
                 AND approval_status = 'pending'
                 AND status != 'active'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $borrowing_id, $_SESSION['user_id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $delete_sql = "DELETE FROM borrowings WHERE borrowing_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $borrowing_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['success'] = "Borrowing request cancelled successfully.";
        } else {
            $_SESSION['error'] = "Error cancelling request.";
        }
    } else {
        $_SESSION['error'] = "Invalid request or request already processed.";
    }
    
    header("Location: borrowings.php");
    exit();
}

include '../../pages/borrowings/borrowings.html';
?>