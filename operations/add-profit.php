<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

// Get user's active operations
$stmt = $pdo->prepare("SELECT id, moeda_par, valor_inicial_usdt, data_inicio FROM operacoes WHERE usuario_id = ? AND status_operacao = 'ativa' ORDER BY data_inicio DESC");
$stmt->execute([$_SESSION['user_id']]);
$active_operations = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operacao_id = intval($_POST['operacao_id'] ?? 0);
    $valor_lucro = floatval($_POST['valor_lucro'] ?? 0);
    $data_registro = sanitizeInput($_POST['data_registro'] ?? '');
    $hora_registro = sanitizeInput($_POST['hora_registro'] ?? '');
    $tipo_lucro = sanitizeInput($_POST['tipo_lucro'] ?? '');
    
    // Combine date and time
    $data_hora_registro = $data_registro . ' ' . $hora_registro . ':00';
    
    // Validation
    if ($operacao_id <= 0) {
        $error = 'Selecione uma operação válida.';
    } elseif ($valor_lucro <= 0) {
        $error = 'Valor do lucro deve ser maior que zero.';
    } elseif (empty($data_registro)) {
        $error = 'Data do registro é obrigatória.';
    } elseif (empty($hora_registro)) {
        $error = 'Horário do registro é obrigatório.';
    } elseif ($data_hora_registro > date('Y-m-d H:i:s')) {
        $error = 'Data e horário do registro não podem ser futuros.';
    } elseif (empty($tipo_lucro)) {
        $error = 'Tipo de lucro é obrigatório.';
    } else {
        // Verify operation belongs to user and is active
        $stmt = $pdo->prepare("SELECT id FROM operacoes WHERE id = ? AND usuario_id = ? AND status_operacao = 'ativa'");
        $stmt->execute([$operacao_id, $_SESSION['user_id']]);
        
        if (!$stmt->fetch()) {
            $error = 'Operação não encontrada ou não está ativa.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO registros_lucro (usuario_id, operacao_id, valor_lucro, data_registro, tipo_lucro) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $operacao_id, $valor_lucro, $data_hora_registro, $tipo_lucro]);
                
                $success = 'Lucro registrado com sucesso!';
                
                // Clear form
                $_POST = [];
            } catch(PDOException $e) {
                error_log("Error adding profit: " . $e->getMessage());
                $error = 'Erro ao registrar lucro. Tente novamente.';
            }
        }
    }
}

$page_title = 'Registrar Lucro - Arbitragem Cripto';
?>

<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Registrar Lucro</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Registrar Lucro de Operação
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($active_operations)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5>Nenhuma operação ativa encontrada</h5>
                    <p class="text-muted">Você precisa ter operações ativas para registrar lucros.</p>
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
                    <?php echo htmlspecialchars($success); ?>
                    <div class="mt-2">
                        <a href="../index.php" class="btn btn-success btn-sm me-2">
                            <i class="fas fa-tachometer-alt me-1"></i>Voltar ao Dashboard
                        </a>
                        <a href="add-profit.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i>Registrar Outro Lucro
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="operacao_id" class="form-label">
                            <i class="fas fa-coins me-1"></i>
                            Operação *
                        </label>
                        <select class="form-select" id="operacao_id" name="operacao_id" required>
                            <option value="">Selecione uma operação ativa</option>
                            <?php foreach ($active_operations as $op): ?>
                            <option value="<?php echo $op['id']; ?>" <?php echo ($_POST['operacao_id'] ?? '') == $op['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($op['moeda_par']); ?> - 
                                <?php echo formatCurrency($op['valor_inicial_usdt'], 'USDT'); ?> - 
                                <?php echo date('d/m/Y', strtotime($op['data_inicio'])); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="valor_lucro" class="form-label">
                                <i class="fas fa-dollar-sign me-1"></i>
                                Valor do Lucro (USDT) *
                            </label>
                            <input type="number" class="form-control" id="valor_lucro" name="valor_lucro" 
                                   step="0.00000001" min="0" value="<?php echo htmlspecialchars($_POST['valor_lucro'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="data_registro" class="form-label">
                                <i class="fas fa-calendar me-1"></i>
                                Data e Horário do Registro *
                            </label>
                            <div class="row">
                                <div class="col-7">
                                    <input type="date" class="form-control" id="data_registro" name="data_registro" 
                                           max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($_POST['data_registro'] ?? date('Y-m-d')); ?>" required>
                                </div>
                                <div class="col-5">
                                    <select class="form-select" id="hora_registro" name="hora_registro" required>
                                        <option value="">Hora</option>
                                        <option value="06:00">06:00</option>
                                        <option value="14:00">14:00</option>
                                        <option value="22:00">22:00</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="tipo_lucro" class="form-label">
                            <i class="fas fa-tags me-1"></i>
                            Tipo de Lucro *
                        </label>
                        <select class="form-select" id="tipo_lucro" name="tipo_lucro" required>
                            <option value="">Selecione o tipo</option>
                            <option value="diário" <?php echo ($_POST['tipo_lucro'] ?? '') == 'diário' ? 'selected' : ''; ?>>Diário</option>
                            <option value="semanal" <?php echo ($_POST['tipo_lucro'] ?? '') == 'semanal' ? 'selected' : ''; ?>>Semanal</option>
                            <option value="mensal" <?php echo ($_POST['tipo_lucro'] ?? '') == 'mensal' ? 'selected' : ''; ?>>Mensal</option>
                            <option value="outro" <?php echo ($_POST['tipo_lucro'] ?? '') == 'outro' ? 'selected' : ''; ?>>Outro</option>
                        </select>
                    </div>



                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i>Registrar Lucro
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

<?php include '../includes/footer.php'; ?>
