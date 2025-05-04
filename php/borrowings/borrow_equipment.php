<?php
session_start();
require_once '../db_connection.php';
require_once '../classes/Borrowing.php';

if (!isLoggedIn()) {
    redirectWithMessage("../login.php", "Please log in to continue.");
}

if ($_SESSION['role'] === 'admin') {
    redirectWithMessage("../dashboard.php", "Admins are not allowed to borrow equipment.");
}

if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    redirectWithMessage("../equipment/equipment.php", "Invalid equipment ID.");
}

$equipment_id = (int)$_GET['id'];

$equipment = fetchEquipmentDetails($conn, $equipment_id);
if (!$equipment) {
    redirectWithMessage("../equipment/equipment.php", "Equipment not found or not available for borrowing.");
}

if (!empty($_SESSION['university_id'])) {
    echo "<p>University ID: {$_SESSION['university_id']}</p>";
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $borrow_date = sanitize($_POST['borrow_date']);
    $due_date = sanitize($_POST['due_date']);
    $purpose = sanitize($_POST['purpose']);

    $borrow_datetime = new DateTime($borrow_date);
    $due_datetime = new DateTime($due_date);
    if ($borrow_datetime > $due_datetime) {
        $error = "Due date must be after the borrow date.";
    } else {
        $borrowing = new Borrowing($conn);
        $borrowing_data = [
            'equipment_id' => $equipment_id,
            'user_id' => $_SESSION['user_id'],
            'borrow_date' => $borrow_date,
            'due_date' => $due_date,
            'notes' => $purpose
        ];
        
        $result = $borrowing->create($borrowing_data);
        
        if ($result['success']) {
            $success = $result['message'];
            header("refresh:2;url=my_borrowings.php");
        } else {
            $error = $result['message'];
        }
    }
}

include '../../pages/borrowings/borrow_equipment.html';

function redirectWithMessage($url, $message) {
    $_SESSION['error'] = $message;
    header("Location: $url");
    exit();
}

function fetchEquipmentDetails($conn, $equipment_id) {
    $sql = "SELECT e.*, c.name as category_name 
            FROM equipment e
            JOIN categories c ON e.category_id = c.category_id
            WHERE e.equipment_id = ? AND e.status = 'available'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $equipment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>