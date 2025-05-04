<?php
class Equipment {
    private $conn;
    private $equipment_id;
    private $name;
    private $description;
    private $equipment_code;
    private $category_id;
    private $condition_status;
    private $status;
    private $acquisition_date;
    private $notes;
    private $quantity;
    private $available_quantity;
    private $created_at;
    private $updated_at;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function load($equipment_id) {
        $sql = "SELECT * FROM equipment WHERE equipment_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $equipment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $equipment_data = $result->fetch_assoc();
        $this->setData($equipment_data);
        return true;
    }

    public function setData($data) {
        $this->equipment_id = $data['equipment_id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->equipment_code = $data['equipment_code'] ?? null;
        $this->category_id = $data['category_id'] ?? null;
        $this->condition_status = $data['condition_status'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->acquisition_date = $data['acquisition_date'] ?? null;
        $this->notes = $data['notes'] ?? null;
        $this->quantity = $data['quantity'] ?? 1;
        $this->available_quantity = $data['available_quantity'] ?? $this->quantity;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }
    
    public function create($data) {
        // Validate required fields
        if (empty($data['name'])) {
            return ['success' => false, 'message' => 'Equipment name is required.'];
        }

        if (empty($data['equipment_code'])) {
            return ['success' => false, 'message' => 'Equipment code is required.'];
        }

        if ($this->codeExists($data['equipment_code'])) {
            return ['success' => false, 'message' => 'A equipment with this code already exists.'];
        }
        
        // Set default values for optional fields
        $description = $data['description'] ?? '';
        $notes = $data['notes'] ?? '';
        $quantity = isset($data['quantity']) && is_numeric($data['quantity']) && $data['quantity'] > 0 ? $data['quantity'] : 1;
        
        $sql = "INSERT INTO equipment (
            name, 
            description, 
            equipment_code, 
            category_id, 
            condition_status, 
            status, 
            acquisition_date,
            quantity,
            available_quantity,
            notes,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($sql);
        
        // Check if prepare was successful
        if ($stmt === false) {
            return ['success' => false, 'message' => 'Error preparing statement: ' . $this->conn->error];
        }

        $stmt->bind_param("ssssssiiss", 
            $data['name'], 
            $description, 
            $data['equipment_code'], 
            $data['category_id'], 
            $data['condition_status'], 
            $data['status'], 
            $data['acquisition_date'],
            $quantity,
            $quantity,
            $notes
        );
        
        if ($stmt->execute()) {
            $this->equipment_id = $stmt->insert_id;
            $this->setData($data);
            return ['success' => true, 'message' => 'Equipment added successfully.'];
        } else {
            return ['success' => false, 'message' => 'Error adding equipment: ' . $stmt->error];
        }
    }

    public function update($data) {
        if (empty($data['name'])) {
            return ['success' => false, 'message' => 'Equipment name is required.'];
        }
  
        if (empty($data['equipment_code'])) {
            return ['success' => false, 'message' => 'Equipment code is required.'];
        }

        if ($this->codeExists($data['equipment_code'], $this->equipment_id)) {
            return ['success' => false, 'message' => 'Another equipment with this code already exists.'];
        }
        
        if (isset($data['status']) && $data['status'] != 'borrowed' && $this->isBorrowed()) {
            return ['success' => false, 'message' => 'Cannot change status of equipment that is currently borrowed.'];
        }

        $description = $data['description'] ?? '';
        $notes = $data['notes'] ?? '';
        $quantity = isset($data['quantity']) && is_numeric($data['quantity']) && $data['quantity'] > 0 ? $data['quantity'] : 1;
        
        $borrowed_count = $this->getBorrowedCount();
        if ($quantity < $borrowed_count) {
            return ['success' => false, 'message' => "Cannot reduce quantity below currently borrowed amount ($borrowed_count)."];
        }
        
        $available_quantity = $quantity - $borrowed_count;
        
        $sql = "UPDATE equipment 
                SET name = ?, 
                    description = ?, 
                    equipment_code = ?, 
                    category_id = ?, 
                    condition_status = ?, 
                    status = ?, 
                    acquisition_date = ?,
                    quantity = ?,
                    available_quantity = ?,
                    notes = ?,
                    updated_at = NOW() 
                WHERE equipment_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            return ['success' => false, 'message' => 'Error preparing statement: ' . $this->conn->error];
        }
        
        $stmt->bind_param("ssssssiissi", 
            $data['name'], 
            $description, 
            $data['equipment_code'], 
            $data['category_id'], 
            $data['condition_status'], 
            $data['status'], 
            $data['acquisition_date'],
            $quantity,
            $available_quantity,
            $notes,
            $this->equipment_id
        );
        
        if ($stmt->execute()) {
            $this->setData($data);
            return ['success' => true, 'message' => 'Equipment updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Error updating equipment: ' . $stmt->error];
        }
    }
    
    public function delete() {
        if ($this->isBorrowed()) {
            return ['success' => false, 'message' => 'Cannot delete equipment that is currently borrowed.'];
        }
        
        $sql = "DELETE FROM equipment WHERE equipment_id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return ['success' => false, 'message' => 'Error preparing statement: ' . $this->conn->error];
        }
        
        $stmt->bind_param("i", $this->equipment_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Equipment deleted successfully.'];
        } else {
            return ['success' => false, 'message' => 'Error deleting equipment: ' . $stmt->error];
        }
    }
    
