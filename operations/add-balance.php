<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

// Get operation ID from URL
$operation_id = intval($_GET['operation_id'] ?? 0);

if ($operation_id <= 0) {
    header('Location: list-operations.php');
    exit;
}

// Get operation details
$stmt = $pdo->prepare("SELECT * FROM operacoes WHERE id = ? AND usuario_id = ? AND status_operacao = 'ativa'");
$stmt->execute([$operation_id, $_SESSION['user_id']]);
$operation = $stmt->fetch();

if (!$operation) {
    header('Location: list-operations.php');
    exit;
}

// Get existing balance additions
$stmt = $pdo->prepare("SELECT * FROM adicoes_saldo WHERE operacao_id = ? ORDER BY created_at DESC");
$stmt->execute([$operation_id]);
$balance_additions = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $valor_adicional = floatval($_POST['valor_adicional_usdt'] ?? 0);
    $data_adicao = sanitizeInput($_POST['data_adicao'] ?? '');
    
    // MEXC data
    $mexc_qtd_moeda = floatval($_POST['mexc_qtd_moeda'] ?? 0);
    $mexc_preco = floatval($_POST['mexc_preco'] ?? 0);
    $mexc_comprado_usdt = floatval($_POST['mexc_comprado_usdt'] ?? 0);
    $mexc_taxa_compra = floatval($_POST['mexc_taxa_compra'] ?? 0);
    $mexc_total_usdt = floatval($_POST['mexc_total_usdt'] ?? 0);
    
    // BTCC data
    $btcc_qtd_moeda = floatval($_POST['btcc_qtd_moeda'] ?? 0);
    $btcc_preco = floatval($_POST['btcc_preco'] ?? 0);
    $btcc_comprado_usdt = floatval($_POST['btcc_comprado_usdt'] ?? 0);
    $btcc_taxa_compra = floatval($_POST['btcc_taxa_compra'] ?? 0);
    $btcc_total_usdt = floatval($_POST['btcc_total_usdt'] ?? 0);
    
    // Validation
    if ($valor_adicional <= 0) {
        $error = 'Valor adicional deve ser maior que zero.';
    } elseif (empty($data_adicao)) {
        $error = 'Data da adição é obrigatória.';
    } elseif ($data_adicao > date('Y-m-d')) {
        $error = 'Data da adição não pode ser futura.';
    } elseif ($mexc_qtd_moeda <= 0 || $mexc_preco <= 0) {
        $error = 'Dados da posição MEXC (Long) são obrigatórios.';
    } elseif ($btcc_qtd_moeda <= 0 || $btcc_preco <= 0) {
        $error = 'Dados da posição BTCC (Short) são obrigatórios.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Insert balance addition record
            $stmt = $pdo->prepare("INSERT INTO adicoes_saldo 
                (operacao_id, usuario_id, valor_adicional_usdt, data_adicao, 
                 mexc_qtd_moeda, mexc_preco, mexc_comprado_usdt, mexc_taxa_compra, mexc_total_usdt,
                 btcc_qtd_moeda, btcc_preco, btcc_comprado_usdt, btcc_taxa_compra, btcc_total_usdt) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $operation_id, $_SESSION['user_id'], $valor_adicional, $data_adicao,
                $mexc_qtd_moeda, $mexc_preco, $mexc_comprado_usdt, $mexc_taxa_compra, $mexc_total_usdt,
                $btcc_qtd_moeda, $btcc_preco, $btcc_comprado_usdt, $btcc_taxa_compra, $btcc_total_usdt
            ]);
            
            // Update main operation with new total
            $stmt = $pdo->prepare("UPDATE operacoes SET valor_inicial_usdt = valor_inicial_usdt + ? WHERE id = ?");
            $stmt->execute([$valor_adicional, $operation_id]);
            
            $pdo->commit();
            $success = 'Saldo adicionado com sucesso à operação!';
            
            // Clear form
            $_POST = [];
        } catch(PDOException $e) {
            $pdo->rollback();
            error_log("Error adding balance: " . $e->getMessage());
            $error = 'Erro ao adicionar saldo. Tente novamente.';
        }
    }
}

