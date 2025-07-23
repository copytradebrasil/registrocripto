<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

// Get user's active operations
$stmt = $pdo->prepare("SELECT o.*, 
                      COALESCE(SUM(rl.valor_lucro), 0) as lucros_intermediarios
                      FROM operacoes o 
                      LEFT JOIN registros_lucro rl ON o.id = rl.operacao_id
                      WHERE o.usuario_id = ? AND o.status_operacao = 'ativa' 
                      GROUP BY o.id 
                      ORDER BY o.data_inicio DESC");
$stmt->execute([$_SESSION['user_id']]);
$active_operations = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operacao_id = intval($_POST['operacao_id'] ?? 0);
    $valor_final_usdt = floatval($_POST['valor_final_usdt'] ?? 0);
    $data_fechamento = sanitizeInput($_POST['data_fechamento'] ?? '');
    
    // Validation
    if ($operacao_id <= 0) {
        $error = 'Selecione uma operação válida.';
    } elseif ($valor_final_usdt <= 0) {
        $error = 'Valor final deve ser maior que zero.';
    } elseif (empty($data_fechamento)) {
        $error = 'Data de fechamento é obrigatória.';
    } elseif ($data_fechamento > date('Y-m-d')) {
        $error = 'Data de fechamento não pode ser futura.';
    } else {
        // Get operation details and verify ownership
        $stmt = $pdo->prepare("SELECT o.*, COALESCE(SUM(rl.valor_lucro), 0) as lucros_intermediarios
                              FROM operacoes o 
                              LEFT JOIN registros_lucro rl ON o.id = rl.operacao_id
                              WHERE o.id = ? AND o.usuario_id = ? AND o.status_operacao = 'ativa'
                              GROUP BY o.id");
        $stmt->execute([$operacao_id, $_SESSION['user_id']]);
        $operation = $stmt->fetch();
        
        if (!$operation) {
            $error = 'Operação não encontrada ou não está ativa.';
        } elseif ($data_fechamento < $operation['data_inicio']) {
            $error = 'Data de fechamento não pode ser anterior à data de início.';
        } else {
            try {
                // Calculate total profit (final value - initial value + intermediate profits)
                $lucro_final = $valor_final_usdt - $operation['valor_inicial_usdt'];
                $lucro_total = $lucro_final + $operation['lucros_intermediarios'];
                
                $stmt = $pdo->prepare("UPDATE operacoes SET 
                                      status_operacao = 'finalizada', 
                                      valor_final_usdt = ?, 
                                      data_fim = ?, 
                                      lucro_total = ? 
                                      WHERE id = ?");
                $stmt->execute([$valor_final_usdt, $data_fechamento, $lucro_total, $operacao_id]);
                
                $success = sprintf(
                    'Operação fechada com sucesso! Lucro total: %s (Lucro final: %s + Lucros intermediários: %s)',
                    formatCurrency($lucro_total, 'USDT'),
                    formatCurrency($lucro_final, 'USDT'),
                    formatCurrency($operation['lucros_intermediarios'], 'USDT')
                );
                
                // Refresh active operations list
                $stmt = $pdo->prepare("SELECT o.*, 
                                      COALESCE(SUM(rl.valor_lucro), 0) as lucros_intermediarios
                                      FROM operacoes o 
                                      LEFT JOIN registros_lucro rl ON o.id = rl.operacao_id
                                      WHERE o.usuario_id = ? AND o.status_operacao = 'ativa' 
                                      GROUP BY o.id 
                                      ORDER BY o.data_inicio DESC");
                $stmt->execute([$_SESSION['user_id']]);
                $active_operations = $stmt->fetchAll();
                
                // Clear form
                $_POST = [];
            } catch(PDOException $e) {
                error_log("Error closing operation: " . $e->getMessage());
                $error = 'Erro ao fechar operação. Tente novamente.';
            }
        }
    }
}

$page_title = 'Fechar Operação - Arbitragem Cripto';
?>

<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Fechar Operação</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-flag-checkered me-2"></i>
                    Fechar Operação de Arbitragem
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($active_operations)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5>Nenhuma operação ativa encontrada</h5>
                    <p class="text-muted">Você não tem operações ativas para fechar.</p>
                    <a href="new-operation.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Criar Nova Operação
                    </a>
                </div>
                <?php else: ?>

                <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-1"></i>
                    <?php echo $success; ?>
                    <div class="mt-2">
                        <a href="../index.php" class="btn btn-success btn-sm me-2">
                            <i class="fas fa-tachometer-alt me-1"></i>Voltar ao Dashboard
                        </a>
                        <a href="list-operations.php" class="btn btn-info btn-sm text-white">
                            <i class="fas fa-list me-1"></i>Ver Todas Operações
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" action="" id="closeOperationForm">
                    <div class="mb-3">
                        <label for="operacao_id" class="form-label">
                            <i class="fas fa-coins me-1"></i>
                            Operação a Fechar *
                        </label>
                        <select class="form-select" id="operacao_id" name="operacao_id" required onchange="updateOperationDetails()">
                            <option value="">Selecione uma operação ativa</option>
                            <?php foreach ($active_operations as $op): ?>
                            <option value="<?php echo $op['id']; ?>" 
                                    data-inicial="<?php echo $op['valor_inicial_usdt']; ?>"
                                    data-inicio="<?php echo $op['data_inicio']; ?>"
                                    data-lucros="<?php echo $op['lucros_intermediarios']; ?>"
                                    <?php echo ($_POST['operacao_id'] ?? '') == $op['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($op['moeda_par']); ?> - 
                                <?php echo formatCurrency($op['valor_inicial_usdt'], 'USDT'); ?> - 
                                <?php echo date('d/m/Y', strtotime($op['data_inicio'])); ?>
                                (Lucros: <?php echo formatCurrency($op['lucros_intermediarios'], 'USDT'); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="operationDetails" class="mb-3" style="display: none;">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Detalhes da Operação</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Valor Inicial:</strong>
                                        <span id="detailInicial">-</span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Data Início:</strong>
                                        <span id="detailInicio">-</span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Lucros Registrados:</strong>
                                        <span id="detailLucros" class="text-success">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="valor_final_usdt" class="form-label">
                                <i class="fas fa-dollar-sign me-1"></i>
                                Valor Final (USDT) *
                            </label>
                            <input type="number" class="form-control" id="valor_final_usdt" name="valor_final_usdt" 
                                   step="0.00000001" min="0" value="<?php echo htmlspecialchars($_POST['valor_final_usdt'] ?? ''); ?>" 
                                   required onchange="calculateProfit()">
                        </div>
                        <div class="col-md-6">
                            <label for="data_fechamento" class="form-label">
                                <i class="fas fa-calendar me-1"></i>
                                Data de Fechamento *
                            </label>
                            <input type="date" class="form-control" id="data_fechamento" name="data_fechamento" 
                                   max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($_POST['data_fechamento'] ?? date('Y-m-d')); ?>" required>
                        </div>
                    </div>

                    <div id="profitPreview" class="mb-3" style="display: none;">
                        <div class="card border-success">
                            <div class="card-body">
                                <h6 class="card-title text-success">
                                    <i class="fas fa-calculator me-1"></i>
                                    Previsão de Lucro
                                </h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Lucro Final:</strong>
                                        <span id="previewLucroFinal">-</span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Lucros Intermediários:</strong>
                                        <span id="previewLucrosInter" class="text-success">-</span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Lucro Total:</strong>
                                        <span id="previewLucroTotal" class="text-success fw-bold">-</span>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <strong>ROI:</strong>
                                    <span id="previewROI" class="badge bg-info">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja fechar esta operação? Esta ação não pode ser desfeita.')">
                            <i class="fas fa-flag-checkered me-1"></i>Fechar Operação
                        </button>
                        <a href="../index.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </a>
                    </div>
                </form>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function updateOperationDetails() {
    const select = document.getElementById('operacao_id');
    const detailsDiv = document.getElementById('operationDetails');
    
    if (select.value) {
        const option = select.options[select.selectedIndex];
        const inicial = parseFloat(option.dataset.inicial);
        const inicio = option.dataset.inicio;
        const lucros = parseFloat(option.dataset.lucros);
        
        document.getElementById('detailInicial').textContent = formatCurrency(inicial);
        document.getElementById('detailInicio').textContent = formatDate(inicio);
        document.getElementById('detailLucros').textContent = formatCurrency(lucros);
        
        detailsDiv.style.display = 'block';
        calculateProfit();
    } else {
        detailsDiv.style.display = 'none';
        document.getElementById('profitPreview').style.display = 'none';
    }
}

function calculateProfit() {
    const select = document.getElementById('operacao_id');
    const valorFinal = parseFloat(document.getElementById('valor_final_usdt').value) || 0;
    const previewDiv = document.getElementById('profitPreview');
    
    if (select.value && valorFinal > 0) {
        const option = select.options[select.selectedIndex];
        const inicial = parseFloat(option.dataset.inicial);
        const lucrosInter = parseFloat(option.dataset.lucros);
        
        const lucroFinal = valorFinal - inicial;
        const lucroTotal = lucroFinal + lucrosInter;
        const roi = inicial > 0 ? ((lucroTotal / inicial) * 100) : 0;
        
        document.getElementById('previewLucroFinal').textContent = formatCurrency(lucroFinal);
        document.getElementById('previewLucrosInter').textContent = formatCurrency(lucrosInter);
        document.getElementById('previewLucroTotal').textContent = formatCurrency(lucroTotal);
        document.getElementById('previewROI').textContent = roi.toFixed(2) + '%';
        
        previewDiv.style.display = 'block';
    } else {
        previewDiv.style.display = 'none';
    }
}

function formatCurrency(amount) {
    return '$' + amount.toFixed(8).replace(/\.?0+$/, '');
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-BR');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateOperationDetails();
});
</script>

<?php include '../includes/footer.php'; ?>
