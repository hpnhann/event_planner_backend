<?php
class Auth {
    private static $currentUser = null;
    
    public static function check() {
        session_start();
        
        if (!isset($_SESSION['uid']) || empty($_SESSION['uid'])) {
            Response::error('Unauthorized', 401);
            exit;
        }
        
        return true;
    }
    
    public static function checkRole($allowedRoles = []) {
        self::check();
        
        $db = Database::getInstance();
        $uid = $_SESSION['uid'];
        
        $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("s", $uid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            Response::error('User not found', 404);
            exit;
        }
        
        $user = $result->fetch_assoc();
        
        if (!empty($allowedRoles) && !in_array($user['role'], $allowedRoles)) {
            Response::error('Forbidden - Insufficient permissions', 403);
            exit;
        }
        
        return $user;
    }
    
    public static function user() {
        if (self::$currentUser === null) {
            session_start();
            
            if (!isset($_SESSION['uid'])) {
                return null;
            }
            
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("s", $_SESSION['uid']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            self::$currentUser = $result->fetch_assoc();
        }
        
        return self::$currentUser;
    }
    
    public static function login($userId) {
        session_start();
        $_SESSION['uid'] = $userId;
        
        // Regenerate session ID for security
        session_regenerate_id(true);
    }
    
    public static function logout() {
        session_start();
        session_unset();
        session_destroy();
    }
}
