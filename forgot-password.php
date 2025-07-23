<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Por favor, digite seu email.';
    } elseif (!validateEmail($email)) {
        $error = 'Email inválido.';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND status = 'ativo'");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $token = generatePasswordResetToken($email, $pdo);
            if ($token) {
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $token;
                $subject = "Recuperação de Senha - Arbitragem Cripto";
                $message = "Clique no link para redefinir sua senha: " . $reset_link;
                
                if (sendEmail($email, $subject, $message)) {
                    $success = 'Email de recuperação enviado! Verifique sua caixa de entrada.';
                } else {
                    $error = 'Erro ao enviar email. Tente novamente.';
                }
            } else {
                $error = 'Erro ao gerar token de recuperação. Tente novamente.';
            }
        } else {
            // Don't reveal if email exists or not
            $success = 'Se o email estiver cadastrado, você receberá as instruções de recuperação.';
        }
    }
}

$page_title = 'Recuperar Senha - Arbitragem Cripto';
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
                            <i class="fas fa-key me-2"></i>
                            Recuperar Senha
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="small mb-3 text-muted">
                            Digite seu email para receber as instruções de recuperação de senha.
                        </div>

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
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="form-floating mb-3">
                                <input class="form-control" id="inputEmail" type="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required />
                                <label for="inputEmail">Email</label>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                                <a class="small" href="login.php">Voltar ao login</a>
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-paper-plane me-1"></i>Enviar
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center py-3">
                        <div class="small">
                            <a href="register.php">Não tem uma conta? Criar conta</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
