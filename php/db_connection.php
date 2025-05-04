<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Auth.php';

$db = Database::getInstance();
$conn = $db->getConnection();

if (!function_exists('sanitize')) {
    function sanitize($data) {
        global $db;
        return $db->sanitize($data);
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return Auth::isLoggedIn();
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return Auth::isAdmin();
    }
}
?>