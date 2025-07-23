<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, moeda_par, valor_inicial_usdt, data_inicio FROM operacoes WHERE usuario_id = ? AND status_operacao = 'ativa' ORDER BY data_inicio DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $operations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data for display
    foreach ($operations as &$op) {
        $op['data_inicio'] = date('d/m/Y', strtotime($op['data_inicio']));
        $op['valor_inicial_usdt'] = number_format($op['valor_inicial_usdt'], 8);
    }
    
    echo json_encode(['success' => true, 'operations' => $operations]);
} catch(PDOException $e) {
    error_log("Error fetching active operations: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar operações.']);
}
?>