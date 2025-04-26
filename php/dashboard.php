<?php
session_start();
require_once 'db_connection.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$sql_available = "SELECT COUNT(*) as count FROM equipment WHERE status = 'available'";
$sql_maintenance = "SELECT COUNT(*) as count FROM equipment WHERE status = 'maintenance'";
$sql_retired = "SELECT COUNT(*) as count FROM equipment WHERE status = 'retired'";
$sql_categories = "SELECT COUNT(*) as count FROM categories";

$sql_borrowed = "SELECT COUNT(*) as count FROM equipment e 
                JOIN borrowings b ON e.equipment_id = b.equipment_id 
                WHERE b.status = 'active'";

$result_available = $conn->query($sql_available);
$result_borrowed = $conn->query($sql_borrowed);
$result_maintenance = $conn->query($sql_maintenance);
$result_retired = $conn->query($sql_retired);
$result_categories = $conn->query($sql_categories);

$available_count = $result_available->fetch_assoc()['count'];
$borrowed_count = $result_borrowed->fetch_assoc()['count'];
$maintenance_count = $result_maintenance->fetch_assoc()['count'];
$retired_count = $result_retired->fetch_assoc()['count'];
$categories_count = $result_categories->fetch_assoc()['count'];

$sql_recent = "SELECT 
    b.borrowing_id, 
    e.name as equipment_name, 
    u.first_name, 
    u.last_name, 
    b.borrow_date, 
    b.due_date, 
    b.status,
    b.approval_status,
    b.condition_on_return
    FROM borrowings b
    JOIN equipment e ON b.equipment_id = e.equipment_id
    JOIN users u ON b.user_id = u.user_id
    ORDER BY b.created_at DESC LIMIT 5";
$result_recent = $conn->query($sql_recent);

include '../pages/dashboard.html';
?>