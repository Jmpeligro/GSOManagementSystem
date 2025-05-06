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
    private $program_year_section;
    private $status;
    private $status_changed_at;
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
        $this->program_year_section = $data['program_year_section'] ?? null;
        $this->status = $data['status'] ?? 'active';
        $this->status_changed_at = $data['status_changed_at'] ?? null;
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
        $program_year_section = ($data['role'] === 'student') ? ($data['program_year_section'] ?? '') : '';
        
        $sql = "INSERT INTO users (
            university_id,
            first_name, 
            last_name, 
            email, 
            password, 
            role, 
            department,
            phone,
            program_year_section,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($sql);
        
        // Check if prepare was successful
        if ($stmt === false) {
            return ['success' => false, 'message' => 'Error preparing statement: ' . $this->conn->error];
        }

        $stmt->bind_param("sssssssss", 
            $data['university_id'],
            $data['first_name'], 
            $data['last_name'], 
            $data['email'], 
            $hashed_password, 
            $data['role'], 
            $data['department'],
            $phone,
            $program_year_section
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
        $program_year_section = ($data['role'] === 'student') ? ($data['program_year_section'] ?? '') : '';
        
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
                        program_year_section = ?,
                        updated_at = NOW() 
                    WHERE user_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            
            if ($stmt === false) {
                return ['success' => false, 'message' => 'Error preparing statement: ' . $this->conn->error];
            }
            
            $stmt->bind_param("sssssssssi", 
                $data['university_id'],
                $data['first_name'], 
                $data['last_name'], 
                $data['email'], 
                $hashed_password,
                $data['role'], 
                $data['department'],
                $phone,
                $program_year_section,
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
                        program_year_section = ?,
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
                $data['role'], 
                $data['department'],
                $phone,
                $program_year_section,
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
        
        if ($data['role'] === 'student' && empty($data['program_year_section'])) {
            $errors[] = "Program/Year/Section is required for students";
        }
        
        if ($check_password) {
            if (empty($data['password'])) {
                $errors[] = "Password is required";
            } elseif (strlen($data['password']) < 8) {
                $errors[] = "Password must be at least 8 characters long";
            }
            
            if (empty($data['confirm_password'])) {
                $errors[] = "Confirm password is required";
            } elseif ($data['password'] !== $data['confirm_password']) {
                $errors[] = "Passwords do not match";
            }
        }
        
        return $errors;
    }
    
    // Check if email exists (for another user)
    private function emailExists($email, $current_user_id = null) {
        $sql = "SELECT user_id FROM users WHERE email = ?";
        $params = [$email];
        $types = "s";
        
        if ($current_user_id) {
            $sql .= " AND user_id != ?";
            $params[] = $current_user_id;
            $types .= "i";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
    
    public function setStatus($status) {
        if (!in_array($status, ['active', 'inactive', 'archived'])) {
            return ['success' => false, 'message' => 'Invalid status value'];
        }
        
        $sql = "UPDATE users SET status = ?, status_changed_at = NOW() WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $this->user_id);
        
        if ($stmt->execute()) {
            $this->status = $status;
            $this->status_changed_at = date('Y-m-d H:i:s');
            
            $status_message = ucfirst($status);
            return ['success' => true, 'message' => "User marked as {$status_message} successfully."];
        } else {
            return ['success' => false, 'message' => 'Error updating user status: ' . $stmt->error];
        }
    }
    
    // Restore a user from archive
    public function restore() {
        $sql = "UPDATE users SET archived = 0, archived_at = NULL WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->user_id);
        
        if ($stmt->execute()) {
            $this->archived = 0;
            $this->archived_at = null;
            return ['success' => true, 'message' => 'User restored successfully.'];
        } else {
            return ['success' => false, 'message' => 'Error restoring user: ' . $stmt->error];
        }
    }
    
    // Delete a user permanently
    public function delete() {
        $sql = "DELETE FROM users WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->user_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'User deleted successfully.'];
        } else {
            return ['success' => false, 'message' => 'Error deleting user: ' . $stmt->error];
        }
    }
    
    public static function getAll($conn, $filters = []) {
        $where_clauses = [];
        $params = [];
        $types = "";
        
        // Filter by role
        if (!empty($filters['role'])) {
            $where_clauses[] = "role = ?";
            $params[] = $filters['role'];
            $types .= "s";
        }
        
        // Updated filter by status
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $placeholders = array_fill(0, count($filters['status']), '?');
                $where_clauses[] = "status IN (" . implode(',', $placeholders) . ")";
                foreach ($filters['status'] as $status) {
                    $params[] = $status;
                    $types .= "s";
                }
            } else {
                $where_clauses[] = "status = ?";
                $params[] = $filters['status'];
                $types .= "s";
            }
        }
        
        // Search by name, email, or university ID
        if (!empty($filters['search'])) {
            $search_term = "%" . $filters['search'] . "%";
            $where_clauses[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR university_id LIKE ?)";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $types .= "ssss";
        }
        
        $sql = "SELECT * FROM users";
        
        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }
        
        $sql .= " ORDER BY last_name, first_name";
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        return $users;
    }
    
    public function activate() {
        return $this->setStatus('active');
    }
    
    public function deactivate() {
        return $this->setStatus('inactive');
    }
    
    public function archive() {
        return $this->setStatus('archived');
    }
    
    // Getters
    public function getUserId() {
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
    
    public function getProgramYearSection() {
        return $this->program_year_section;
    }
    
    public function getStatus() {
        return $this->status;
    }
    
    public function getStatusChangedAt() {
        return $this->status_changed_at;
    }
    
    public function isActive() {
        return $this->status === 'active';
    }
    
    public function isInactive() {
        return $this->status === 'inactive';
    }
    
    public function isArchived() {
        return $this->status === 'archived';
    }
    
    public function getCreatedAt() {
        return $this->created_at;
    }
    
    public function getUpdatedAt() {
        return $this->updated_at;
    }
    
    // Verify password
    public function verifyPassword($password) {
        return password_verify($password, $this->password);
    }
    
    // Check if user is admin
    public function isAdmin() {
        return $this->role === 'admin';
    }
    
    // Check if user is faculty
    public function isFaculty() {
        return $this->role === 'faculty';
    }
    
    // Check if user is student
    public function isStudent() {
        return $this->role === 'student';
    }
    
    // Check if user is staff
    public function isStaff() {
        return $this->role === 'staff';
    }
}
?>