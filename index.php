<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

// Get user operations summary
$summary = getUserOperationsSummary($_SESSION['user_id'], $pdo);

// Get recent active operations
$stmt = $pdo->prepare("SELECT * FROM operacoes WHERE usuario_id = ? AND status_operacao = 'ativa' ORDER BY data_inicio DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$recent_operations = $stmt->fetchAll();

// Get recent profits
$stmt = $pdo->prepare("SELECT rl.*, o.moeda_par FROM registros_lucro rl 
                      INNER JOIN operacoes o ON rl.operacao_id = o.id 
                      WHERE o.usuario_id = ? 
                      ORDER BY rl.created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$recent_profits = $stmt->fetchAll();

// Get chart data with real database values
$chart_data = getDailyProfitData($_SESSION['user_id'], $pdo);

$page_title = 'Dashboard - Arbitragem Cripto';
?>

<?php include 'includes/header.php'; ?>

<!-- Dashboard Header -->
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center dashboard-header">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-tech-dark" data-bs-toggle="modal" data-bs-target="#newOperationModal">
                    <i class="fas fa-plus-circle me-1"></i>
                    <span class="d-none d-sm-inline">Nova Operação</span>
                    <span class="d-sm-none">Nova Operação</span>
                </button>
                <button type="button" class="btn btn-tech-dark" data-bs-toggle="modal" data-bs-target="#profitModal">
                    <i class="fas fa-chart-line me-1"></i>
                    <span class="d-none d-sm-inline">Registrar Lucro</span>
                    <span class="d-sm-none">Lucro</span>
                </button>

            </div>
        </div>
    </div>
</div>

<!-- Metrics Cards -->
<div class="row mb-4 metrics-row">
    <div class="col-6 col-lg-2 mb-3">
        <div class="tech-dashboard-card metric-card-primary h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="metric-label">Ordens Ativas</div>
                        <div class="metric-value"><?php echo $summary['active_operations']; ?></div>
                    </div>
                    <div class="metric-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-2 mb-3">
        <div class="tech-dashboard-card metric-card-success h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="metric-label">$ Investido</div>
                        <div class="metric-value"><?php echo formatCurrency($summary['total_invested'], 'USDT'); ?></div>
                    </div>
                    <div class="metric-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-2 mb-3">
        <div class="tech-dashboard-card metric-card-info h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="metric-label">Lucro Ativo</div>
                        <div class="metric-value"><?php echo formatCurrency($summary['active_profit'], 'USDT'); ?></div>
                    </div>
                    <div class="metric-icon">
                        <i class="fas fa-arrow-trend-up"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-2 mb-3">
        <div class="tech-dashboard-card metric-card-warning h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="metric-label">ROI Total</div>
                        <div class="metric-value">
                            <?php 
                                $roi_percentage = ($summary['total_invested'] > 0) ? (($summary['active_profit'] / $summary['total_invested']) * 100) : 0;
                                echo number_format($roi_percentage, 2); 
                            ?>%
                        </div>
                    </div>
                    <div class="metric-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-2 mb-3">
        <div class="tech-dashboard-card metric-card-purple h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="metric-label">Dias Operados</div>
                        <div class="metric-value"><?php echo $summary['days_operated']; ?></div>
                    </div>
                    <div class="metric-icon">
                        <i class="fas fa-calendar-days"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-2 mb-3">
        <div class="tech-dashboard-card metric-card-cyan h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="metric-label">Média % Dia</div>
                        <div class="metric-value"><?php echo number_format($summary['avg_roi_per_day'], 2); ?>%</div>
                    </div>
                    <div class="metric-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>






<!-- Profit Chart Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="tech-dashboard-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="section-title mb-0 d-none d-md-block" style="font-size: 1.1rem;">
                        <i class="fas fa-chart-area me-2"></i>
                        EVOLUÇÃO DOS LUCROS
                    </h5>
                    <div class="d-flex gap-2 align-items-center">
                        <select id="chartPeriod" class="form-select form-select-sm tech-input" style="width: auto; font-size: 0.8rem;">
                            <option value="7">7 dias</option>
                            <option value="15">15 dias</option>
                            <option value="30" selected>30 dias</option>
                            <option value="60">60 dias</option>
                            <option value="90">90 dias</option>
                        </select>
                        <button id="chartModeToggle" class="btn btn-sm btn-tech-dark" style="font-size: 0.7rem;">
                            <i class="fas fa-chart-line me-1"></i>Acumulado
                        </button>
                    </div>
                </div>
                <div class="chart-container" style="position: relative; height: 300px;">
                    <canvas id="profitChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- New Operation Modal -->
<div class="modal fade" id="newOperationModal" tabindex="-1" aria-labelledby="newOperationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.2);">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title section-title mb-0" id="newOperationModalLabel" style="font-size: 1.125rem;">
                    <i class="fas fa-plus-circle me-2"></i>
                    NOVA OPERAÇÃO DE ARBITRAGEM
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="operationAlert" class="alert d-none" role="alert"></div>
                
                <form id="newOperationForm">
                    <!-- Dados Principais -->
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label for="modal_inicial_usdt" class="form-label text-light">
                                <i class="fas fa-coins me-1"></i>
                                Inicial USDT *
                            </label>
                            <input type="number" class="form-control tech-input" id="modal_inicial_usdt" name="inicial_usdt" 
                                   step="0.00000001" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label for="modal_data_compra" class="form-label text-light">
                                <i class="fas fa-calendar me-1"></i>
                                Data Compra *
                            </label>
                            <input type="date" class="form-control tech-input" id="modal_data_compra" name="data_compra" 
                                   max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="modal_moeda" class="form-label text-light">
                                <i class="fas fa-coins me-1"></i>
                                Moeda *
                            </label>
                            <input type="text" class="form-control tech-input" id="modal_moeda" name="moeda" 
                                   placeholder="Ex: BTC/USDT" required>
                        </div>
                    </div>

                    <!-- Operação MEXC (Long) -->
                    <div class="card mb-3" style="background: rgba(0, 255, 127, 0.1); border: 1px solid rgba(0, 255, 127, 0.3);">
                        <div class="card-header" style="background: rgba(0, 255, 127, 0.2); border-bottom: 1px solid rgba(0, 255, 127, 0.3);">
                            <h6 class="mb-0 text-light">
                                <i class="fas fa-arrow-up me-2"></i>MEXC - Posição Long
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-md-2-4">
                                    <label for="mexc_qtd_moeda" class="form-label text-light">
                                        <i class="fas fa-coins me-1"></i>
                                        Qtd. Moeda *
                                    </label>
                                    <input type="number" class="form-control tech-input" id="mexc_qtd_moeda" name="mexc_qtd_moeda" 
                                           step="0.00000001" min="0" required>
                                </div>
                                <div class="col-md-2-4">
                                    <label for="mexc_preco" class="form-label text-light">
                                        <i class="fas fa-dollar-sign me-1"></i>
                                        Preço *
                                    </label>
                                    <input type="number" class="form-control tech-input" id="mexc_preco" name="mexc_preco" 
                                           step="0.00000001" min="0" required>
                                </div>
                                <div class="col-md-2-4">
                                    <label for="mexc_comprado_usdt" class="form-label text-light">
                                        <i class="fas fa-coins me-1"></i>
                                        Comprado USDT *
                                    </label>
                                    <input type="number" class="form-control tech-input" id="mexc_comprado_usdt" name="mexc_comprado_usdt" 
                                           step="0.00000001" min="0" required>
                                </div>
                                <div class="col-md-2-4">
                                    <label for="mexc_taxa_compra" class="form-label text-light">
                                        <i class="fas fa-percentage me-1"></i>
                                        Taxa Compra *
                                    </label>
                                    <input type="number" class="form-control tech-input" id="mexc_taxa_compra" name="mexc_taxa_compra" 
                                           step="0.00000001" min="0" required>
                                </div>
                                <div class="col-md-2-4">
                                    <label for="mexc_total_usdt" class="form-label text-light">
                                        <i class="fas fa-calculator me-1"></i>
                                        Total USDT Operação *
                                    </label>
                                    <input type="number" class="form-control tech-input" id="mexc_total_usdt" name="mexc_total_usdt" 
                                           step="0.00000001" min="0" readonly style="background-color: rgba(255, 255, 255, 0.1);">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Operação BTCC (Short) -->
                    <div class="card mb-3" style="background: rgba(255, 69, 58, 0.1); border: 1px solid rgba(255, 69, 58, 0.3);">
                        <div class="card-header" style="background: rgba(255, 69, 58, 0.2); border-bottom: 1px solid rgba(255, 69, 58, 0.3);">
                            <h6 class="mb-0 text-light">
                                <i class="fas fa-arrow-down me-2"></i>BTCC - Posição Short
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-md-2-4">
                                    <label for="btcc_qtd_moeda" class="form-label text-light">
                                        <i class="fas fa-coins me-1"></i>
                                        Qtd. Moeda *
                                    </label>
                                    <input type="number" class="form-control tech-input" id="btcc_qtd_moeda" name="btcc_qtd_moeda" 
                                           step="0.00000001" min="0" required>
                                </div>
                                <div class="col-md-2-4">
                                    <label for="btcc_preco" class="form-label text-light">
                                        <i class="fas fa-dollar-sign me-1"></i>
                                        Preço *
                                    </label>
                                    <input type="number" class="form-control tech-input" id="btcc_preco" name="btcc_preco" 
                                           step="0.00000001" min="0" required>
                                </div>
                                <div class="col-md-2-4">
                                    <label for="btcc_comprado_usdt" class="form-label text-light">
                                        <i class="fas fa-coins me-1"></i>
                                        Comprado USDT *
                                    </label>
                                    <input type="number" class="form-control tech-input" id="btcc_comprado_usdt" name="btcc_comprado_usdt" 
                                           step="0.00000001" min="0" required>
                                </div>
                                <div class="col-md-2-4">
                                    <label for="btcc_taxa_compra" class="form-label text-light">
                                        <i class="fas fa-percentage me-1"></i>
                                        Taxa Compra *
                                    </label>
                                    <input type="number" class="form-control tech-input" id="btcc_taxa_compra" name="btcc_taxa_compra" 
                                           step="0.00000001" min="0" required>
                                </div>
                                <div class="col-md-2-4">
                                    <label for="btcc_total_usdt" class="form-label text-light">
                                        <i class="fas fa-calculator me-1"></i>
                                        Total USDT Operação *
                                    </label>
                                    <input type="number" class="form-control tech-input" id="btcc_total_usdt" name="btcc_total_usdt" 
                                           step="0.00000001" min="0" readonly style="background-color: rgba(255, 255, 255, 0.1);">
                                </div>
                            </div>
                        </div>
                    </div>


                </form>
            </div>
            <div class="modal-footer border-top border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-tech-dark" id="saveOperationBtn">
                    <i class="fas fa-save me-1"></i>Criar Operação
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const saveBtn = document.getElementById('saveOperationBtn');
    const form = document.getElementById('newOperationForm');
    const alert = document.getElementById('operationAlert');
    
    saveBtn.addEventListener('click', function() {
        const formData = new FormData(form);
        
        // Show loading state
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Criando...';
        
        fetch('operations/create-operation-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', '<i class="fas fa-check-circle me-1"></i>' + data.message);
                form.reset();
                document.getElementById('modal_data_compra').value = '<?php echo date('Y-m-d'); ?>';
                
                // Reload page after 2 seconds to update dashboard
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showAlert('danger', '<i class="fas fa-exclamation-triangle me-1"></i>' + data.message);
            }
        })
        .catch(error => {
            showAlert('danger', '<i class="fas fa-exclamation-triangle me-1"></i>Erro de conexão. Tente novamente.');
        })
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Criar Operação';
        });
    });
    
    function showAlert(type, message) {
        alert.className = `alert alert-${type}`;
        alert.innerHTML = message;
        alert.classList.remove('d-none');
        
        if (type === 'success') {
            setTimeout(() => {
                alert.classList.add('d-none');
            }, 3000);
        }
    }
    
    // Auto-calculate MEXC Total USDT Operação (Preço x Qtd. Moeda)
    function calculateMexcTotalUsdt() {
        const qtd = parseFloat(document.getElementById('mexc_qtd_moeda').value) || 0;
        const preco = parseFloat(document.getElementById('mexc_preco').value) || 0;
        const result = qtd * preco;
        document.getElementById('mexc_total_usdt').value = result.toFixed(8);
    }
    
    // Auto-calculate BTCC Total USDT Operação (Preço x Qtd. Moeda)
    function calculateBtccTotalUsdt() {
        const qtd = parseFloat(document.getElementById('btcc_qtd_moeda').value) || 0;
        const preco = parseFloat(document.getElementById('btcc_preco').value) || 0;
        const result = qtd * preco;
        document.getElementById('btcc_total_usdt').value = result.toFixed(8);
    }
    
    // Add event listeners for auto-calculation
    document.getElementById('mexc_qtd_moeda').addEventListener('input', calculateMexcTotalUsdt);
    document.getElementById('mexc_preco').addEventListener('input', calculateMexcTotalUsdt);
    document.getElementById('btcc_qtd_moeda').addEventListener('input', calculateBtccTotalUsdt);
    document.getElementById('btcc_preco').addEventListener('input', calculateBtccTotalUsdt);
});
</script>

