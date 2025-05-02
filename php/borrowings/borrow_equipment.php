<?php
session_start();
require_once '../db_connection.php';

// Redirect if user is not logged in
if (!isLoggedIn()) {
    redirectWithMessage("../login.php", "Please log in to continue.");
}

// Restrict admin users from borrowing equipment
if ($_SESSION['role'] === 'admin') {
    redirectWithMessage("../dashboard.php", "Admins are not allowed to borrow equipment.");
}

// Validate equipment ID
if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    redirectWithMessage("../equipment/equipment.php", "Invalid equipment ID.");
}

$equipment_id = (int)$_GET['id'];

// Fetch equipment details
$equipment = fetchEquipmentDetails($conn, $equipment_id);
if (!$equipment) {
    redirectWithMessage("../equipment/equipment.php", "Equipment not found or not available for borrowing.");
}

// Display university ID if available
if (!empty($_SESSION['university_id'])) {
    echo "<p>University ID: {$_SESSION['university_id']}</p>";
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $borrow_date = sanitize($_POST['borrow_date']);
    $due_date = sanitize($_POST['due_date']);
    $purpose = sanitize($_POST['purpose']);

    $validation_error = validateDates($borrow_date, $due_date);
    if ($validation_error) {
        $error = $validation_error;
    } else {
        $user_id = $_SESSION['user_id'];
        $success = processBorrowRequest($conn, $equipment_id, $user_id, $borrow_date, $due_date, $purpose);
        if ($success) {
            header("refresh:2;url=my_borrowings.php");
        }
    }
}

include '../../pages/borrowings/borrow_equipment.html';

// Helper Functions
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

function validateDates($borrow_date, $due_date) {
    $borrow_datetime = new DateTime($borrow_date);
    $due_datetime = new DateTime($due_date);
    if ($borrow_datetime > $due_datetime) {
        return "Due date must be after the borrow date.";
    }
    return null;
}

function processBorrowRequest($conn, $equipment_id, $user_id, $borrow_date, $due_date, $purpose) {
    $sql = "INSERT INTO borrowings (
                equipment_id, 
                user_id, 
                borrow_date, 
                due_date, 
                status, 
                notes, 
                approval_status,
                created_at,
                updated_at
            ) VALUES (
                ?, ?, ?, ?, 
                'pending', 
                ?, 
                'pending',
                NOW(),
                NOW()
            )";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return "Error preparing statement: " . $conn->error;
    }
    if (!$stmt->bind_param("iisss", $equipment_id, $user_id, $borrow_date, $due_date, $purpose)) {
        return "Error binding parameters: " . $stmt->error;
    }
    if ($stmt->execute()) {
        return "Equipment borrow request submitted successfully. An administrator will review your request shortly.";
    }
    return "Error processing your request: " . $stmt->error;
}
?>