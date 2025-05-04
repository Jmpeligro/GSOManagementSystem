<?php
session_start();
require_once '../db_connection.php';
require_once '../classes/Borrowing.php';

if (!isLoggedIn()) {
    header("Location: php/login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: my_borrowings.php");
    exit();
}

$borrowing_id = (int)$_GET['id'];

$borrowing = new Borrowing($conn);
if (!$borrowing->load($borrowing_id)) {
    $_SESSION['error'] = "Borrowing record not found.";
    header("Location: my_borrowings.php");
    exit();
}

$borrower = $borrowing->getBorrowerDetails();
$equipment = $borrowing->getEquipmentDetails();

if ($borrower['user_id'] != $_SESSION['user_id'] && !isAdmin()) {
    $_SESSION['error'] = "You don't have permission to view this borrowing record.";
    header("Location: my_borrowings.php");
    exit();
}

$is_overdue = $borrowing->isOverdue();
$status_class = $is_overdue ? 'overdue' : $borrowing->getStatus();

$admin_approved = null;
$admin_issued = null;
$returned_by = null;

if ($borrowing->getApprovedBy()) {
    $sql = "SELECT first_name, last_name FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $approved_by = $borrowing->getApprovedBy();
    $stmt->bind_param("i", $approved_by);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin_approved = $result->fetch_assoc();
}

if ($borrowing->getAdminIssuedId()) {
    $sql = "SELECT first_name, last_name FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $admin_issued_id = $borrowing->getAdminIssuedId();
    $stmt->bind_param("i", $admin_issued_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin_issued = $result->fetch_assoc();
}

if ($borrowing->getReturnedBy()) {
    $sql = "SELECT first_name, last_name FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $returned_by_id = $borrowing->getReturnedBy();
    $stmt->bind_param("i", $returned_by_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $returned_by = $result->fetch_assoc();
}

if (isset($_POST['return']) && ($borrowing->getStatus() == 'active' && $borrowing->getApprovalStatus() == 'approved') && 
    (isAdmin() || $borrower['user_id'] == $_SESSION['user_id'])) {
    
    $return_notes = isset($_POST['return_notes']) ? sanitize($_POST['return_notes']) : 'Returned via system';
    $condition = isset($_POST['condition']) ? sanitize($_POST['condition']) : 'good';
    
    $result = $borrowing->returnEquipment($_SESSION['user_id'], $condition, $return_notes);
    
    if ($result['success']) {
        $_SESSION['success'] = "Equipment returned successfully.";
    } else {
        $_SESSION['error'] = "Error returning equipment: " . $result['message'];
    }
    
    header("Location: view_borrowing.php?id=$borrowing_id");
    exit();
}

$borrowing_data = [
    'borrowing_id' => $borrowing->getId(),
    'equipment_id' => $borrowing->getEquipmentId(),
    'borrow_date' => $borrowing->getBorrowDate(),
    'due_date' => $borrowing->getDueDate(),
    'return_date' => $borrowing->getReturnDate(),
    'notes' => $borrowing->getNotes(),
    'admin_notes' => $borrowing->getAdminNotes(),
    'approval_status' => $borrowing->getApprovalStatus(),
    'status' => $borrowing->getStatus(),
    'created_at' => $borrowing->getCreatedAt(),
    'equipment_name' => $equipment['name'],
    'equipment_code' => $equipment['equipment_code'],
    'equipment_description' => $equipment['description'],
    'category_name' => $equipment['category_name'],
    'first_name' => $borrower['first_name'],
    'last_name' => $borrower['last_name'],
    'email' => $borrower['email'],
    'admin_approved_first_name' => $admin_approved ? $admin_approved['first_name'] : null,
    'admin_approved_last_name' => $admin_approved ? $admin_approved['last_name'] : null,
    'admin_issued_first_name' => $admin_issued ? $admin_issued['first_name'] : null,
    'admin_issued_last_name' => $admin_issued ? $admin_issued['last_name'] : null,
    'returned_first_name' => $returned_by ? $returned_by['first_name'] : null,
    'returned_last_name' => $returned_by ? $returned_by['last_name'] : null,
    'condition_on_return' => $borrowing->getConditionOnReturn(),
    'return_notes' => $borrowing->getReturnNotes()
];

include '../../pages/borrowings/view_borrowing.html';
?>