<?php
session_start();
require_once '../db_connection.php';
require_once '../classes/Borrowing.php';

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

$filters = [];

if (!empty($status_filter)) {
    $filters['status'] = $status_filter;
}

if (!empty($approval_filter)) {
    $filters['approval_status'] = $approval_filter;
}

if ($equipment_filter > 0) {
    $filters['equipment_id'] = $equipment_filter;
}

if (!empty($date_from)) {
    $filters['date_from'] = $date_from;
}

if (!empty($date_to)) {
    $filters['date_to'] = $date_to;
}

if (!empty($search_query)) {
    $filters['search'] = $search_query;
}

if (!empty($condition_filter)) {
    $filters['condition'] = $condition_filter;
}

if (!isAdmin()) {
    $filters['user_id'] = $_SESSION['user_id'];
    $user_filter = $_SESSION['user_id'];
} else {
    $user_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    if ($user_filter > 0) {
        $filters['user_id'] = $user_filter;
    }
}

$result = Borrowing::getAll($conn, $filters);

if (isAdmin()) {
    $users_sql = "SELECT user_id, first_name, last_name FROM users ORDER BY last_name, first_name";
    $users_result = $conn->query($users_sql);
}

$equipment_sql = "SELECT equipment_id, name FROM equipment ORDER BY name";
$equipment_result = $conn->query($equipment_sql);

if (isset($_GET['return']) && isLoggedIn()) {
    $borrowing_id = (int)$_GET['return'];
    
    $borrowing = new Borrowing($conn);
    if ($borrowing->load($borrowing_id)) {
        $borrower_id = $borrowing->getUserId();

        if ($borrower_id == $_SESSION['user_id'] || isAdmin()) {
            $result = $borrowing->returnEquipment($_SESSION['user_id'], 'good', 'Returned via system');
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
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

if (isset($_GET['cancel']) && !empty($_GET['cancel'])) {
    $borrowing_id = (int)$_GET['cancel'];
    
    $borrowing = new Borrowing($conn);
    if ($borrowing->load($borrowing_id)) {
        if ($borrowing->getUserId() == $_SESSION['user_id'] && 
            $borrowing->getApprovalStatus() == 'pending' && 
            $borrowing->getStatus() != 'active') {
            
            $result = $borrowing->cancel();
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
        } else {
            $_SESSION['error'] = "Invalid request or request already processed.";
        }
    } else {
        $_SESSION['error'] = "Borrowing record not found.";
    }
    
    header("Location: borrowings.php");
    exit();
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

include '../../pages/borrowings/borrowings.html';
?>