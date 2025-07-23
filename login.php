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
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif (!validateEmail($email)) {
        $error = 'Email inválido.';
    } else {
        if (login($email, $password, $pdo)) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Email ou senha incorretos.';
        }
    }
}

$page_title = 'Login - Arbitragem Cripto';
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
            <div class="col-md-6 col-lg-5 col-xl-4">
                <div class="auth-card">
                    <div class="auth-header">
                        <div class="tech-icon mb-3" style="font-size: 3rem; color: var(--neon-blue);">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>ARBITRAGEM CRIPTO</h3>
                        <p class="auth-subtitle">Sistema Avançado de Trading</p>
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

                        <form method="POST" action="" id="loginForm">
                            <div class="form-floating mb-4">
                                <input class="form-control tech-input" id="inputEmail" type="email" name="email" 
                                       placeholder="seu@email.com"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required />
                                <label for="inputEmail">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </label>
                            </div>
                            
                            <div class="form-floating mb-4">
                                <input class="form-control tech-input" id="inputPassword" type="password" name="password" 
                                       placeholder="Sua senha" required />
                                <label for="inputPassword">
                                    <i class="fas fa-lock me-2"></i>Senha
                                </label>
                            </div>
                            
                            <div class="d-grid mb-4">
                                <button class="btn btn-tech w-100 py-3" type="submit">
                                    <i class="fas fa-rocket me-2"></i>
                                    <span>INICIAR SESSÃO</span>
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center">
                            <div>
                                <a href="register.php" class="tech-link">
                                    <i class="fas fa-user-plus me-1"></i>
                                    Criar Nova Conta
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tech footer -->
                    <div class="text-center p-3" style="border-top: 1px solid rgba(255,255,255,0.1);">
                        <small class="text-secondary">
                            <i class="fas fa-shield-alt me-1"></i>
                            Conexão Segura SSL
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
        const particleCount = 50;
        
        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.dx = (Math.random() - 0.5) * 0.5;
                this.dy = (Math.random() - 0.5) * 0.5;
                this.size = Math.random() * 2;
                this.opacity = Math.random() * 0.5 + 0.2;
            }
            
            update() {
                this.x += this.dx;
                this.y += this.dy;
                
                if (this.x < 0 || this.x > canvas.width) this.dx = -this.dx;
                if (this.y < 0 || this.y > canvas.height) this.dy = -this.dy;
            }
            
            draw() {
                ctx.globalAlpha = this.opacity;
                ctx.fillStyle = '#00f5ff';
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
                    
                    if (distance < 100) {
                        ctx.globalAlpha = (1 - distance / 100) * 0.1;
                        ctx.strokeStyle = '#00f5ff';
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
    </script>
</body>
</html>