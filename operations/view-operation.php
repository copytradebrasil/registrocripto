<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$operation_id = intval($_GET['id'] ?? 0);

if ($operation_id <= 0) {
    header('Location: list-operations.php');
    exit;
}

// Get operation details
$stmt = $pdo->prepare("SELECT * FROM operacoes WHERE id = ? AND usuario_id = ?");
$stmt->execute([$operation_id, $_SESSION['user_id']]);
$operation = $stmt->fetch();

if (!$operation) {
    header('Location: list-operations.php');
    exit;
}

// Get profit records
$stmt = $pdo->prepare("SELECT * FROM registros_lucro WHERE operacao_id = ? ORDER BY data_registro DESC");
$stmt->execute([$operation_id]);
$profit_records = $stmt->fetchAll();

// Get balance additions for this operation
$stmt = $pdo->prepare("SELECT * FROM adicoes_saldo WHERE operacao_id = ? ORDER BY data_adicao DESC");
$stmt->execute([$operation_id]);
$balance_additions = $stmt->fetchAll();

// Calculate totals
$total_intermediate_profits = array_sum(array_column($profit_records, 'valor_lucro'));
$current_profit = $operation['status_operacao'] === 'finalizada' ? $operation['lucro_total'] : $total_intermediate_profits;
$roi = calculateROI($operation['valor_inicial_usdt'], $operation['valor_inicial_usdt'] + $current_profit);

$duration = $operation['status_operacao'] === 'finalizada' && $operation['data_fim'] 
           ? daysBetween($operation['data_inicio'], $operation['data_fim'])
           : daysBetween($operation['data_inicio'], date('Y-m-d'));

$page_title = 'Detalhes da Operação - Arbitragem Cripto';
?>

<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="list-operations.php">Operações</a></li>
                <li class="breadcrumb-item active">Detalhes</li>
            </ol>
        </nav>
    </div>
</div>





<div class="row">


    <!-- Profit Records -->
    <div class="col-12 mb-4">
        <div class="tech-dashboard-card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Histórico de Lucros
                    </h5>
                    <?php if ($operation['status_operacao'] === 'ativa'): ?>
                    <div class="btn-group" role="group">
                        <a href="add-profit.php?operation_id=<?php echo $operation['id']; ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-plus me-1"></i>Lucro
                        </a>
                        <a href="add-balance.php?operation_id=<?php echo $operation['id']; ?>" class="btn btn-sm btn-info">
                            <i class="fas fa-wallet me-1"></i>Saldo
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($profit_records)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-chart-line fa-3x mb-3"></i>
                    <p>Nenhum lucro registrado ainda.</p>
                    <?php if ($operation['status_operacao'] === 'ativa'): ?>
                    <a href="add-profit.php?operation_id=<?php echo $operation['id']; ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-plus me-1"></i>Registrar Primeiro Lucro
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>

                <!-- Compact Table for All Devices -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th class="text-nowrap">Data</th>
                                <th class="text-nowrap">Hora</th>
                                <th class="text-nowrap">Valor</th>
                                <th class="text-nowrap">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($profit_records as $profit): ?>
                            <tr>
                                <td class="text-nowrap">
                                    <span class="text-light small">
                                        <?php echo date('d/m/y', strtotime($profit['data_registro'])); ?>
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <span class="text-muted small">
                                        <?php 
                                        // Extract hour from data_registro field which contains date and time
                                        $hora_registro = date('H:i', strtotime($profit['data_registro']));
                                        echo $hora_registro;
                                        ?>
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <span class="fw-bold text-success small">
                                        +<?php echo formatCurrency($profit['valor_lucro'], 'USDT'); ?>
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <span class="fw-bold text-warning small">
                                        <?php 
                                        // Calculate percentage of profit relative to initial investment
                                        $percentage = ($profit['valor_lucro'] / $operation['valor_inicial_usdt']) * 100;
                                        echo sprintf('%.2f%%', $percentage);
                                        ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 p-3 bg-dark border border-success rounded">
                    <div class="d-flex justify-content-between">
                        <strong class="text-light">Total de Lucros Registrados:</strong>
                        <span class="text-success fw-bold">
                            <?php echo formatCurrency($total_intermediate_profits, 'USDT'); ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Balance Additions Card -->
    <div class="col-12 mb-4">
        <div class="tech-dashboard-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-wallet me-2"></i>
                    Histórico de Adições de Saldo
                </h5>
                <?php if ($operation['status_operacao'] === 'ativa'): ?>
                <a href="add-balance.php?operation_id=<?php echo $operation['id']; ?>" class="btn btn-sm btn-info">
                    <i class="fas fa-plus me-1"></i>Adicionar Saldo
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($balance_additions)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-wallet fa-3x mb-3"></i>
                    <h6>Nenhuma adição de saldo registrada</h6>
                    <p class="mb-0">Use o botão acima para adicionar saldo a esta operação</p>
                </div>
                <?php else: ?>
                
                <!-- Compact Table for All Devices -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th class="text-nowrap">Data</th>
                                <th class="text-nowrap">Valor</th>
                                <th class="d-none d-md-table-cell">MEXC (Long)</th>
                                <th class="d-none d-md-table-cell">BTCC (Short)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($balance_additions as $addition): ?>
                            <tr>
                                <td class="text-nowrap">
                                    <span class="text-light small">
                                        <?php echo date('d/m/y', strtotime($addition['data_adicao'])); ?>
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <span class="fw-bold text-info small">
                                        +<?php echo formatCurrency($addition['valor_adicional_usdt'], 'USDT'); ?>
                                    </span>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <div class="small text-light">
                                        <strong>Qtd:</strong> <?php echo number_format($addition['mexc_qtd_moeda'], 6); ?><br>
                                        <strong>Preço:</strong> <?php echo formatCurrency($addition['mexc_preco'], 'USDT'); ?>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <div class="small text-light">
                                        <strong>Qtd:</strong> <?php echo number_format($addition['btcc_qtd_moeda'], 6); ?><br>
                                        <strong>Preço:</strong> <?php echo formatCurrency($addition['btcc_preco'], 'USDT'); ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