<!-- Profit Modal -->
<div class="modal fade" id="profitModal" tabindex="-1" aria-labelledby="profitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.2);">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title section-title mb-0" id="profitModalLabel" style="font-size: 1.125rem;">
                    <i class="fas fa-chart-line me-2"></i>
                    REGISTRAR LUCRO
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="profitAlert" class="alert d-none" role="alert"></div>
                
                <form id="profitForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="profit_operacao_id" class="form-label text-light">
                                <i class="fas fa-list me-1"></i>
                                Operação Ativa *
                            </label>
                            <select class="form-select tech-input" id="profit_operacao_id" name="operacao_id" required>
                                <option value="">Carregando operações...</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="profit_tipo_lucro" class="form-label text-light">
                                <i class="fas fa-tags me-1"></i>
                                Tipo de Lucro *
                            </label>
                            <select class="form-select tech-input" id="profit_tipo_lucro" name="tipo_lucro" required>
                                <option value="">Selecione o tipo</option>
                                <option value="funding">Funding Rate</option>
                                <option value="arbitragem">Arbitragem</option>
                                <option value="spread">Spread</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="profit_valor_lucro" class="form-label text-light">
                                <i class="fas fa-dollar-sign me-1"></i>
                                Valor do Lucro (USDT) *
                            </label>
                            <input type="number" class="form-control tech-input" id="profit_valor_lucro" name="valor_lucro" 
                                   step="0.00000001" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label for="profit_data_registro" class="form-label text-light">
                                <i class="fas fa-calendar me-1"></i>
                                Data do Registro *
                            </label>
                            <div class="row">
                                <div class="col-7">
                                    <input type="date" class="form-control tech-input" id="profit_data_registro" name="data_registro" 
                                           max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-5">
                                    <select class="form-select tech-input" id="profit_hora_registro" name="hora_registro" required>
                                        <option value="">Hora</option>
                                        <option value="06:00">06:00</option>
                                        <option value="14:00">14:00</option>
                                        <option value="22:00" selected>22:00</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>


                </form>
            </div>
            <div class="modal-footer border-top border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-tech-dark" id="saveProfitBtn">
                    <i class="fas fa-save me-1"></i>Registrar Lucro
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Profit Modal functionality
    const profitModal = document.getElementById('profitModal');
    const saveProfitBtn = document.getElementById('saveProfitBtn');
    const profitForm = document.getElementById('profitForm');
    const profitAlert = document.getElementById('profitAlert');
    const operacaoSelect = document.getElementById('profit_operacao_id');
    
    // Load active operations when modal opens
    profitModal.addEventListener('show.bs.modal', function () {
        loadActiveOperations();
    });
    
    function loadActiveOperations() {
        fetch('operations/get-active-operations.php')
        .then(response => response.json())
        .then(data => {
            operacaoSelect.innerHTML = '<option value="">Selecione uma operação</option>';
            
            if (data.success && data.operations.length > 0) {
                data.operations.forEach(op => {
                    const option = document.createElement('option');
                    option.value = op.id;
                    option.textContent = `${op.moeda_par} - ${op.valor_inicial_usdt} USDT (${op.data_inicio})`;
                    operacaoSelect.appendChild(option);
                });
            } else {
                operacaoSelect.innerHTML = '<option value="">Nenhuma operação ativa encontrada</option>';
            }
        })
        .catch(error => {
            operacaoSelect.innerHTML = '<option value="">Erro ao carregar operações</option>';
        });
    }
    
    saveProfitBtn.addEventListener('click', function() {
        const formData = new FormData(profitForm);
        
        // Show loading state
        saveProfitBtn.disabled = true;
        saveProfitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Registrando...';
        
        fetch('operations/create-profit-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showProfitAlert('success', '<i class="fas fa-check-circle me-1"></i>' + data.message);
                profitForm.reset();
                document.getElementById('profit_data_registro').value = '<?php echo date('Y-m-d'); ?>';
                document.getElementById('profit_hora_registro').value = '22:00';
                
                // Reload page after 2 seconds to update dashboard
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showProfitAlert('danger', '<i class="fas fa-exclamation-triangle me-1"></i>' + data.message);
            }
        })
        .catch(error => {
            showProfitAlert('danger', '<i class="fas fa-exclamation-triangle me-1"></i>Erro de conexão. Tente novamente.');
        })
        .finally(() => {
            saveProfitBtn.disabled = false;
            saveProfitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Registrar Lucro';
        });
    });
    
    function showProfitAlert(type, message) {
        profitAlert.className = `alert alert-${type}`;
        profitAlert.innerHTML = message;
        profitAlert.classList.remove('d-none');
        
        if (type === 'success') {
            setTimeout(() => {
                profitAlert.classList.add('d-none');
            }, 3000);
        }
    }
    
    // Profit chart is handled by chart.js file - no inline code needed
});
</script>

<?php include 'includes/footer.php'; ?>