    public function updateStatus($new_status) {
        $allowed_statuses = ['available', 'maintenance', 'retired'];
        
        if (empty($new_status) || !in_array($new_status, $allowed_statuses)) {
            return ['success' => false, 'message' => 'Invalid status selected.'];
        }
        
        if ($this->isBorrowed()) {
            return ['success' => false, 'message' => 'Cannot update status of equipment that is currently borrowed.'];
        }
        
        $sql = "UPDATE equipment 
                SET status = ?, 
                    updated_at = NOW() 
                WHERE equipment_id = ?";
                
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return ['success' => false, 'message' => 'Error preparing statement: ' . $this->conn->error];
        }
        
        $stmt->bind_param("si", $new_status, $this->equipment_id);
        
        if ($stmt->execute()) {
            $this->status = $new_status;
            return ['success' => true, 'message' => 'Equipment status updated to ' . ucfirst($new_status)];
        } else {
            return ['success' => false, 'message' => 'Error updating status: ' . $stmt->error];
        }
    }
    
    public function updateQuantity($new_quantity) {
        if (!is_numeric($new_quantity) || $new_quantity < 0) {
            return ['success' => false, 'message' => 'Quantity cannot be negative.'];
        }
        
        $borrowed_count = $this->getBorrowedCount();
        
        if ($new_quantity < $borrowed_count) {
            return ['success' => false, 'message' => "Cannot reduce quantity below currently borrowed amount ($borrowed_count)."];
        }
        
        $sql = "UPDATE equipment 
                SET quantity = ?, 
                    available_quantity = quantity - ?,
                    updated_at = NOW() 
                WHERE equipment_id = ?";
                
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return ['success' => false, 'message' => 'Error preparing statement: ' . $this->conn->error];
        }
        
        $stmt->bind_param("iii", $new_quantity, $borrowed_count, $this->equipment_id);
        
        if ($stmt->execute()) {
            $this->quantity = $new_quantity;
            $this->available_quantity = $new_quantity - $borrowed_count;
            return ['success' => true, 'message' => 'Equipment quantity updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Error updating quantity: ' . $stmt->error];
        }
    }
    
    // Check if equipment code exists
    private function codeExists($code, $exclude_id = null) {
        $sql = "SELECT COUNT(*) as count FROM equipment WHERE equipment_code = ?";
        $params = [$code];
        $types = "s";
        
        if ($exclude_id !== null) {
            $sql .= " AND equipment_id != ?";
            $params[] = $exclude_id;
            $types .= "i";
        }
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log('Error preparing statement in codeExists: ' . $this->conn->error);
            return true;
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['count'] > 0;
    }
    
    public function isBorrowed() {
        $sql = "SELECT COUNT(*) as count FROM borrowings WHERE equipment_id = ? AND status = 'active'";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log('Error preparing statement in isBorrowed: ' . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("i", $this->equipment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['count'] > 0;
    }
    
    public function getBorrowedCount() {
        $sql = "SELECT COUNT(*) as count FROM borrowings WHERE equipment_id = ? AND status = 'active'";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log('Error preparing statement in getBorrowedCount: ' . $this->conn->error);
            return 0;
        }
        
        $stmt->bind_param("i", $this->equipment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['count'];
    }
    
    public function getBorrowingHistory($limit = 10) {
        $history = [];
        
        if (!isset($this->equipment_id) || empty($this->equipment_id)) {
            return $history;
        }
        
        $sql = "SELECT b.borrowing_id, u.first_name, u.last_name, 
                b.borrow_date, b.due_date, b.return_date, b.status, b.borrowed_quantity
                FROM borrowings b
                JOIN users u ON b.user_id = u.user_id
                WHERE b.equipment_id = ?
                ORDER BY b.borrow_date DESC
                LIMIT ?";
                
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            error_log('Error preparing statement in getBorrowingHistory: ' . $this->conn->error);
            return $history;
        }
        
        $limit_value = (int)$limit;
        
        $stmt->bind_param("ii", $this->equipment_id, $limit_value);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        return $history;
    }
    
    public function getCurrentBorrower() {
        if ($this->status !== 'borrowed') {
            return null;
        }
        
        $sql = "SELECT b.borrowing_id, u.first_name, u.last_name, u.email,
                b.borrow_date, b.due_date, b.purpose, b.borrowed_quantity
                FROM borrowings b
                JOIN users u ON b.user_id = u.user_id
                WHERE b.equipment_id = ? AND b.status = 'active'
                ORDER BY b.borrow_date DESC
                LIMIT 1";
                
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log('Error preparing statement in getCurrentBorrower: ' . $this->conn->error);
            return null;
        }
        
        $stmt->bind_param("i", $this->equipment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    // Getter methods
    public function getId() {
        return $this->equipment_id;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getDescription() {
        return $this->description;
    }
    
    public function getEquipmentCode() {
        return $this->equipment_code;
    }
    
    public function getCategoryId() {
        return $this->category_id;
    }
    
    public function getConditionStatus() {
        return $this->condition_status;
    }
    
    public function getStatus() {
        return $this->status;
    }
    
    public function getAcquisitionDate() {
        return $this->acquisition_date;
    }
    
    public function getNotes() {
        return $this->notes;
    }
    
    public function getQuantity() {
        return $this->quantity;
    }
    
    public function getAvailableQuantity() {
        return $this->available_quantity;
    }
    
    public function getCreatedAt() {
        return $this->created_at;
    }
    
    public function getUpdatedAt() {
        return $this->updated_at;
    }
    
    // Get all equipment with optional filtering
    public static function getAll($conn, $filters = []) {
        $equipment = [];
        $where_clauses = ["1=1"]; 
        $params = [];
        $types = "";
        
        if (!empty($filters['status'])) {
            if ($filters['status'] == 'borrowed') {
                $where_clauses[] = "b.equipment_id IS NOT NULL AND b.status = 'active'";
            } else {
                $status = $filters['status'];
                $where_clauses[] = "e.status = ? AND (b.equipment_id IS NULL OR b.status != 'active')";
                $params[] = $status;
                $types .= "s";
            }
        }
        
        // Handle category filter
        if (!empty($filters['category']) && $filters['category'] > 0) {
            $where_clauses[] = "e.category_id = ?";
            $params[] = $filters['category'];
            $types .= "i";
        }
        
        // Handle search query
        if (!empty($filters['search'])) {
            $where_clauses[] = "(e.name LIKE ? OR e.equipment_code LIKE ? OR e.description LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $types .= "sss";
        }
        
        $sql = "SELECT 
                e.*, 
                c.name as category_name,
                CASE WHEN b.equipment_id IS NOT NULL AND b.status = 'active' THEN 'borrowed' ELSE e.status END as display_status,
                (SELECT COUNT(*) FROM borrowings WHERE equipment_id = e.equipment_id AND status = 'active') as borrowed_count
                FROM equipment e
                JOIN categories c ON e.category_id = c.category_id
                LEFT JOIN borrowings b ON e.equipment_id = b.equipment_id AND b.status = 'active'
                WHERE " . implode(" AND ", $where_clauses);
        
        // Add ordering
        $sql .= " ORDER BY e.name ASC";
        
        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                error_log('Error preparing statement in getAll: ' . $conn->error);
                return $equipment;
            }
            
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($sql);
        }
        
        while ($row = $result->fetch_assoc()) {
            if (!isset($row['available_quantity'])) {
                $row['available_quantity'] = $row['quantity'] - $row['borrowed_count'];
            }
            $equipment[] = $row;
        }
        
        return $equipment;
    }
}
?>