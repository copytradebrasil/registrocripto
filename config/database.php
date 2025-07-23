<?php
// Database configuration with environment variable support
$host = $_ENV['DB_HOST'] ?? 'srv1887.hstgr.io';
$username = $_ENV['DB_USER'] ?? 'u999216088_registrocripto';
$password = $_ENV['DB_PASS'] ?? 'Copytrade@2025';
$database = $_ENV['DB_NAME'] ?? 'u999216088_registrocripto';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    
    // For deployment environments, provide more graceful fallback
    if (isset($_ENV['REPL_SLUG'])) {
        die("Sistema temporariamente indisponível. Configuração de banco pendente.");
    } else {
        die("Erro na conexão com o banco de dados. Tente novamente mais tarde.");
    }
}
?>