$page_title = 'Adicionar Saldo - Arbitragem Cripto';
?>

<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="list-operations.php">Operações</a></li>
                <li class="breadcrumb-item active">Adicionar Saldo</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-plus-circle me-2"></i>
                    Adicionar Saldo à Operação: <?php echo htmlspecialchars($operation['moeda_par']); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Moeda:</strong> <?php echo htmlspecialchars($operation['moeda_par']); ?></p>
                        <p><strong>Valor Atual:</strong> <?php echo formatCurrency($operation['valor_inicial_usdt'], 'USDT'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Data Início:</strong> <?php echo date('d/m/Y', strtotime($operation['data_inicio'])); ?></p>
                        <p><strong>Status:</strong> <span class="badge bg-success">Ativa</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-wallet me-2"></i>
                    Adicionar Novo Saldo
                </h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-1"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <div class="mt-2">
                        <a href="view-operation.php?id=<?php echo $operation_id; ?>" class="btn btn-success btn-sm me-2">
                            <i class="fas fa-eye me-1"></i>Ver Operação
                        </a>
                        <a href="add-balance.php?operation_id=<?php echo $operation_id; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i>Adicionar Mais Saldo
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="valor_adicional_usdt" class="form-label">
                                <i class="fas fa-dollar-sign me-1"></i>
                                Valor Adicional Total (USDT) *
                            </label>
                            <input type="number" class="form-control" id="valor_adicional_usdt" name="valor_adicional_usdt" 
                                   step="0.00000001" min="0" value="<?php echo htmlspecialchars($_POST['valor_adicional_usdt'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="data_adicao" class="form-label">
                                <i class="fas fa-calendar me-1"></i>
                                Data da Adição *
                            </label>
                            <input type="date" class="form-control" id="data_adicao" name="data_adicao" 
                                   max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($_POST['data_adicao'] ?? date('Y-m-d')); ?>" required>
                        </div>
                    </div>

                    <!-- MEXC Long Position -->
                    <div class="card mb-3" style="border-left: 4px solid #28a745;">
                        <div class="card-header bg-success bg-opacity-10">
                            <h6 class="mb-0">
                                <i class="fas fa-arrow-up me-2"></i>MEXC - Posição Long
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="mexc_qtd_moeda" class="form-label">Qtd. Moeda *</label>
                                    <input type="number" class="form-control" id="mexc_qtd_moeda" name="mexc_qtd_moeda" 
                                           step="0.00000001" min="0" value="<?php echo htmlspecialchars($_POST['mexc_qtd_moeda'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="mexc_preco" class="form-label">Preço *</label>
                                    <input type="number" class="form-control" id="mexc_preco" name="mexc_preco" 
                                           step="0.00000001" min="0" value="<?php echo htmlspecialchars($_POST['mexc_preco'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="mexc_comprado_usdt" class="form-label">Comprado USDT *</label>
                                    <input type="number" class="form-control" id="mexc_comprado_usdt" name="mexc_comprado_usdt" 
                                           step="0.00000001" min="0" value="<?php echo htmlspecialchars($_POST['mexc_comprado_usdt'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="mexc_taxa_compra" class="form-label">% Taxa Compra *</label>
                                    <input type="number" class="form-control" id="mexc_taxa_compra" name="mexc_taxa_compra" 
                                           step="0.0001" min="0" max="100" value="<?php echo htmlspecialchars($_POST['mexc_taxa_compra'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="mexc_total_usdt" class="form-label">Total USDT Operação *</label>
                                    <input type="number" class="form-control" id="mexc_total_usdt" name="mexc_total_usdt" 
                                           step="0.00000001" min="0" value="<?php echo htmlspecialchars($_POST['mexc_total_usdt'] ?? ''); ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BTCC Short Position -->
                    <div class="card mb-3" style="border-left: 4px solid #dc3545;">
                        <div class="card-header bg-danger bg-opacity-10">
                            <h6 class="mb-0">
                                <i class="fas fa-arrow-down me-2"></i>BTCC - Posição Short
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="btcc_qtd_moeda" class="form-label">Qtd. Moeda *</label>
                                    <input type="number" class="form-control" id="btcc_qtd_moeda" name="btcc_qtd_moeda" 
                                           step="0.00000001" min="0" value="<?php echo htmlspecialchars($_POST['btcc_qtd_moeda'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="btcc_preco" class="form-label">Preço *</label>
                                    <input type="number" class="form-control" id="btcc_preco" name="btcc_preco" 
                                           step="0.00000001" min="0" value="<?php echo htmlspecialchars($_POST['btcc_preco'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="btcc_comprado_usdt" class="form-label">Comprado USDT *</label>
                                    <input type="number" class="form-control" id="btcc_comprado_usdt" name="btcc_comprado_usdt" 
                                           step="0.00000001" min="0" value="<?php echo htmlspecialchars($_POST['btcc_comprado_usdt'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="btcc_taxa_compra" class="form-label">% Taxa Compra *</label>
                                    <input type="number" class="form-control" id="btcc_taxa_compra" name="btcc_taxa_compra" 
                                           step="0.0001" min="0" max="100" value="<?php echo htmlspecialchars($_POST['btcc_taxa_compra'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="btcc_total_usdt" class="form-label">Total USDT Operação *</label>
                                    <input type="number" class="form-control" id="btcc_total_usdt" name="btcc_total_usdt" 
                                           step="0.00000001" min="0" value="<?php echo htmlspecialchars($_POST['btcc_total_usdt'] ?? ''); ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>



                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus me-1"></i>Adicionar Saldo
                        </button>
                        <a href="view-operation.php?id=<?php echo $operation_id; ?>" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>
                    Histórico de Adições
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($balance_additions)): ?>
                <div class="text-center text-muted py-3">
                    <i class="fas fa-plus-circle fa-2x mb-2"></i>
                    <p class="mb-0">Nenhuma adição registrada</p>
                </div>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($balance_additions as $addition): ?>
                    <div class="timeline-item mb-3">
                        <div class="small text-muted">
                            <?php echo date('d/m/Y', strtotime($addition['data_adicao'])); ?>
                        </div>
                        <div class="fw-bold">
                            +<?php echo formatCurrency($addition['valor_adicional_usdt'], 'USDT'); ?>
                        </div>

                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-calculate MEXC Total USDT
    function calculateMexcTotal() {
        const qtd = parseFloat(document.getElementById('mexc_qtd_moeda').value) || 0;
        const preco = parseFloat(document.getElementById('mexc_preco').value) || 0;
        const result = qtd * preco;
        document.getElementById('mexc_total_usdt').value = result.toFixed(8);
    }
    
    // Auto-calculate BTCC Total USDT  
    function calculateBtccTotal() {
        const qtd = parseFloat(document.getElementById('btcc_qtd_moeda').value) || 0;
        const preco = parseFloat(document.getElementById('btcc_preco').value) || 0;
        const result = qtd * preco;
        document.getElementById('btcc_total_usdt').value = result.toFixed(8);
    }
    
    // Add event listeners
    document.getElementById('mexc_qtd_moeda').addEventListener('input', calculateMexcTotal);
    document.getElementById('mexc_preco').addEventListener('input', calculateMexcTotal);
    document.getElementById('btcc_qtd_moeda').addEventListener('input', calculateBtccTotal);
    document.getElementById('btcc_preco').addEventListener('input', calculateBtccTotal);
});
</script>

<?php include '../includes/footer.php'; ?>