<?php
session_start();

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require user to be logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Login user
 */
function login($email, $password, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id, nome, senha FROM usuarios WHERE email = ? AND status = 'ativo'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['senha'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nome'];
            $_SESSION['user_email'] = $email;
            
            // Simple session tracking without expires_at column
            try {
                $session_id = session_id();
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                
                $stmt = $pdo->prepare("INSERT INTO sessoes (id, usuario_id, ip_address, user_agent) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE usuario_id = ?, ip_address = ?, user_agent = ?");
                $stmt->execute([$session_id, $user['id'], $ip_address, $user_agent, $user['id'], $ip_address, $user_agent]);
            } catch(PDOException $e) {
                // Continue even if session tracking fails
                error_log("Session tracking error: " . $e->getMessage());
            }
            
            return true;
        }
        return false;
    } catch(PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

/**
 * Register new user
 */
function register($nome, $email, $password, $pdo) {
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email já está em uso.'];
        }
        
        // Create user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $email, $hashed_password]);
        
        return ['success' => true, 'message' => 'Usuário criado com sucesso!'];
    } catch(PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao criar usuário. Tente novamente.'];
    }
}

/**
 * Logout user
 */
function logout($pdo = null) {
    if ($pdo && isset($_SESSION['user_id'])) {
        $session_id = session_id();
        $stmt = $pdo->prepare("DELETE FROM sessoes WHERE id = ?");
        $stmt->execute([$session_id]);
    }
    
    session_destroy();
    header('Location: login.php');
    exit;
}

/**
 * Generate secure token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Clean old sessions (optional function for maintenance)
 */
function cleanOldSessions($pdo) {
    try {
        // Clean sessions older than 30 days based on created_at if column exists
        $stmt = $pdo->prepare("DELETE FROM sessoes WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute();
    } catch(PDOException $e) {
        // Ignore errors if column doesn't exist
        error_log("Session cleanup info: " . $e->getMessage());
    }
}
?>
