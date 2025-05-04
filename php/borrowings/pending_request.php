<?php
session_start();
require_once '../db_connection.php';
require_once '../classes/Borrowing.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: php/login.php");
    exit();
}

if (isset($_POST['approve_request'])) {
    $borrowing_id = (int)$_POST['borrowing_id'];

    $borrowing = new Borrowing($conn);
    if ($borrowing->load($borrowing_id)) {
        $result = $borrowing->approve($_SESSION['user_id'], 'Approved by administrator');
        
        if ($result['success']) {
            $_SESSION['success'] = "Borrowing request approved successfully.";
        } else {
            $_SESSION['error'] = "Error approving request: " . $result['message'];
        }
    } else {
        $_SESSION['error'] = "Borrowing record not found.";
    }
    
    header("Location: pending_request.php");
    exit();
}

if (isset($_POST['deny_request'])) {
    $borrowing_id = (int)$_POST['borrowing_id'];
    $denial_reason = sanitize($_POST['denial_reason']);
  
    $borrowing = new Borrowing($conn);
    if ($borrowing->load($borrowing_id)) {
        $result = $borrowing->deny($_SESSION['user_id'], $denial_reason);
        
        if ($result['success']) {
            $_SESSION['success'] = "Borrowing request denied.";
        } else {
            $_SESSION['error'] = "Error denying request: " . $result['message'];
        }
    } else {
        $_SESSION['error'] = "Borrowing record not found.";
    }
    
    header("Location: pending_request.php");
    exit();
}

$pending_requests = Borrowing::getPendingRequests($conn);

include '../../pages/borrowings/pending_request.html';
?>