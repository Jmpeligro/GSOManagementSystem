<?php
session_start();
require_once '../db_connection.php';
require_once '../classes/Category.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Category ID is required.";
    header("Location: categories.php");
    exit();
}

$category_id = (int)$_GET['id'];
$category = new Category($conn);

if (!$category->load($category_id)) {
    $_SESSION['error'] = "Category not found.";
    header("Location: categories.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    
    $result = $category->update($name, $description);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
        header("Location: categories.php");
        exit();
    } else {
        $_SESSION['error'] = $result['message'];
    }
}

$equipment = $category->getEquipment();

class EquipmentResultSet {
    public $num_rows;
    private $equipment;
    private $position = 0;
    
    public function __construct($equipment) {
        $this->equipment = $equipment;
        $this->num_rows = count($equipment);
    }
    
    public function fetch_assoc() {
        if ($this->position >= count($this->equipment)) {
            return null;
        }
        
        $row = $this->equipment[$this->position];
        $this->position++;
        return $row;
    }
    
    public function reset() {
        $this->position = 0;
    }
}

$equipment_result = new EquipmentResultSet($equipment);

$category = [
    'category_id' => $category->getId(),
    'name' => $category->getName(),
    'description' => $category->getDescription()
];

include '../../pages/categories/edit_categories.html';
?>