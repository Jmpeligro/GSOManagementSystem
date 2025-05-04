<?php
class Category {
    private $conn;
    private $category_id;
    private $name;
    private $description;
    private $created_at;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function load($category_id) {
        $sql = "SELECT * FROM categories WHERE category_id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return false;
        }
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $category_data = $result->fetch_assoc();
        $this->setData($category_data);
        return true;
    }

    public function setData($data) {
        $this->category_id = $data['category_id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
    }
    
    public function create($name, $description = '') {

        if (empty($name)) {
            return ['success' => false, 'message' => 'Category name is required.'];
        }

        if ($this->nameExists($name)) {
            return ['success' => false, 'message' => 'A category with this name already exists.'];
        }
        
        $sql = "INSERT INTO categories (name, description, created_at) VALUES (?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return ['success' => false, 'message' => 'Error preparing statement: ' . $this->conn->error];
        }
        $stmt->bind_param("ss", $name, $description);
        
        if ($stmt->execute()) {
            $this->category_id = $stmt->insert_id;
            $this->name = $name;
            $this->description = $description;
            return ['success' => true, 'message' => 'Category added successfully.'];
        } else {
            return ['success' => false, 'message' => 'Error adding category: ' . $stmt->error];
        }
    }

    public function update($name, $description = '') {
        if (empty($name)) {
            return ['success' => false, 'message' => 'Category name is required.'];
        }
  
        if ($this->nameExists($name, $this->category_id)) {
            return ['success' => false, 'message' => 'Another category with this name already exists.'];
        }
        
        $sql = "UPDATE categories 
                SET name = ?, 
                    description = ?
                WHERE category_id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return ['success' => false, 'message' => 'Error preparing statement: ' . $this->conn->error];
        }
        $stmt->bind_param("ssi", $name, $description, $this->category_id);
        
        if ($stmt->execute()) {
            $this->name = $name;
            $this->description = $description;
            return ['success' => true, 'message' => 'Category updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Error updating category: ' . $stmt->error];
        }
    }
    
    public function delete() {

        if ($this->hasEquipment()) {
            return ['success' => false, 'message' => 'Cannot delete category that contains equipment. Please reassign or delete the equipment first.'];
        }
        
        $sql = "DELETE FROM categories WHERE category_id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return ['success' => false, 'message' => 'Error preparing statement: ' . $this->conn->error];
        }
        $stmt->bind_param("i", $this->category_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Category deleted successfully.'];
        } else {
            return ['success' => false, 'message' => 'Error deleting category: ' . $stmt->error];
        }
    }
    
    // Check if category name exists
    private function nameExists($name, $exclude_id = null) {
        $sql = "SELECT COUNT(*) as count FROM categories WHERE name = ?";
        $params = [$name];
        $types = "s";
        
        if ($exclude_id !== null) {
            $sql .= " AND category_id != ?";
            $params[] = $exclude_id;
            $types .= "i";
        }
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return false; 
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['count'] > 0;
    }
    
    // Check if category has equipment
    private function hasEquipment() {
        $sql = "SELECT COUNT(*) as count FROM equipment WHERE category_id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return false; 
        }
        $stmt->bind_param("i", $this->category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['count'] > 0;
    }
    
    // Get related equipment
    public function getEquipment() {
        $equipment = [];
        $sql = "SELECT * FROM equipment WHERE category_id = ? ORDER BY name ASC";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return $equipment; 
        }
        $stmt->bind_param("i", $this->category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $equipment[] = $row;
        }
        
        return $equipment;
    }
    
    // Getter methods
    public function getId() {
        return $this->category_id;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getDescription() {
        return $this->description;
    }
    
    public function getCreatedAt() {
        return $this->created_at;
    }
    
    public static function getAll($conn, $search_query = '') {
        $categories = [];
        $sql = "SELECT c.*, COUNT(e.equipment_id) as equipment_count 
                FROM categories c
                LEFT JOIN equipment e ON c.category_id = e.category_id
                WHERE 1=1";
                
        if (!empty($search_query)) {
            $search_query = $conn->real_escape_string($search_query);
            $sql .= " AND (c.name LIKE '%$search_query%' OR c.description LIKE '%$search_query%')";
        }
        
        $sql .= " GROUP BY c.category_id ORDER BY c.name ASC";
        $result = $conn->query($sql);
        
        if ($result === false) {
            return $categories; 
        }
        
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        return $categories;
    }
}
?>