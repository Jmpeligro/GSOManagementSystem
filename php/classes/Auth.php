<?php
class Auth {
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public static function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
 
    public static function authenticate($email, $password, $conn) {
        $email = $conn->real_escape_string($email);
        
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department'] = $user['department'];
                
                return ['success' => true, 'message' => 'Login successful'];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid email or password'];
    }

    public static function logout() {
        $_SESSION = [];

        session_destroy();
        
        return ['success' => true, 'message' => 'Logout successful'];
    }

    public static function checkAccess($required_role = 'user') {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        if ($required_role === 'admin' && !self::isAdmin()) {
            return false;
        }
        
        return true;
    }
}
?>  