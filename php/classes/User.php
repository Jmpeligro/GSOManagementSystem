<?php
class User {
    private $conn;
    private $user_id;
    private $university_id;
    private $first_name;
    private $last_name;
    private $email;
    private $password;
    private $role;
    private $department;
    private $phone;
    private $archived;
    private $archived_at;
    private $created_at;
    private $updated_at;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function load($user_id) {
        $sql = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $user_data = $result->fetch_assoc();
        $this->setData($user_data);
        return true;
    }

    public function setData($data) {
        $this->user_id = $data['user_id'] ?? null;
        $this->university_id = $data['university_id'] ?? null;
        $this->first_name = $data['first_name'] ?? null;
        $this->last_name = $data['last_name'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->password = $data['password'] ?? null;
        $this->role = $data['role'] ?? null;
        $this->department = $data['department'] ?? null;
        $this->phone = $data['phone'] ?? null;
        $this->archived = $data['archived'] ?? false;
        $this->archived_at = $data['archived_at'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }
    
    public function create($data) {
        // Validate required fields
        $errors = $this->validateData($data);
        if (!empty($errors)) {
            return ['success' => false, 'message' => implode("<br>", $errors)];
        }

        // Check if email exists
        if ($this->emailExists($data['email'])) {
            return ['success' => false, 'message' => 'Email address already in use'];
        }
        
        // Hash password
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Set default values for optional fields
        $phone = $data['phone'] ?? '';
        
        $sql = "INSERT INTO users (
            university_id,
            first_name, 
            last_name, 
            email, 
            password, 
            role, 
            department,
            phone,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($sql);
        
        // Check if prepare was successful
        if ($stmt === false) {
            return ['success' => false, 'message' => 'Error preparing statement: ' . $this->conn->error];
        }

        $stmt->bind_param("ssssssss", 
            $data['university_id'],
            $data['first_name'], 
            $data['last_name'], 
            $data['email'], 
            $hashed_password, 
            $data['role'], 
            $data['department'],
            $phone
        );
        
        if ($stmt->execute()) {
            $this->user_id = $stmt->insert_id;
            $this->setData($data);
            return ['success' => true, 'message' => 'User added successfully.'];
        } else {
            return ['success' => false, 'message' => 'Error adding user: ' . $stmt->error];
        }
    }

    public function update($data) {
        // Validate required fields except password
        $errors = $this->validateData($data, false);
        if (!empty($errors)) {
            return ['success' => false, 'message' => implode("<br>", $errors)];
        }

        // Check if email exists
        if ($this->emailExists($data['email'], $this->user_id)) {
            return ['success' => false, 'message' => 'Email address already in use'];
        }
        
        // Check if password is being updated
        $password_update = !empty($data['password']);
        if ($password_update) {
            if (strlen($data['password']) < 8) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
            }
            
            if ($data['password'] !== $data['confirm_password']) {
                return ['success' => false, 'message' => 'Passwords do not match'];
            }
        }
        
        // Set default values for optional fields
        $phone = $data['phone'] ?? '';
        
        if ($password_update) {
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE users 
                    SET university_id = ?,
                        first_name = ?, 
                        last_name = ?, 
                        email = ?, 
                        password = ?,
                        role = ?, 
                        department = ?,
                        phone = ?,
                        updated_at = NOW() 
                    WHERE user_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            
            if ($stmt === false) {
                return ['success' => false, 'message' => 'Error preparing statement: ' . $this->conn->error];
            }
            
            $stmt->bind_param("ssssssssi", 
                $data['university_id'],
                $data['first_name'], 
                $data['last_name'], 
                $data['email'], 
                $hashed_password,
                $data['role'], 
                $data['department'],
                $phone,
                $this->user_id
            );
        } else {
            $sql = "UPDATE users 
                    SET university_id = ?,
                        first_name = ?, 
                        last_name = ?, 
                        email = ?, 
                        role = ?, 
                        department = ?,
                        phone = ?,
                        updated_at = NOW() 
                    WHERE user_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            
            if ($stmt === false) {
                return ['success' => false, 'message' => 'Error preparing statement: ' . $this->conn->error];
            }
            
            $stmt->bind_param("sssssssi", 
                $data['university_id'],
                $data['first_name'], 
                $data['last_name'], 
                $data['email'], 
                $data['role'], 
                $data['department'],
                $phone,
                $this->user_id
            );
        }
        
        if ($stmt->execute()) {
            $this->setData($data);
            return ['success' => true, 'message' => 'User updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Error updating user: ' . $stmt->error];
        }
    }
    
    public function delete() {
        // First check if user has active borrowings
        if ($this->hasActiveBorrowings()) {
            return ['success' => false, 'message' => 'Cannot delete user with active borrowings.'];
        }
        
        $sql = "DELETE FROM users WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return ['success' => false, 'message' => 'Error preparing statement: ' . $this->conn->error];
        }
        
        $stmt->bind_param("i", $this->user_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'User deleted successfully.'];
        } else {
            return ['success' => false, 'message' => 'Error deleting user: ' . $stmt->error];
        }
    }
    
    public function archive() {
        // First check if user has active borrowings
        if ($this->hasActiveBorrowings()) {
            return ['success' => false, 'message' => 'Cannot archive user with active borrowings.'];
        }
        
        $sql = "UPDATE users 
                SET archived = TRUE, 
                    archived_at = NOW() 
                WHERE user_id = ?";
                
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return ['success' => false, 'message' => 'Error preparing statement: ' . $this->conn->error];
        }
        
        $stmt->bind_param("i", $this->user_id);
        
        if ($stmt->execute()) {
            $this->archived = true;
            $this->archived_at = date('Y-m-d H:i:s');
            return ['success' => true, 'message' => 'User archived successfully.'];
        } else {
            return ['success' => false, 'message' => 'Error archiving user: ' . $stmt->error];
        }
    }
    
    public function restore() {
        $sql = "UPDATE users 
                SET archived = FALSE, 
                    archived_at = NULL 
                WHERE user_id = ?";
                
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return ['success' => false, 'message' => 'Error preparing statement: ' . $this->conn->error];
        }
        
        $stmt->bind_param("i", $this->user_id);
        
        if ($stmt->execute()) {
            $this->archived = false;
            $this->archived_at = null;
            return ['success' => true, 'message' => 'User restored successfully.'];
        } else {
            return ['success' => false, 'message' => 'Error restoring user: ' . $stmt->error];
        }
    }
    
    // Check if email exists
    private function emailExists($email, $exclude_id = null) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $params = [$email];
        $types = "s";
        
        if ($exclude_id !== null) {
            $sql .= " AND user_id != ?";
            $params[] = $exclude_id;
            $types .= "i";
        }
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log('Error preparing statement in emailExists: ' . $this->conn->error);
            return true;
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['count'] > 0;
    }
    
    // Check if user has active borrowings
    public function hasActiveBorrowings() {
        $sql = "SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status = 'active'";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log('Error preparing statement in hasActiveBorrowings: ' . $this->conn->error);
            return true; 
        }
        
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['count'] > 0;
    }
    
    // Validate user data
    private function validateData($data, $check_password = true) {
        $errors = [];
        
        if (empty($data['university_id'])) {
            $errors[] = "University ID is required";
        }
        
        if (empty($data['first_name'])) {
            $errors[] = "First name is required";
        }
        
        if (empty($data['last_name'])) {
            $errors[] = "Last name is required";
        }
        
        if (empty($data['email'])) {
            $errors[] = "Email is required";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        if (empty($data['role'])) {
            $errors[] = "Role is required";
        } elseif (!in_array($data['role'], ['student', 'faculty', 'staff', 'admin'])) {
            $errors[] = "Invalid role selected";
        }
        
        if (empty($data['department'])) {
            $errors[] = "Department/Course is required";
        }
        
        if ($check_password) {
            if (empty($data['password'])) {
                $errors[] = "Password is required";
            } elseif (strlen($data['password']) < 8) {
                $errors[] = "Password must be at least 8 characters long";
            }
            
            if ($data['password'] !== $data['confirm_password']) {
                $errors[] = "Passwords do not match";
            }
        }
        
        return $errors;
    }
    
    public function getBorrowingHistory($limit = 10) {
        $history = [];
        
        // Check if user_id is set
        if (!isset($this->user_id) || empty($this->user_id)) {
            return $history;
        }
        
        $sql = "SELECT b.borrowing_id, e.name as equipment_name, e.equipment_code,
                b.borrow_date, b.due_date, b.return_date, b.status, b.borrowed_quantity
                FROM borrowings b
                JOIN equipment e ON b.equipment_id = e.equipment_id
                WHERE b.user_id = ?
                ORDER BY b.borrow_date DESC
                LIMIT ?";
                
        $stmt = $this->conn->prepare($sql);
        
        // Check if the prepare was successful
        if ($stmt === false) {
            // Log the error for debugging
            error_log('Error preparing statement in getBorrowingHistory: ' . $this->conn->error);
            return $history;
        }
        
        // Use a variable for limit parameter
        $limit_value = (int)$limit;
        
        $stmt->bind_param("ii", $this->user_id, $limit_value);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        return $history;
    }
    
    public function getCurrentBorrowings() {
        $borrowings = [];
        
        $sql = "SELECT b.borrowing_id, e.name as equipment_name, e.equipment_code,
                b.borrow_date, b.due_date, b.borrowed_quantity, b.purpose
                FROM borrowings b
                JOIN equipment e ON b.equipment_id = e.equipment_id
                WHERE b.user_id = ? AND b.status = 'active'
                ORDER BY b.due_date ASC";
                
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log('Error preparing statement in getCurrentBorrowings: ' . $this->conn->error);
            return $borrowings;
        }
        
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $borrowings[] = $row;
        }
        
        return $borrowings;
    }
    
    // Getter methods
    public function getId() {
        return $this->user_id;
    }
    
    public function getUniversityId() {
        return $this->university_id;
    }
    
    public function getFirstName() {
        return $this->first_name;
    }
    
    public function getLastName() {
        return $this->last_name;
    }
    
    public function getFullName() {
        return $this->first_name . ' ' . $this->last_name;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    public function getRole() {
        return $this->role;
    }
    
    public function getDepartment() {
        return $this->department;
    }
    
    public function getPhone() {
        return $this->phone;
    }
    
    public function isArchived() {
        return $this->archived;
    }
    
    public function getArchivedAt() {
        return $this->archived_at;
    }
    
    public function getCreatedAt() {
        return $this->created_at;
    }
    
    public function getUpdatedAt() {
        return $this->updated_at;
    }
    
    // Static methods for getting user lists
    public static function getAll($conn, $filters = []) {
        $users = [];
        $where_clauses = ["1=1"]; 
        $params = [];
        $types = "";
        
        // Handle role filter
        if (!empty($filters['role'])) {
            $where_clauses[] = "role = ?";
            $params[] = $filters['role'];
            $types .= "s";
        }
        
        // Handle archived filter
        if (isset($filters['archived'])) {
            $where_clauses[] = "archived = ?";
            $params[] = $filters['archived'] ? 1 : 0;
            $types .= "i";
        }
        
        // Handle search query
        if (!empty($filters['search'])) {
            $where_clauses[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR department LIKE ? OR university_id LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $types .= "sssss";
        }
        
        $sql = "SELECT 
                u.*,
                (SELECT COUNT(*) FROM borrowings WHERE user_id = u.user_id AND status = 'active') as active_borrowings
                FROM users u
                WHERE " . implode(" AND ", $where_clauses);

        $sql .= " ORDER BY u.archived ASC, u.last_name ASC, u.first_name ASC";
        
        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                error_log('Error preparing statement in getAll: ' . $conn->error);
                return $users;
            }
            
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($sql);
        }
        
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        return $users;
    }
    
    // Method to authenticate user by email and password
    public static function authenticate($conn, $email, $password) {
        $sql = "SELECT * FROM users WHERE email = ? AND archived = FALSE";
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            error_log('Error preparing statement in authenticate: ' . $conn->error);
            return false;
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }
}
?>