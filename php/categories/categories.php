<?php
session_start();
require_once '../db_connection.php';
require_once '../classes/Category.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: php/login.php");
    exit();
}

$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';

if (isset($_GET['delete']) && isAdmin()) {
    $delete_id = (int)$_GET['delete'];
    
    $category = new Category($conn);
    if ($category->load($delete_id)) {
        $result = $category->delete();
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    } else {
        $_SESSION['error'] = "Category not found.";
    }
    
    header("Location: categories.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    
    $category = new Category($conn);
    $result = $category->create($name, $description);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
        header("Location: categories.php");
        exit();
    } else {
        $_SESSION['error'] = $result['message'];
    }
}

$categories = Category::getAll($conn, $search_query);

include '../../pages/categories/categories.html';
?>