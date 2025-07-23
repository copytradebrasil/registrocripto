<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

// Filters
$status_filter = $_GET['status'] ?? 'all';
$moeda_filter = $_GET['moeda'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = ['o.usuario_id = ?'];
$params = [$_SESSION['user_id']];

if ($status_filter !== 'all') {
    $where_conditions[] = 'o.status_operacao = ?';
    $params[] = $status_filter;
}

if (!empty($moeda_filter)) {
    $where_conditions[] = 'o.moeda_par LIKE ?';
    $params[] = '%' . $moeda_filter . '%';
}

if (!empty($date_from)) {
    $where_conditions[] = 'o.data_inicio >= ?';
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = 'o.data_inicio <= ?';
    $params[] = $date_to;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM operacoes o $where_clause";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetch()['total'];
$total_pages = ceil($total_records / $per_page);

// Get operations with profit data
$query = "SELECT o.*, 
          COALESCE(SUM(rl.valor_lucro), 0) as lucros_intermediarios,
          CASE 
            WHEN o.status_operacao = 'finalizada' THEN o.lucro_total
            ELSE COALESCE(SUM(rl.valor_lucro), 0)
          END as lucro_atual
          FROM operacoes o 
          LEFT JOIN registros_lucro rl ON o.id = rl.operacao_id
          $where_clause
          GROUP BY o.id 
          ORDER BY o.created_at DESC 
          LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$operations = $stmt->fetchAll();

// Get unique currencies for filter
$stmt = $pdo->prepare("SELECT DISTINCT moeda_par FROM operacoes WHERE usuario_id = ? ORDER BY moeda_par");
$stmt->execute([$_SESSION['user_id']]);
$currencies = $stmt->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Operações - Arbitragem Cripto';
?>

<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Operações</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Todas as Operações
                    </h5>
                </div>
            </div>
            <div class="card-body">
                <!-- Mobile Filter Toggle Button -->
                <div class="d-md-none mb-3">
                    <button class="btn btn-tech-dark btn-sm w-100" type="button" data-bs-toggle="collapse" data-bs-target="#mobileFilters" aria-expanded="false" aria-controls="mobileFilters">
                        <i class="fas fa-filter me-2"></i>
                        Filtros
                        <i class="fas fa-chevron-down ms-auto"></i>
                    </button>
                </div>
                
                <!-- Filters -->
                <form method="GET" class="mb-4">
                    <div class="collapse d-md-block" id="mobileFilters">
                        <div class="row g-3">
                            <div class="col-md-2 col-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select tech-input" id="status" name="status">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Todos</option>
                                    <option value="ativa" <?php echo $status_filter === 'ativa' ? 'selected' : ''; ?>>Ativa</option>
                                    <option value="finalizada" <?php echo $status_filter === 'finalizada' ? 'selected' : ''; ?>>Finalizada</option>
                                </select>
                            </div>
                            <div class="col-md-2 col-6">
                                <label for="moeda" class="form-label">Moeda</label>
                                <select class="form-select tech-input" id="moeda" name="moeda">
                                    <option value="">Todas</option>
                                    <?php foreach ($currencies as $currency): ?>
                                    <option value="<?php echo htmlspecialchars($currency); ?>" 
                                            <?php echo $moeda_filter === $currency ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($currency); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 col-6">
                                <label for="date_from" class="form-label">Data De</label>
                                <input type="date" class="form-control tech-input" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div class="col-md-2 col-6">
                                <label for="date_to" class="form-label">Data Até</label>
                                <input type="date" class="form-control tech-input" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            <div class="col-md-4 col-12">
                                <label class="form-label d-none d-md-block">&nbsp;</label>
                                <div class="d-flex gap-2 mt-2 mt-md-0">
                                    <button type="submit" class="btn btn-tech flex-fill">
                                        <i class="fas fa-filter me-1"></i>Filtrar
                                    </button>
                                    <a href="list-operations.php" class="btn btn-tech-dark flex-fill">
                                        <i class="fas fa-times me-1"></i>Limpar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <?php if (empty($operations)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5>Nenhuma operação encontrada</h5>
                    <p class="text-muted">
                        <?php if ($status_filter !== 'all' || !empty($moeda_filter) || !empty($date_from) || !empty($date_to)): ?>
                        Nenhuma operação encontrada com os filtros aplicados.
                        <?php else: ?>
                        Você ainda não criou nenhuma operação.
                        <?php endif; ?>
                    </p>
                    <a href="new-operation.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Criar Primeira Operação
                    </a>
                </div>
                <?php else: ?>

                <!-- Desktop Table -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Moeda</th>
                                <th>Valor Inicial</th>
                                <th>Data Início</th>
                                <th>Status</th>
                                <th>Lucro Atual</th>
                                <th>ROI</th>
                                <th>Duração</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($operations as $op): ?>
                            <?php
                                $roi = calculateROI($op['valor_inicial_usdt'], $op['valor_inicial_usdt'] + $op['lucro_atual']);
                                $duracao = $op['status_operacao'] === 'finalizada' && $op['data_fim'] 
                                          ? daysBetween($op['data_inicio'], $op['data_fim'])
                                          : daysBetween($op['data_inicio'], date('Y-m-d'));
                            ?>
                            <tr>
                                <td>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($op['moeda_par']); ?></span>
                                </td>
                                <td style="color: #ffffff !important;"><?php echo formatCurrency($op['valor_inicial_usdt'], 'USDT'); ?></td>
                                <td style="color: #ffffff !important;"><?php echo date('d/m/Y', strtotime($op['data_inicio'])); ?></td>
                                <td>
                                    <?php if ($op['status_operacao'] === 'ativa'): ?>
                                    <span class="badge bg-success">Ativa</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Finalizada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="<?php echo $op['lucro_atual'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $op['lucro_atual'] >= 0 ? '+' : ''; ?><?php echo formatCurrency($op['lucro_atual'], 'USDT'); ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $roi >= 0 ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $roi >= 0 ? '+' : ''; ?><?php echo number_format($roi, 2); ?>%
                                    </span>
                                </td>
                                <td><?php echo $duracao; ?> dia<?php echo $duracao !== 1 ? 's' : ''; ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="view-operation.php?id=<?php echo $op['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Ver Detalhes">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($op['status_operacao'] === 'ativa'): ?>
                                        <a href="add-profit.php?operation_id=<?php echo $op['id']; ?>" 
                                           class="btn btn-sm btn-outline-success" title="Adicionar Lucro">
                                            <i class="fas fa-plus"></i>
                                        </a>
                                        <a href="add-balance.php?operation_id=<?php echo $op['id']; ?>" 
                                           class="btn btn-sm btn-outline-info" title="Adicionar Saldo">
                                            <i class="fas fa-wallet"></i>
                                        </a>
                                        <a href="close-operation.php?operation_id=<?php echo $op['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger" title="Fechar Operação">
                                            <i class="fas fa-flag-checkered"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="d-md-none">
                    <?php foreach ($operations as $op): ?>
                    <?php
                        $roi = calculateROI($op['valor_inicial_usdt'], $op['valor_inicial_usdt'] + $op['lucro_atual']);
                        $duracao = $op['status_operacao'] === 'finalizada' && $op['data_fim'] 
                                  ? daysBetween($op['data_inicio'], $op['data_fim'])
                                  : daysBetween($op['data_inicio'], date('Y-m-d'));
                    ?>
                    <div class="tech-dashboard-card mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($op['moeda_par']); ?></span>
                                    <?php if ($op['status_operacao'] === 'ativa'): ?>
                                    <span class="badge bg-success">Ativa</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Finalizada</span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($op['data_inicio'])); ?></small>
                            </div>
                            
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <small class="text-muted d-block">Valor Inicial</small>
                                    <strong class="text-light"><?php echo formatCurrency($op['valor_inicial_usdt'], 'USDT'); ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Lucro Atual</small>
                                    <strong class="<?php echo $op['lucro_atual'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $op['lucro_atual'] >= 0 ? '+' : ''; ?><?php echo formatCurrency($op['lucro_atual'], 'USDT'); ?>
                                    </strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">ROI</small>
                                    <span class="badge <?php echo $roi >= 0 ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $roi >= 0 ? '+' : ''; ?><?php echo number_format($roi, 2); ?>%
                                    </span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Duração</small>
                                    <strong class="text-light"><?php echo $duracao; ?> dia<?php echo $duracao !== 1 ? 's' : ''; ?></strong>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-1">
                                <a href="view-operation.php?id=<?php echo $op['id']; ?>" 
                                   class="btn btn-sm btn-tech-dark flex-fill">
                                    <i class="fas fa-eye me-1"></i>Ver
                                </a>
                                <?php if ($op['status_operacao'] === 'ativa'): ?>
                                <a href="add-profit.php?operation_id=<?php echo $op['id']; ?>" 
                                   class="btn btn-sm btn-success flex-fill">
                                    <i class="fas fa-plus me-1"></i>Lucro
                                </a>
                                <a href="add-balance.php?operation_id=<?php echo $op['id']; ?>" 
                                   class="btn btn-sm btn-info flex-fill">
                                    <i class="fas fa-wallet me-1"></i>Saldo
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Navegação de páginas">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <div class="text-center text-muted small">
                    Mostrando <?php echo min($offset + 1, $total_records); ?> a <?php echo min($offset + $per_page, $total_records); ?> 
                    de <?php echo $total_records; ?> registro<?php echo $total_records !== 1 ? 's' : ''; ?>
                </div>
                <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
