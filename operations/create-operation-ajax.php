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
    // Capture all form data
    $inicial_usdt = floatval($_POST['inicial_usdt'] ?? 0);
    $data_compra = sanitizeInput($_POST['data_compra'] ?? '');
    $moeda = strtoupper(sanitizeInput($_POST['moeda'] ?? ''));

    
    // MEXC (Long) data
    $mexc_qtd_moeda = floatval($_POST['mexc_qtd_moeda'] ?? 0);
    $mexc_preco = floatval($_POST['mexc_preco'] ?? 0);
    $mexc_comprado_usdt = floatval($_POST['mexc_comprado_usdt'] ?? 0);
    $mexc_taxa_compra = floatval($_POST['mexc_taxa_compra'] ?? 0);
    $mexc_total_usdt = floatval($_POST['mexc_total_usdt'] ?? 0);
    
    // BTCC (Short) data
    $btcc_qtd_moeda = floatval($_POST['btcc_qtd_moeda'] ?? 0);
    $btcc_preco = floatval($_POST['btcc_preco'] ?? 0);
    $btcc_comprado_usdt = floatval($_POST['btcc_comprado_usdt'] ?? 0);
    $btcc_taxa_compra = floatval($_POST['btcc_taxa_compra'] ?? 0);
    $btcc_total_usdt = floatval($_POST['btcc_total_usdt'] ?? 0);
    
    // Validation
    if ($inicial_usdt <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valor inicial em USDT deve ser maior que zero.']);
        exit;
    }
    
    if (empty($data_compra)) {
        echo json_encode(['success' => false, 'message' => 'Data de compra é obrigatória.']);
        exit;
    }
    
    if ($data_compra > date('Y-m-d')) {
        echo json_encode(['success' => false, 'message' => 'Data de compra não pode ser futura.']);
        exit;
    }
    
    if (empty($moeda)) {
        echo json_encode(['success' => false, 'message' => 'Moeda é obrigatória.']);
        exit;
    }
    

    
    // Validate MEXC data
    if ($mexc_qtd_moeda <= 0 || $mexc_preco <= 0 || $mexc_comprado_usdt <= 0 || $mexc_taxa_compra < 0 || $mexc_total_usdt <= 0) {
        echo json_encode(['success' => false, 'message' => 'Todos os campos da posição MEXC (Long) são obrigatórios.']);
        exit;
    }
    
    // Validate BTCC data
    if ($btcc_qtd_moeda <= 0 || $btcc_preco <= 0 || $btcc_comprado_usdt <= 0 || $btcc_taxa_compra < 0 || $btcc_total_usdt <= 0) {
        echo json_encode(['success' => false, 'message' => 'Todos os campos da posição BTCC (Short) são obrigatórios.']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO operacoes (usuario_id, valor_inicial_usdt, data_inicio, moeda_par, status_operacao) VALUES (?, ?, ?, ?, 'ativa')");
        $stmt->execute([$_SESSION['user_id'], $inicial_usdt, $data_compra, $moeda]);
        
        echo json_encode(['success' => true, 'message' => 'Operação de arbitragem criada com sucesso!']);
    } catch(PDOException $e) {
        error_log("Error creating operation: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao criar operação. Tente novamente.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
}
?>