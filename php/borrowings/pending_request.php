<?php
session_start();
require_once '../db_connection.php';
require_once '../helpers.php'; 
require_once '../classes/borrowing/Borrowing.php';
require_once 'notifications.php';
require_once '../classes/User/UserBase.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: php/login.php");
    exit();
}

$userBase = new UserBase($conn);
$userBase->load($_SESSION['user_id']);
$recipientEmail = $userBase->getEmail();
$name = $userBase->getFullName();   

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
    $emailBody = "
        <B><U>THIS IS AN AUTOMATICALLY GENERATED EMAIL | DO NOT REPLY</U></B> <BR>
        <hr>
        <p>The equipment you requested to borrow has been approved.</p>
        <p>Please visit the General Service Office at your soonest convenience to collect/claim your requested equipment.</p>
    ";

    $emailBodyAlt = "
        text -- fill this up with alternative text :D
    ";
    pushNotif("Email Sent", "An Email has been sent notify " . $name . " of their request status at " . $email);
    sendEmail($recipientEmail, $name, "Borrow Request: Approved", $emailBody, $emailBodyAlt);
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
    $emailBody = "
        <B><U>THIS IS AN AUTOMATICALLY GENERATED EMAIL | DO NOT REPLY</U></B> <BR>
        <hr>
        <p>The equipment you requested to borrow has been denied.</p>        
    ";

    $emailBodyAlt = "
        text -- fill this up with alternative text :D
    ";
    pushNotif("Email Sent", "An Email has been sent notify " . $name . " of their request status at " . $email);
    sendEmail($recipientEmail, $name, "Borrow Request: Approved", $emailBody, $emailBodyAlt);
    exit();
}

$pending_requests = Borrowing::getPendingRequests($conn);

include '../../pages/borrowings/pending_request.html';
?>
