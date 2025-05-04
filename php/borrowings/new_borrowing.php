<?php
session_start();
require_once '../db_connection.php';
require_once '../classes/Borrowing.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$sql_equipment = "SELECT equipment_id, name, equipment_code FROM equipment 
                 WHERE status = 'available' AND available_quantity > 0
                 ORDER BY name";
$result_equipment = $conn->query($sql_equipment);

$sql_users = "SELECT user_id, first_name, last_name, email FROM users 
              WHERE role != 'admin' 
              ORDER BY last_name, first_name";
$result_users = $conn->query($sql_users);

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $equipment_id = (int)$_POST['equipment_id'];
    $user_id = (int)$_POST['user_id'];
    $borrow_date = sanitize($_POST['borrow_date']);
    $due_date = sanitize($_POST['due_date']);
    $notes = sanitize($_POST['notes']);

    if ($equipment_id <= 0) {
        $errors[] = "Please select valid equipment.";
    }
    
    if ($user_id <= 0) {
        $errors[] = "Please select a valid user.";
    }

    $borrowing = new Borrowing($conn);

    $borrowing_data = [
        'equipment_id' => $equipment_id,
        'user_id' => $user_id,
        'borrow_date' => $borrow_date,
        'due_date' => $due_date,
        'notes' => $notes,
        'status' => 'pending',
        'approval_status' => 'pending'
    ];
 
    if (empty($errors)) {
        $result = $borrowing->create($borrowing_data);
        
        if ($result['success']) {
            $borrowing->load($borrowing->getId());
            $approve_result = $borrowing->approve($_SESSION['user_id'], 'Approved at creation by administrator');
            
            if ($approve_result['success']) {
                $_SESSION['success'] = "Equipment borrowed successfully.";
                header("Location: borrowings.php");
                exit();
            } else {
                $errors[] = "Error approving borrowing: " . $approve_result['message'];
            }
        } else {
            $errors[] = "Error creating borrowing: " . $result['message'];
        }
    }
}

include '../../pages/borrowings/new_borrowing.html';
?>