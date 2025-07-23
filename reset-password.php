<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    header('Location: forgot-password.php');
    exit;
}

// Validate token
$reset_data = validatePasswordResetToken($token, $pdo);
if (!$reset_data) {
    $error = 'Token inválido ou expirado.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif (strlen($password) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($password !== $confirm_password) {
        $error = 'As senhas não coincidem.';
    } else {
        if (usePasswordResetToken($token, $password, $pdo)) {
            $success = 'Senha alterada com sucesso! Você já pode fazer login.';
        } else {
            $error = 'Erro ao alterar senha. Tente novamente.';
        }
    }
}

$page_title = 'Redefinir Senha - Arbitragem Cripto';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg border-0 rounded-lg mt-5">
                    <div class="card-header">
                        <h3 class="text-center font-weight-light my-4">
                            <i class="fas fa-lock me-2"></i>
                            Nova Senha
                        </h3>
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
                                <a href="login.php" class="btn btn-success btn-sm">Fazer Login</a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!$error && !$success): ?>
                        <form method="POST" action="">
                            <div class="form-floating mb-3">
                                <input class="form-control" id="inputPassword" type="password" name="password" required />
                                <label for="inputPassword">Nova Senha</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input class="form-control" id="inputPasswordConfirm" type="password" name="confirm_password" required />
                                <label for="inputPasswordConfirm">Confirmar Nova Senha</label>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                                <a class="small" href="login.php">Voltar ao login</a>
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-save me-1"></i>Alterar Senha
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
