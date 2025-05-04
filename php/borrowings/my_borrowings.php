<?php
session_start();
require_once '../db_connection.php';
require_once '../classes/Borrowing.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$borrowings = Borrowing::getUserBorrowings($conn, $user_id);

if (isset($_GET['return']) && !empty($_GET['return'])) {
    $borrowing_id = (int)$_GET['return'];
    
    $borrowing = new Borrowing($conn);
    if ($borrowing->load($borrowing_id)) {
        if ($borrowing->getUserId() == $user_id) {
            $result = $borrowing->returnEquipment($user_id, 'good', 'Returned via my borrowings page');
            
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

    header("Location: my_borrowings.php");
    exit();
}

if (isset($_GET['cancel']) && !empty($_GET['cancel'])) {
    $borrowing_id = (int)$_GET['cancel'];
    
    $borrowing = new Borrowing($conn);
    if ($borrowing->load($borrowing_id)) {
        if ($borrowing->getUserId() == $user_id && 
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
    
    header("Location: my_borrowings.php");
    exit();
}

$overdue_items = [];
foreach ($borrowings as $borrowing) {
    $borrow = new Borrowing($conn);
    $borrow->load($borrowing['borrowing_id']);
    
    if ($borrow->isOverdue()) {
        $overdue_items[] = $borrowing;
    }
}

include '../../pages/borrowings/my_borrowings.html';
?>