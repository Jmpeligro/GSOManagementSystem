<?php
session_start();
require_once 'db_connection.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: index.php");
    exit();
}

function getReportStats($conn) {
    $stats = [];

    $sql_equipment_status = "SELECT status, COUNT(*) as count FROM equipment GROUP BY status";
    $result = $conn->query($sql_equipment_status);
    
    $stats['equipment_status'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['equipment_status'][$row['status']] = $row['count'];
    }
  
    $sql_borrowing_status = "SELECT status, COUNT(*) as count FROM borrowings GROUP BY status";
    $result = $conn->query($sql_borrowing_status);
    
    $stats['borrowing_status'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['borrowing_status'][$row['status']] = $row['count'];
    }

    $sql_maintenance_status = "SELECT status, COUNT(*) as count FROM maintenance GROUP BY status";
    $result = $conn->query($sql_maintenance_status);
    
    $stats['maintenance_status'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['maintenance_status'][$row['status']] = $row['count'];
    }
    
    $sql_maintenance_cost = "SELECT SUM(cost) as total_cost FROM maintenance";
    $result = $conn->query($sql_maintenance_cost);
    $stats['maintenance_cost'] = $result->fetch_assoc()['total_cost'] ?: 0;
  
    $sql_popular_equipment = "SELECT e.equipment_id, e.name, COUNT(b.borrowing_id) as borrow_count 
                              FROM equipment e 
                              JOIN borrowings b ON e.equipment_id = b.equipment_id 
                              GROUP BY e.equipment_id 
                              ORDER BY borrow_count DESC 
                              LIMIT 5";
    $result = $conn->query($sql_popular_equipment);
    
    $stats['popular_equipment'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['popular_equipment'][] = $row;
    }

    $sql_department_usage = "SELECT u.department, COUNT(b.borrowing_id) as borrow_count 
                            FROM borrowings b 
                            JOIN users u ON b.user_id = u.user_id 
                            WHERE u.department IS NOT NULL 
                            GROUP BY u.department 
                            ORDER BY borrow_count DESC";
    $result = $conn->query($sql_department_usage);
    
    $stats['department_usage'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['department_usage'][] = $row;
    }
    
    return $stats;
}

include '../pages/reports.html';
?>