<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operacao_id = intval($_POST['operacao_id'] ?? 0);
    $valor_lucro = floatval($_POST['valor_lucro'] ?? 0);
    $tipo_lucro = sanitizeInput($_POST['tipo_lucro'] ?? '');
    $data_registro = sanitizeInput($_POST['data_registro'] ?? '');
    $hora_registro = sanitizeInput($_POST['hora_registro'] ?? '');
    
    // Combine date and time
    $data_hora_registro = $data_registro . ' ' . $hora_registro . ':00';
    
    // Validation
    if ($operacao_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Operação deve ser selecionada.']);
        exit;
    }
    
    if ($valor_lucro <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valor do lucro deve ser maior que zero.']);
        exit;
    }
    
    if (empty($tipo_lucro)) {
        echo json_encode(['success' => false, 'message' => 'Tipo de lucro é obrigatório.']);
        exit;
    }
    
    if (empty($data_registro)) {
        echo json_encode(['success' => false, 'message' => 'Data de registro é obrigatória.']);
        exit;
    }
    
    if (empty($hora_registro)) {
        echo json_encode(['success' => false, 'message' => 'Horário de registro é obrigatório.']);
        exit;
    }
    
    if ($data_hora_registro > date('Y-m-d H:i:s')) {
        echo json_encode(['success' => false, 'message' => 'Data e horário de registro não podem ser futuros.']);
        exit;
    }
    
    try {
        // Verify that the operation belongs to the user and is active
        $stmt = $pdo->prepare("SELECT id FROM operacoes WHERE id = ? AND usuario_id = ? AND status_operacao = 'ativa'");
        $stmt->execute([$operacao_id, $_SESSION['user_id']]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Operação não encontrada ou não está ativa.']);
            exit;
        }
        
        // Insert profit record
        $stmt = $pdo->prepare("INSERT INTO registros_lucro (usuario_id, operacao_id, valor_lucro, tipo_lucro, data_registro, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], $operacao_id, $valor_lucro, $tipo_lucro, $data_hora_registro]);
        
        echo json_encode(['success' => true, 'message' => 'Lucro registrado com sucesso!']);
    } catch(PDOException $e) {
        error_log("Error creating profit record: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao registrar lucro. Tente novamente.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
}
?>