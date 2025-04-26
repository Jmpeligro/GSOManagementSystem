<?php
session_start();
require_once '../db_connection.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Handle quick add from equipment page
if (isset($_GET['quick_add']) && is_numeric($_GET['quick_add'])) {
    $equipment_id = (int)$_GET['quick_add'];
    
    // Check if the equipment exists and is not already in maintenance
    $check_sql = "SELECT name, status FROM equipment WHERE equipment_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $equipment_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $equipment = $check_result->fetch_assoc();
        
        if ($equipment['status'] === 'maintenance') {
            $_SESSION['error'] = "This equipment is already under maintenance!";
        } elseif ($equipment['status'] === 'borrowed') {
            $_SESSION['error'] = "Cannot send borrowed equipment to maintenance!";
        } elseif ($equipment['status'] === 'retired') {
            $_SESSION['error'] = "Cannot send retired equipment to maintenance!";
        } else {
            // Show a pre-filled modal form for the selected equipment
            $quick_add = true;
            $quick_add_equipment_id = $equipment_id;
            $quick_add_equipment_name = $equipment['name'];
        }
    } else {
        $_SESSION['error'] = "Equipment not found!";
    }
}
// Get all maintenance records with equipment info
$sql = "SELECT 
    m.maintenance_id,
    m.equipment_id,
    e.name as equipment_name,
    e.equipment_code,
    m.issue_description,
    m.maintenance_date,
    m.resolved_date,
    m.cost,
    m.resolved_by,
    m.status,
    m.notes,
    m.created_at,
    m.updated_at
    FROM maintenance m
    JOIN equipment e ON m.equipment_id = e.equipment_id
    ORDER BY 
        CASE 
            WHEN m.status = 'pending' THEN 1
            WHEN m.status = 'in_progress' THEN 2
            WHEN m.status = 'completed' THEN 3
        END, 
        m.maintenance_date DESC";

$result = $conn->query($sql);

// Handle form submissions for updating maintenance records
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_maintenance'])) {
        $maintenance_id = (int)$_POST['maintenance_id'];
        $status = sanitize($_POST['status']);
        $notes = sanitize($_POST['notes']);
        $resolved_date = null;
        $resolved_by = null;
        $cost = null;
        
        // If status is completed, update resolved fields
        if ($status === 'completed') {
            $resolved_date = date('Y-m-d');
            $resolved_by = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
            $cost = !empty($_POST['cost']) ? (float)$_POST['cost'] : 0;
        }
        
        $update_sql = "UPDATE maintenance 
                      SET status = ?, 
                          notes = ?,
                          resolved_date = ?,
                          resolved_by = ?,
                          cost = ?,
                          updated_at = NOW()
                      WHERE maintenance_id = ?";
                      
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssdi", $status, $notes, $resolved_date, $resolved_by, $cost, $maintenance_id);
        
        if ($update_stmt->execute()) {
            // If maintenance is completed, update equipment status to available
            if ($status === 'completed') {
                $equipment_id = (int)$_POST['equipment_id'];
                $equipment_update = "UPDATE equipment SET status = 'available', updated_at = NOW() WHERE equipment_id = ?";
                $equipment_stmt = $conn->prepare($equipment_update);
                $equipment_stmt->bind_param("i", $equipment_id);
                $equipment_stmt->execute();
            }
            
            $_SESSION['success'] = "Maintenance record updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating maintenance record: " . $update_stmt->error;
        }
        
        header("Location: maintenance.php");
        exit();
    }
    
    // Handle adding new maintenance record
    if (isset($_POST['add_maintenance'])) {
        $equipment_id = (int)$_POST['equipment_id'];
        $issue_description = sanitize($_POST['issue_description']);
        $maintenance_date = date('Y-m-d');
        $notes = sanitize($_POST['notes']);
        $status = 'pending';
        
        // Check if the equipment exists and is not already in maintenance
        $check_sql = "SELECT status FROM equipment WHERE equipment_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $equipment_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            $_SESSION['error'] = "Equipment not found!";
            header("Location: maintenance.php");
            exit();
        }
        
        $equipment_status = $check_result->fetch_assoc()['status'];
        if ($equipment_status === 'maintenance') {
            $_SESSION['error'] = "This equipment is already under maintenance!";
            header("Location: maintenance.php");
            exit();
        }
        
        // Add maintenance record
        $insert_sql = "INSERT INTO maintenance 
                      (equipment_id, issue_description, maintenance_date, notes, status, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("issss", $equipment_id, $issue_description, $maintenance_date, $notes, $status);
        
        if ($insert_stmt->execute()) {
            // Update equipment status to maintenance
            $update_sql = "UPDATE equipment SET status = 'maintenance', updated_at = NOW() WHERE equipment_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $equipment_id);
            $update_stmt->execute();
            
            $_SESSION['success'] = "Maintenance record created successfully!";
        } else {
            $_SESSION['error'] = "Error creating maintenance record: " . $insert_stmt->error;
        }
        
        header("Location: maintenance.php");
        exit();
    }
    
    // Handle deleting maintenance record
    if (isset($_POST['delete_maintenance'])) {
        $maintenance_id = (int)$_POST['maintenance_id'];
        $equipment_id = (int)$_POST['equipment_id'];
        
        // Get the current status of the maintenance record
        $status_sql = "SELECT status FROM maintenance WHERE maintenance_id = ?";
        $status_stmt = $conn->prepare($status_sql);
        $status_stmt->bind_param("i", $maintenance_id);
        $status_stmt->execute();
        $status_result = $status_stmt->get_result();
        $maintenance_status = $status_result->fetch_assoc()['status'];
        
        $delete_sql = "DELETE FROM maintenance WHERE maintenance_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $maintenance_id);
        
        if ($delete_stmt->execute()) {
            // Only update equipment status if maintenance wasn't completed
            if ($maintenance_status !== 'completed') {
                $update_sql = "UPDATE equipment SET status = 'available', updated_at = NOW() WHERE equipment_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $equipment_id);
                $update_stmt->execute();
            }
            
            $_SESSION['success'] = "Maintenance record deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting maintenance record: " . $delete_stmt->error;
        }
        
        header("Location: maintenance.php");
        exit();
    }
}

// Get available equipment for the add maintenance form
$equipment_sql = "SELECT equipment_id, name, equipment_code FROM equipment WHERE status != 'maintenance' AND status != 'retired' ORDER BY name";
$equipment_result = $conn->query($equipment_sql);

include '../../pages/equipment/maintenance.html';
?>