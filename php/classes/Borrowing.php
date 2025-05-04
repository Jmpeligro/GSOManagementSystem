<?php
class Borrowing {
    private $conn;
    private $borrowing_id;
    private $equipment_id;
    private $user_id;
    private $borrow_date;
    private $due_date;
    private $return_date;
    private $admin_issued_id;
    private $admin_received_id;
    private $status;
    private $condition_on_return;
    private $notes;
    private $approval_status;
    private $approved_by;
    private $approval_date;
    private $admin_notes;
    private $return_notes;
    private $returned_by;
    private $created_at;
    private $updated_at;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function load($borrowing_id) {
        $sql = "SELECT * FROM borrowings WHERE borrowing_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $borrowing_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $borrowing_data = $result->fetch_assoc();
        $this->setData($borrowing_data);
        return true;
    }
    
    public function setData($data) {
        $this->borrowing_id = $data['borrowing_id'] ?? null;
        $this->equipment_id = $data['equipment_id'] ?? null;
        $this->user_id = $data['user_id'] ?? null;
        $this->borrow_date = $data['borrow_date'] ?? null;
        $this->due_date = $data['due_date'] ?? null;
        $this->return_date = $data['return_date'] ?? null;
        $this->admin_issued_id = $data['admin_issued_id'] ?? null;
        $this->admin_received_id = $data['admin_received_id'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->condition_on_return = $data['condition_on_return'] ?? null;
        $this->notes = $data['notes'] ?? null;
        $this->approval_status = $data['approval_status'] ?? null;
        $this->approved_by = $data['approved_by'] ?? null;
        $this->approval_date = $data['approval_date'] ?? null;
        $this->admin_notes = $data['admin_notes'] ?? null;
        $this->return_notes = $data['return_notes'] ?? null;
        $this->returned_by = $data['returned_by'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }
    
    public function create($data) {
        // Validate required fields
        if (empty($data['equipment_id'])) {
            return ['success' => false, 'message' => 'Equipment ID is required.'];
        }
        
        if (empty($data['user_id'])) {
            return ['success' => false, 'message' => 'User ID is required.'];
        }
        
        if (empty($data['borrow_date'])) {
            return ['success' => false, 'message' => 'Borrow date is required.'];
        }
        
        if (empty($data['due_date'])) {
            return ['success' => false, 'message' => 'Due date is required.'];
        }
        
        // Validate dates
        $borrow_datetime = new DateTime($data['borrow_date']);
        $due_datetime = new DateTime($data['due_date']);
        if ($borrow_datetime > $due_datetime) {
            return ['success' => false, 'message' => 'Due date must be after the borrow date.'];
        }
        
        // Validate equipment availability
        $equipment_available = $this->checkEquipmentAvailability($data['equipment_id']);
        if (!$equipment_available) {
            return ['success' => false, 'message' => 'Selected equipment is not available for borrowing.'];
        }
        
        // Set default values for optional fields
        $status = $data['status'] ?? 'pending';
        $notes = $data['notes'] ?? '';
        $approval_status = $data['approval_status'] ?? 'pending';
        $admin_notes = $data['admin_notes'] ?? '';
        
        $sql = "INSERT INTO borrowings (
            equipment_id,
            user_id,
            borrow_date,
            due_date,
            status,
            notes,
            approval_status,
            admin_notes,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($sql);
        
        // Check if prepare was successful
        if ($stmt === false) {
            return ['success' => false, 'message' => 'Error preparing statement: ' . $this->conn->error];
        }
        
        $stmt->bind_param("iissssss",
            $data['equipment_id'],
            $data['user_id'],
            $data['borrow_date'],
            $data['due_date'],
            $status,
            $notes,
            $approval_status,
            $admin_notes
        );
        
        if ($stmt->execute()) {
            $this->borrowing_id = $stmt->insert_id;
            $this->setData($data);
            return ['success' => true, 'message' => 'Borrowing request created successfully.'];
        } else {
            return ['success' => false, 'message' => 'Error creating borrowing request: ' . $stmt->error];
        }
    }
    
    public function approve($admin_id, $admin_notes = '') {
        if (!$this->borrowing_id) {
            return ['success' => false, 'message' => 'No borrowing loaded.'];
        }
        
        if ($this->approval_status !== 'pending') {
            return ['success' => false, 'message' => 'This borrowing request has already been processed.'];
        }
        
        // Check if equipment is still available
        $equipment_available = $this->checkEquipmentAvailability($this->equipment_id);
        if (!$equipment_available) {
            return ['success' => false, 'message' => 'Equipment is no longer available for borrowing.'];
        }
        
        $this->conn->begin_transaction();
        
        try {
            // Update borrowing record
            $sql = "UPDATE borrowings 
                    SET approval_status = 'approved',
                        status = 'active',
                        approved_by = ?,
                        approval_date = NOW(),
                        admin_notes = ?,
                        updated_at = NOW()
                    WHERE borrowing_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Error preparing approval statement: " . $this->conn->error);
            }
            
            $stmt->bind_param("isi", $admin_id, $admin_notes, $this->borrowing_id);
            if (!$stmt->execute()) {
                throw new Exception("Error approving borrowing: " . $stmt->error);
            }
            
            // Update equipment status
            $update_equipment_sql = "UPDATE equipment 
                                     SET status = 'borrowed',
                                         available_quantity = available_quantity - 1,
                                         updated_at = NOW() 
                                     WHERE equipment_id = ?";
            
            $update_equipment_stmt = $this->conn->prepare($update_equipment_sql);
            if ($update_equipment_stmt === false) {
                throw new Exception("Error preparing equipment update: " . $this->conn->error);
            }
            
            $update_equipment_stmt->bind_param("i", $this->equipment_id);
            if (!$update_equipment_stmt->execute()) {
                throw new Exception("Error updating equipment status: " . $update_equipment_stmt->error);
            }
            
            $this->conn->commit();
            
            // Update local properties
            $this->approval_status = 'approved';
            $this->status = 'active';
            $this->approved_by = $admin_id;
            $this->approval_date = date('Y-m-d H:i:s');
            $this->admin_notes = $admin_notes;
            
            return ['success' => true, 'message' => 'Borrowing request approved successfully.'];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function deny($admin_id, $denial_reason = '') {
        if (!$this->borrowing_id) {
            return ['success' => false, 'message' => 'No borrowing loaded.'];
        }
        
        if ($this->approval_status !== 'pending') {
            return ['success' => false, 'message' => 'This borrowing request has already been processed.'];
        }
        
        $sql = "UPDATE borrowings 
                SET approval_status = 'denied',
                    admin_notes = ?,
                    approved_by = ?,
                    approval_date = NOW(),
                    updated_at = NOW()
                WHERE borrowing_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return ['success' => false, 'message' => 'Error preparing statement: ' . $this->conn->error];
        }
        
        $stmt->bind_param("sii", $denial_reason, $admin_id, $this->borrowing_id);
        
        if ($stmt->execute()) {
            // Update local properties
            $this->approval_status = 'denied';
            $this->approved_by = $admin_id;
            $this->approval_date = date('Y-m-d H:i:s');
            $this->admin_notes = $denial_reason;
            
            return ['success' => true, 'message' => 'Borrowing request denied successfully.'];
        } else {
            return ['success' => false, 'message' => 'Error denying borrowing request: ' . $stmt->error];
        }
    }
    
    public function returnEquipment($user_id, $condition = 'good', $return_notes = '') {
        if (!$this->borrowing_id) {
            return ['success' => false, 'message' => 'No borrowing loaded.'];
        }
        
        if ($this->status !== 'active' || $this->approval_status !== 'approved') {
            return ['success' => false, 'message' => 'This equipment is not currently borrowed or was not approved.'];
        }
        
        $this->conn->begin_transaction();
        
        try {
            // Update borrowing record
            $sql = "UPDATE borrowings 
                    SET status = 'returned',
                        return_date = NOW(),
                        condition_on_return = ?,
                        return_notes = ?,
                        returned_by = ?,
                        updated_at = NOW()
                    WHERE borrowing_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Error preparing return statement: " . $this->conn->error);
            }
            
            $stmt->bind_param("ssii", $condition, $return_notes, $user_id, $this->borrowing_id);
            if (!$stmt->execute()) {
                throw new Exception("Error returning equipment: " . $stmt->error);
            }
            
            // Update equipment status
            $update_equipment_sql = "UPDATE equipment 
                                     SET available_quantity = available_quantity + 1,
                                         updated_at = NOW() 
                                     WHERE equipment_id = ?";
            
            $update_equipment_stmt = $this->conn->prepare($update_equipment_sql);
            if ($update_equipment_stmt === false) {
                throw new Exception("Error preparing equipment update: " . $this->conn->error);
            }
            
            $update_equipment_stmt->bind_param("i", $this->equipment_id);
            if (!$update_equipment_stmt->execute()) {
                throw new Exception("Error updating equipment status: " . $update_equipment_stmt->error);
            }
            
            // Check if all items of this equipment are returned, update status if needed
            $check_sql = "SELECT quantity, available_quantity FROM equipment WHERE equipment_id = ?";
            $check_stmt = $this->conn->prepare($check_sql);
            $check_stmt->bind_param("i", $this->equipment_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $equipment_data = $result->fetch_assoc();
            
            if ($equipment_data['quantity'] == $equipment_data['available_quantity']) {
                $status_sql = "UPDATE equipment SET status = 'available' WHERE equipment_id = ?";
                $status_stmt = $this->conn->prepare($status_sql);
                $status_stmt->bind_param("i", $this->equipment_id);
                $status_stmt->execute();
            }
            
            $this->conn->commit();
            
            // Update local properties
            $this->status = 'returned';
            $this->return_date = date('Y-m-d H:i:s');
            $this->condition_on_return = $condition;
            $this->return_notes = $return_notes;
            $this->returned_by = $user_id;
            
            return ['success' => true, 'message' => 'Equipment returned successfully.'];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function cancel() {
        if (!$this->borrowing_id) {
            return ['success' => false, 'message' => 'No borrowing loaded.'];
        }
        
        if ($this->approval_status !== 'pending' || $this->status === 'active') {
            return ['success' => false, 'message' => 'Only pending borrowing requests can be cancelled.'];
        }
        
        $sql = "DELETE FROM borrowings WHERE borrowing_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt === false) {
            return ['success' => false, 'message' => 'Error preparing statement: ' . $this->conn->error];
        }
        
        $stmt->bind_param("i", $this->borrowing_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Borrowing request cancelled successfully.'];
        } else {
            return ['success' => false, 'message' => 'Error cancelling borrowing request: ' . $stmt->error];
        }
    }
    
    private function checkEquipmentAvailability($equipment_id) {
        $sql = "SELECT status, available_quantity FROM equipment WHERE equipment_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $equipment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $equipment_data = $result->fetch_assoc();
        return ($equipment_data['status'] === 'available' && $equipment_data['available_quantity'] > 0);
    }
    
    public function isOverdue() {
        if ($this->status === 'active' && $this->due_date) {
            $due_datetime = new DateTime($this->due_date);
            $current_datetime = new DateTime();
            return $current_datetime > $due_datetime;
        }
        return false;
    }
    
    public function getBorrowerDetails() {
        if (!$this->user_id) {
            return null;
        }
        
        $sql = "SELECT user_id, first_name, last_name, email, university_id, role, department 
                FROM users WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }
    
    public function getEquipmentDetails() {
        if (!$this->equipment_id) {
            return null;
        }
        
        $sql = "SELECT e.*, c.name as category_name 
                FROM equipment e
                JOIN categories c ON e.category_id = c.category_id
                WHERE e.equipment_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->equipment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }
    
    // Getter methods
    public function getId() {
        return $this->borrowing_id;
    }
    
    public function getEquipmentId() {
        return $this->equipment_id;
    }
    
    public function getUserId() {
        return $this->user_id;
    }
    
    public function getBorrowDate() {
        return $this->borrow_date;
    }
    
    public function getDueDate() {
        return $this->due_date;
    }
    
    public function getReturnDate() {
        return $this->return_date;
    }
    
    public function getAdminIssuedId() {
        return $this->admin_issued_id;
    }
    
    public function getAdminReceivedId() {
        return $this->admin_received_id;
    }
    
    public function getStatus() {
        return $this->status;
    }
    
    public function getConditionOnReturn() {
        return $this->condition_on_return;
    }
    
    public function getNotes() {
        return $this->notes;
    }
    
    public function getApprovalStatus() {
        return $this->approval_status;
    }
    
    public function getApprovedBy() {
        return $this->approved_by;
    }
    
    public function getApprovalDate() {
        return $this->approval_date;
    }
    
    public function getAdminNotes() {
        return $this->admin_notes;
    }
    
    public function getReturnNotes() {
        return $this->return_notes;
    }
    
    public function getReturnedBy() {
        return $this->returned_by;
    }
    
    public function getCreatedAt() {
        return $this->created_at;
    }
    
    public function getUpdatedAt() {
        return $this->updated_at;
    }
    
    // Static methods to fetch borrowings
    public static function getAll($conn, $filters = []) {
        $borrowings = [];
        $where_clauses = ["1=1"]; 
        $params = [];
        $types = "";
        
        // Handle status filter
        if (!empty($filters['status'])) {
            $where_clauses[] = "b.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
        
        // Handle approval status filter
        if (!empty($filters['approval_status'])) {
            $where_clauses[] = "b.approval_status = ?";
            $params[] = $filters['approval_status'];
            $types .= "s";
        }
        
        // Handle equipment filter
        if (!empty($filters['equipment_id']) && $filters['equipment_id'] > 0) {
            $where_clauses[] = "b.equipment_id = ?";
            $params[] = $filters['equipment_id'];
            $types .= "i";
        }
        
        // Handle user filter
        if (!empty($filters['user_id']) && $filters['user_id'] > 0) {
            $where_clauses[] = "b.user_id = ?";
            $params[] = $filters['user_id'];
            $types .= "i";
        }
        
        // Handle date range filter
        if (!empty($filters['date_from'])) {
            $where_clauses[] = "b.borrow_date >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = "b.borrow_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
            $types .= "s";
        }
        
        // Handle search query
        if (!empty($filters['search'])) {
            $where_clauses[] = "(e.name LIKE ? OR e.equipment_code LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            for ($i = 0; $i < 5; $i++) {
                $params[] = $search_term;
                $types .= "s";
            }
        }
        
        // Handle condition filter
        if (!empty($filters['condition'])) {
            $where_clauses[] = "b.condition_on_return = ?";
            $params[] = $filters['condition'];
            $types .= "s";
        }
        
        $sql = "SELECT 
                b.*,
                e.name as equipment_name, 
                e.equipment_code,
                u.first_name, 
                u.last_name, 
                u.email
                FROM borrowings b
                JOIN equipment e ON b.equipment_id = e.equipment_id
                JOIN users u ON b.user_id = u.user_id
                WHERE " . implode(" AND ", $where_clauses) . "
                ORDER BY b.borrow_date DESC";
        
        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                return $borrowings;
            }
            
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($sql);
        }
        
        while ($row = $result->fetch_assoc()) {
            $borrowings[] = $row;
        }
        
        return $borrowings;
    }
    
    public static function getPendingRequests($conn) {
        return self::getAll($conn, ['approval_status' => 'pending']);
    }
    
    public static function getUserBorrowings($conn, $user_id) {
        return self::getAll($conn, ['user_id' => $user_id]);
    }
    
    public static function getOverdueBorrowings($conn) {
        $overdue_borrowings = [];
        
        $sql = "SELECT 
                b.*,
                e.name as equipment_name, 
                e.equipment_code,
                u.first_name, 
                u.last_name, 
                u.email
                FROM borrowings b
                JOIN equipment e ON b.equipment_id = e.equipment_id
                JOIN users u ON b.user_id = u.user_id
                WHERE b.status = 'active' 
                AND b.due_date < NOW()
                ORDER BY b.due_date ASC";
        
        $result = $conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $overdue_borrowings[] = $row;
        }
        
        return $overdue_borrowings;
    }
    
    public static function getEquipmentBorrowingHistory($conn, $equipment_id, $limit = 10) {
        $history = [];
        
        $sql = "SELECT b.*, 
                u.first_name, u.last_name, u.email
                FROM borrowings b
                JOIN users u ON b.user_id = u.user_id
                WHERE b.equipment_id = ?
                ORDER BY b.borrow_date DESC
                LIMIT ?";
                
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return $history;
        }
        
        $limit_value = (int)$limit;
        $stmt->bind_param("ii", $equipment_id, $limit_value);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        return $history;
    }
}
?>