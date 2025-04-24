<?php
session_start();
require_once 'db_connection.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_credential = sanitize($_POST['login_credential']);
    $password = $_POST['password'];
    
    if (empty($login_credential) || empty($password)) {
        $error = "Please enter both your credentials and password.";
    } else {
        $is_email = filter_var($login_credential, FILTER_VALIDATE_EMAIL);
        
        if ($is_email) {
            $query = "SELECT user_id, first_name, last_name, email, university_id, password, role FROM users WHERE email = ?";
        } else {
            $query = "SELECT user_id, first_name, last_name, email, university_id, password, role FROM users WHERE university_id = ?";
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $login_credential);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['university_id'] = $user['university_id'];
                $_SESSION['role'] = $user['role'];

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid password. Please try again.";
            }
        } else {
            $error = "No account found with those credentials. Please check and try again.";
        }
        
        $stmt->close();
    }
}

include 'login.html';
?>
