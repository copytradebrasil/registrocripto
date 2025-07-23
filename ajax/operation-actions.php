<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'get_operation_details':
        $operation_id = intval($_POST['operation_id'] ?? 0);
        
        if ($operation_id <= 0) {
            echo json_encode(['error' => 'ID da operação inválido']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT o.*, 
                                  COALESCE(SUM(rl.valor_lucro), 0) as lucros_intermediarios
                                  FROM operacoes o 
                                  LEFT JOIN registros_lucro rl ON o.id = rl.operacao_id
                                  WHERE o.id = ? AND o.usuario_id = ?
                                  GROUP BY o.id");
            $stmt->execute([$operation_id, $_SESSION['user_id']]);
            $operation = $stmt->fetch();
            
            if (!$operation) {
                echo json_encode(['error' => 'Operação não encontrada']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'operation' => $operation
            ]);
        } catch(PDOException $e) {
            error_log("Error getting operation details: " . $e->getMessage());
            echo json_encode(['error' => 'Erro interno do servidor']);
        }
        break;
        
    case 'delete_profit_record':
        $profit_id = intval($_POST['profit_id'] ?? 0);
        
        if ($profit_id <= 0) {
            echo json_encode(['error' => 'ID do lucro inválido']);
            exit;
        }
        
        try {
            // Verify ownership
            $stmt = $pdo->prepare("SELECT rl.id FROM registros_lucro rl 
                                  INNER JOIN operacoes o ON rl.operacao_id = o.id 
                                  WHERE rl.id = ? AND o.usuario_id = ?");
            $stmt->execute([$profit_id, $_SESSION['user_id']]);
            
            if (!$stmt->fetch()) {
                echo json_encode(['error' => 'Registro não encontrado']);
                exit;
            }
            
            // Delete profit record
            $stmt = $pdo->prepare("DELETE FROM registros_lucro WHERE id = ?");
            $stmt->execute([$profit_id]);
            
            echo json_encode(['success' => true]);
        } catch(PDOException $e) {
            error_log("Error deleting profit record: " . $e->getMessage());
            echo json_encode(['error' => 'Erro interno do servidor']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Ação inválida']);
        break;
}
?>
