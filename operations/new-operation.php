<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inicial_brl = floatval($_POST['inicial_brl'] ?? 0);
    $inicial_usdt = floatval($_POST['inicial_usdt'] ?? 0);
    $data_inicio = sanitizeInput($_POST['data_inicio'] ?? '');
    $moeda = strtoupper(sanitizeInput($_POST['moeda'] ?? ''));

    
    // Validation
    if ($inicial_brl <= 0) {
        $error = 'Valor inicial em BRL deve ser maior que zero.';
    } elseif ($inicial_usdt <= 0) {
        $error = 'Valor inicial em USDT deve ser maior que zero.';
    } elseif (empty($data_inicio)) {
        $error = 'Data de início é obrigatória.';
    } elseif ($data_inicio > date('Y-m-d')) {
        $error = 'Data de início não pode ser futura.';
    } elseif (empty($moeda)) {
        $error = 'Par de criptomoeda é obrigatório.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO operacoes (usuario_id, valor_inicial_brl, valor_inicial_usdt, data_inicio, moeda_par, status_operacao) VALUES (?, ?, ?, ?, ?, 'ativa')");
            $stmt->execute([$_SESSION['user_id'], $inicial_brl, $inicial_usdt, $data_inicio, $moeda]);
            
            $success = 'Operação criada com sucesso!';
            
            // Clear form
            $_POST = [];
        } catch(PDOException $e) {
            error_log("Error creating operation: " . $e->getMessage());
            $error = 'Erro ao criar operação. Tente novamente.';
        }
    }
}

$page_title = 'Nova Operação - Arbitragem Cripto';
?>

<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Nova Operação</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-plus me-2"></i>
                    Nova Operação de Arbitragem
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
                        <a href="../index.php" class="btn btn-success btn-sm me-2">
                            <i class="fas fa-tachometer-alt me-1"></i>Voltar ao Dashboard
                        </a>
                        <a href="new-operation.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i>Nova Operação
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="inicial_brl" class="form-label">
                                <i class="fas fa-dollar-sign me-1"></i>
                                Valor Inicial (BRL) *
                            </label>
                            <input type="number" class="form-control" id="inicial_brl" name="inicial_brl" 
                                   step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['inicial_brl'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="inicial_usdt" class="form-label">
                                <i class="fas fa-coins me-1"></i>
                                Valor Inicial (USDT) *
                            </label>
                            <input type="number" class="form-control" id="inicial_usdt" name="inicial_usdt" 
                                   step="0.00000001" min="0" value="<?php echo htmlspecialchars($_POST['inicial_usdt'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="data_inicio" class="form-label">
                                <i class="fas fa-calendar me-1"></i>
                                Data de Início *
                            </label>
                            <input type="date" class="form-control" id="data_inicio" name="data_inicio" 
                                   max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($_POST['data_inicio'] ?? date('Y-m-d')); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="moeda" class="form-label">
                                <i class="fas fa-coins me-1"></i>
                                Par de Criptomoeda *
                            </label>
                            <input type="text" class="form-control" id="moeda" name="moeda" 
                                   placeholder="Ex: BTCUSDT, ETHUSDT, SUIUSDT" value="<?php echo htmlspecialchars($_POST['moeda'] ?? ''); ?>" required>
                            <div class="form-text">Digite o par da criptomoeda (ex: BTCUSDT)</div>
                        </div>
                    </div>



                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Criar Operação
                        </button>
                        <a href="../index.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-format cryptocurrency pair
    const moedaInput = document.getElementById('moeda');
    moedaInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase().replace(/[^A-Z]/g, '');
    });
});
</script>

<?php include '../includes/footer.php'; ?>
