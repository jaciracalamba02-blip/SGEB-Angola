<?php
/**
 * SGEB Angola - Login do Cliente
 * Versão com ligação ao banco de dados MySQL
 */

// Iniciar sessão
session_start();

// Incluir arquivos de configuração
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Ativar exibição de erros para debug (remover em produção)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar se já está logado
if(isset($_SESSION['cliente_id']) && isset($_SESSION['cliente_logado'])) {
    header("Location: dashboard.cliente.php");
    exit();
}

$erro = '';

// Processar formulário de login
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = limparDados($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    // Validar campos
    if(empty($usuario) || empty($senha)) {
        $erro = 'Preencha todos os campos!';
    } else {
        try {
            $conn = getConexao();
            
            // Buscar cliente por email OU usuário
            $sql = "SELECT * FROM clientes WHERE (email = :usuario OR usuario = :usuario) AND status = 'ativo'";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':usuario' => $usuario]);
            $cliente = $stmt->fetch();
            
            // Verificar se encontrou o cliente
            if(!$cliente) {
                $erro = 'Usuário não encontrado! Verifique seus dados.';
            }
            // Verificar senha (comparação direta - para produção usar password_verify)

             if(password_verify($senha, $dados['senha'])){
    echo "Login correto";
}
            elseif($senha !== $cliente['senha']) {
                $erro = 'Senha incorreta! Tente novamente.';
            }
            // Login bem sucedido
            else {
                // Guardar dados na sessão
                $_SESSION['cliente_id'] = $cliente['id_cliente'];
                $_SESSION['cliente_nome'] = $cliente['nome'];
                $_SESSION['cliente_email'] = $cliente['email'];
                $_SESSION['cliente_usuario'] = $cliente['usuario'];
                $_SESSION['cliente_logado'] = true;
                
                // Atualizar último acesso
                $update = $conn->prepare("UPDATE clientes SET ultimo_acesso = NOW() WHERE id_cliente = ?");
                $update->execute([$cliente['id_cliente']]);
                
                // Redirecionar para dashboard
                header("Location: dashboard-cliente.php");
                exit();
            }
        } catch(PDOException $e) {
            $erro = 'Erro ao conectar à base de dados: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Cliente - SGEB Angola</title>
    <link rel="stylesheet" href="index.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0a1a3a 0%, #0d1f44 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            max-width: 480px;
            width: 90%;
            margin: 20px;
        }

        .login-card {
            background: white;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            border: 1px solid rgba(201,160,61,0.3);
            transition: transform 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
        }

        .login-header {
            background: linear-gradient(135deg, #0a1a3a, #0d1f44);
            color: white;
            text-align: center;
            padding: 40px 30px;
            border-bottom: 3px solid #c9a03d;
        }

        .login-icon {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #c9a03d, #e6c468);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .login-icon i {
            font-size: 45px;
            color: #0a1a3a;
        }

        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .login-header p {
            font-size: 14px;
            opacity: 0.85;
            color: #e0e0e0;
        }

        .login-body {
            padding: 35px 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #0a1a3a;
            font-size: 14px;
        }

        .form-group label i {
            margin-right: 8px;
            color: #c9a03d;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #c9a03d;
            font-size: 16px;
        }

        .input-group input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: inherit;
        }

        .input-group input:focus {
            outline: none;
            border-color: #c9a03d;
            box-shadow: 0 0 0 3px rgba(201,160,61,0.1);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #c9a03d, #e6c468);
            color: #0a1a3a;
            border: none;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(201,160,61,0.4);
        }

        .links {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .links a {
            color: #0a1a3a;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .links a:hover {
            color: #c9a03d;
        }

        .links p {
            margin-top: 12px;
            font-size: 13px;
            color: #666;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }

        .alert i {
            font-size: 18px;
        }

        @media (max-width: 768px) {
            .login-header {
                padding: 30px 20px;
            }
            
            .login-body {
                padding: 25px 20px;
            }
            
            .login-icon {
                width: 70px;
                height: 70px;
            }
            
            .login-icon i {
                font-size: 35px;
            }
            
            .login-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h1>Área do Cliente</h1>
                <p>Faça login para solicitar seu empréstimo</p>
            </div>
            <div class="login-body">

                <?php if($erro): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo $erro; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> E-mail ou Usuário</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="text" name="usuario" placeholder="Digite seu e-mail ou usuário" autocomplete="off" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Senha</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="senha" placeholder="Digite sua senha" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Entrar no Sistema
                    </button>
                </form>

                <div class="links">
                    <p>Não tem uma conta? <a href="cadastro.php"><strong>Criar conta gratuita</strong></a></p>
                    <p><a href="index.php"><i class="fas fa-home"></i> Voltar para o site</a></p>
                </div>

            </div>
        </div>
    </div>

</body>
</html>