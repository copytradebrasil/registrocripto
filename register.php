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
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif (!validateEmail($email)) {
        $error = 'Email inválido.';
    } elseif (strlen($senha) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($senha !== $confirmar_senha) {
        $error = 'As senhas não coincidem.';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Este email já está cadastrado.';
        } else {
            // Create user
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
            if ($stmt->execute([$nome, $email, $senha_hash])) {
                $success = 'Conta criada com sucesso! Você já pode fazer login.';
            } else {
                $error = 'Erro ao criar conta. Tente novamente.';
            }
        }
    }
}

$page_title = 'Registro - Arbitragem Cripto';
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
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-page">
    <!-- Animated particles background -->
    <div class="particles-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; pointer-events: none;">
        <canvas id="particles" style="display: block;"></canvas>
    </div>
    
    <div class="container" style="position: relative; z-index: 10;">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6 col-xl-5">
                <div class="auth-card">
                    <div class="auth-header">
                        <div class="tech-icon mb-3" style="font-size: 3rem; color: var(--neon-purple);">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h3>CRIAR CONTA</h3>
                        <p class="auth-subtitle">Junte-se ao Sistema Avançado</p>
                    </div>
                    
                    <div class="p-4">
                        <?php if ($error): ?>
                        <div class="alert alert-tech-error mb-4" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                        <div class="alert alert-tech-success mb-4" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="registerForm">
                            <div class="form-floating mb-4">
                                <input class="form-control tech-input" id="inputNome" type="text" name="nome" 
                                       placeholder="Seu nome completo"
                                       value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required />
                                <label for="inputNome">
                                    <i class="fas fa-user me-2"></i>Nome Completo
                                </label>
                            </div>
                            
                            <div class="form-floating mb-4">
                                <input class="form-control tech-input" id="inputEmail" type="email" name="email" 
                                       placeholder="seu@email.com"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required />
                                <label for="inputEmail">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </label>
                            </div>
                            
                            <div class="form-floating mb-4">
                                <input class="form-control tech-input" id="inputSenha" type="password" name="senha" 
                                       placeholder="Sua senha" required />
                                <label for="inputSenha">
                                    <i class="fas fa-lock me-2"></i>Senha
                                </label>
                                <div class="password-strength mt-2" id="passwordStrength" style="display: none;">
                                    <small class="text-secondary">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Mínimo 6 caracteres
                                    </small>
                                </div>
                            </div>
                            
                            <div class="form-floating mb-4">
                                <input class="form-control tech-input" id="inputConfirmarSenha" type="password" name="confirmar_senha" 
                                       placeholder="Confirme sua senha" required />
                                <label for="inputConfirmarSenha">
                                    <i class="fas fa-lock me-2"></i>Confirmar Senha
                                </label>
                            </div>
                            
                            <div class="d-grid mb-4">
                                <button class="btn btn-tech w-100 py-3" type="submit" style="background: var(--secondary-gradient);">
                                    <i class="fas fa-sparkles me-2"></i>
                                    <span>CRIAR CONTA</span>
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center">
                            <div>
                                <a href="login.php" class="tech-link">
                                    <i class="fas fa-sign-in-alt me-1"></i>
                                    Já tenho uma conta
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tech footer -->
                    <div class="text-center p-3" style="border-top: 1px solid rgba(255,255,255,0.1);">
                        <small class="text-secondary">
                            <i class="fas fa-shield-alt me-1"></i>
                            Dados Protegidos com Criptografia
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    
    <!-- Tech particles animation -->
    <script>
        // Animated particles background
        const canvas = document.getElementById('particles');
        const ctx = canvas.getContext('2d');
        
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        
        const particles = [];
        const particleCount = 60;
        
        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.dx = (Math.random() - 0.5) * 0.8;
                this.dy = (Math.random() - 0.5) * 0.8;
                this.size = Math.random() * 2.5;
                this.opacity = Math.random() * 0.6 + 0.2;
                this.color = Math.random() > 0.5 ? '#00f5ff' : '#bf5af2';
            }
            
            update() {
                this.x += this.dx;
                this.y += this.dy;
                
                if (this.x < 0 || this.x > canvas.width) this.dx = -this.dx;
                if (this.y < 0 || this.y > canvas.height) this.dy = -this.dy;
            }
            
            draw() {
                ctx.globalAlpha = this.opacity;
                ctx.fillStyle = this.color;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
                ctx.globalAlpha = 1;
            }
        }
        
        for (let i = 0; i < particleCount; i++) {
            particles.push(new Particle());
        }
        
        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            particles.forEach(particle => {
                particle.update();
                particle.draw();
            });
            
            // Draw connections
            particles.forEach((particle, i) => {
                particles.slice(i + 1).forEach(otherParticle => {
                    const dx = particle.x - otherParticle.x;
                    const dy = particle.y - otherParticle.y;
                    const distance = Math.sqrt(dx * dx + dy * dy);
                    
                    if (distance < 120) {
                        ctx.globalAlpha = (1 - distance / 120) * 0.15;
                        ctx.strokeStyle = particle.color;
                        ctx.lineWidth = 1;
                        ctx.beginPath();
                        ctx.moveTo(particle.x, particle.y);
                        ctx.lineTo(otherParticle.x, otherParticle.y);
                        ctx.stroke();
                        ctx.globalAlpha = 1;
                    }
                });
            });
            
            requestAnimationFrame(animate);
        }
        
        animate();
        
        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });
        
        // Password strength indicator
        document.getElementById('inputSenha').addEventListener('input', function() {
            const strengthDiv = document.getElementById('passwordStrength');
            const password = this.value;
            
            if (password.length > 0) {
                strengthDiv.style.display = 'block';
                
                if (password.length < 6) {
                    strengthDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times me-1"></i>Muito fraca - Mínimo 6 caracteres</small>';
                } else if (password.length >= 6 && password.length < 8) {
                    strengthDiv.innerHTML = '<small class="text-warning"><i class="fas fa-check me-1"></i>Boa - Recomendado 8+ caracteres</small>';
                } else {
                    strengthDiv.innerHTML = '<small class="text-success"><i class="fas fa-shield-alt me-1"></i>Forte - Senha segura</small>';
                }
            } else {
                strengthDiv.style.display = 'none';
            }
        });
    </script>
</body>
</html>