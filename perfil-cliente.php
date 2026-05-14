<?php
session_start();
require_once 'includes/conexao.php';

// Verificar se o cliente está logado
if(!isset($_SESSION['cliente_id'])) {
    header("Location: login.php");
    exit();
}

$cliente_nome = $_SESSION['cliente_nome'];
$cliente_email = $_SESSION['cliente_email'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - SGEB Angola</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #0a1a3a 0%, #0d1f44 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        .card h1 { color: #0a1a3a; margin-bottom: 20px; }
        .info { margin-bottom: 15px; padding: 10px; background: #f5f5f5; border-radius: 10px; }
        .info label { font-weight: bold; display: inline-block; width: 120px; }
        .btn {
            background: #c62828;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
        }
        .btn-logout { background: #c62828; }
        .btn-home { background: #0a1a3a; margin-right: 10px; }
        .btn-home a { color: white; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1><i class="fas fa-user-circle"></i> Olá, <?php echo htmlspecialchars($cliente_nome); ?>!</h1>
            
            <div class="info">
                <label>Nome:</label> <?php echo htmlspecialchars($cliente_nome); ?>
            </div>
            <div class="info">
                <label>E-mail:</label> <?php echo htmlspecialchars($cliente_email); ?>
            </div>
            <div class="info">
                <label>Usuário:</label> <?php echo htmlspecialchars($_SESSION['cliente_usuario']); ?>
            </div>
            
            <div style="margin-top: 30px;">
                <button class="btn btn-home"><a href="index.php">← Voltar ao Site</a></button>
                <a href="logout.php"><button class="btn btn-logout">Sair</button></a>
            </div>
        </div>
    </div>
</body>
</html>