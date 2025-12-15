<?php
class AdminAuth {
    private $db;
    
    public function __construct() {
        // Use the correct path to the database configuration
        require_once $_SERVER['DOCUMENT_ROOT'] . '/EDR/config/database.php';
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function login($email, $password) {
        // Prepare the SQL statement
        $stmt = $this->db->prepare("SELECT id, username, email, password, full_name, role, status FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            // Check if account is active
            if ($admin['status'] !== 'active') {
                return [
                    'success' => false,
                    'message' => 'Your account is ' . $admin['status'] . '. Please contact the system administrator.'
                ];
            }
            
            // Verify password
            if (password_verify($password, $admin['password'])) {
                // Start session if not already started
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                // Set session variables
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_full_name'] = $admin['full_name'];
                $_SESSION['admin_logged_in'] = true;
                
                // Update last login time
                $this->updateLastLogin($admin['id']);
                
                // Create session record in database
                $this->createSessionRecord($admin['id']);
                
                // Log activity
                $this->logActivity($admin['id'], 'login', 'Admin logged in successfully');
                
                return [
                    'success' => true,
                    'message' => 'Login successful',
                    'admin' => [
                        'id' => $admin['id'],
                        'username' => $admin['username'],
                        'email' => $admin['email'],
                        'role' => $admin['role'],
                        'full_name' => $admin['full_name']
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid password'
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Admin not found'
            ];
        }
    }
    
    public function updateLastLogin($adminId) {
        $stmt = $this->db->prepare("UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
    }
    
    public function createSessionRecord($adminId) {
        // Generate a unique token
        $token = bin2hex(random_bytes(32));
        
        // Get IP address and user agent
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Set expiration time (8 hours from now)
        $expiresAt = date('Y-m-d H:i:s', strtotime('+8 hours'));
        
        // Insert session record
        $stmt = $this->db->prepare("INSERT INTO admin_sessions (admin_id, session_token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $adminId, $token, $ipAddress, $userAgent, $expiresAt);
        $stmt->execute();
        
        // Store token in session
        $_SESSION['admin_session_token'] = $token;
    }
    
    public function logActivity($adminId, $action, $description = null) {
        // Get IP address and user agent
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Insert activity log
        $stmt = $this->db->prepare("INSERT INTO admin_activity_log (admin_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $adminId, $action, $description, $ipAddress, $userAgent);
        $stmt->execute();
    }
    
    public function logout() {
        // Check if session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_session_token'])) {
            // Log activity
            $this->logActivity($_SESSION['admin_id'], 'logout', 'Admin logged out');
            
            // Remove session from database
            $stmt = $this->db->prepare("DELETE FROM admin_sessions WHERE admin_id = ? AND session_token = ?");
            $stmt->bind_param("is", $_SESSION['admin_id'], $_SESSION['admin_session_token']);
            $stmt->execute();
        }
        
        // Destroy session
        session_unset();
        session_destroy();
        
        return [
            'success' => true,
            'message' => 'Logout successful'
        ];
    }
    
    public function isLoggedIn() {
        // Check if session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if admin is logged in
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            // Verify session token
            if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_session_token'])) {
                $stmt = $this->db->prepare("SELECT id FROM admin_sessions WHERE admin_id = ? AND session_token = ? AND expires_at > NOW()");
                $stmt->bind_param("is", $_SESSION['admin_id'], $_SESSION['admin_session_token']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
    
    public function hasPermission($requiredRole) {
        // Check if session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['admin_role'])) {
            return false;
        }
        
        $role = $_SESSION['admin_role'];
        
        // Role hierarchy: super_admin > admin > moderator
        if ($role === 'super_admin') {
            return true;
        }
        
        if ($role === 'admin' && ($requiredRole === 'admin' || $requiredRole === 'moderator')) {
            return true;
        }
        
        if ($role === 'moderator' && $requiredRole === 'moderator') {
            return true;
        }
        
        return false;
    }
    
    public function requirePermission($requiredRole) {
        if (!$this->hasPermission($requiredRole)) {
            header('Location: unauthorized.php');
            exit;
        }
    }

    public function getCurrentAdmin() {
        // Check if session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        // Get admin details from database
        $stmt = $this->db->prepare("SELECT id, username, email, full_name, role, status, last_login FROM admins WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['admin_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        
        return null;
    }

    public function getDb() {
        return $this->db;
    }
}
?>
