<?php
require_once '../includes/config.php';

// Se já está logado, redirecionar para o painel
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    if (!empty($email) && !empty($password)) {
        try {
            $conn = getConnection();
            $stmt = $conn->prepare("SELECT id, nome, senha_hash FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && verifyPassword($password, $user["senha_hash"])) {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_name'] = $user['nome'];
                redirect('index.php');
            } else {
                $error = 'Email ou senha incorretos.';
            }
        } catch(PDOException $e) {
            $error = 'Erro no sistema. Tente novamente.';
        }
    } else {
        $error = 'Preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrativo - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="../logo.png" alt="<?php echo SITE_NAME; ?>" class="login-logo">
                <h1><?php echo SITE_NAME; ?></h1>
                <p>Painel Administrativo</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Senha:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">Entrar</button>
            </form>
            
            <div class="login-footer">
                <a href="../" class="back-link">← Voltar ao site</a>
            </div>
            
            <div class="login-demo">
                <p><strong>Dados para teste:</strong></p>
                <p>Email: admin@cardapiogo.com</p>
                <p>Senha: admin123</p>
            </div>
        </div>
    </div>
</body>
</html>

