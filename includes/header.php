<?php
if (!isset($page_title)) {
    $page_title = 'Arbitragem Cripto';
}

// Determine the base path for assets and navigation
$base_path = '';
if (strpos($_SERVER['REQUEST_URI'], '/operations/') !== false) {
    $base_path = '../';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?php echo $base_path; ?>assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?php echo $base_path; ?>assets/js/chart.js" defer></script>
</head>
<body class="dashboard-page">
    <?php if (isLoggedIn()): ?>
    <nav class="navbar navbar-expand-lg navbar-dark-tech">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $base_path; ?>index.php" style="font-size: 1.1rem;">
                <i class="fas fa-chart-line me-2"></i>
                ARBITRAGEM CRIPTO
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" 
                           href="<?php echo $base_path; ?>index.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'list-operations.php' ? 'active' : ''; ?>" 
                           href="<?php echo $base_path; ?>operations/list-operations.php">
                            <i class="fas fa-list me-1"></i>Operações
                        </a>
                    </li>

                    <!-- Mobile Only Logout Button -->
                    <li class="nav-item d-lg-none">
                        <a class="nav-link text-danger" href="<?php echo $base_path; ?>logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Sair
                        </a>
                    </li>

                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="<?php echo $base_path; ?>logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>Desconectar
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <main class="<?php echo isLoggedIn() ? 'container mt-4' : ''; ?>">