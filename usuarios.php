<?php
/**
 * SGEB - Gestão de Usuários (Admin)
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar se é admin
if(!isset($_SESSION['admin_id']) || $_SESSION['admin_nivel'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$mensagem = '';
$erro = '';

// Processar ações
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if($acao == 'cadastrar') {
        $nome = limparDados($_POST['nome'] ?? '');
        $usuario = limparDados($_POST['usuario'] ?? '');
        $email = limparDados($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $nivel = $_POST['nivel'] ?? 'operador';
        $status = $_POST['status'] ?? 'ativo';
        
        if(empty($nome) || empty($usuario) || empty($email) || empty($senha)) {
            $erro = 'Preencha todos os campos obrigatórios!';
        } elseif(strlen($senha) < 4) {
            $erro = 'A senha deve ter no mínimo 4 caracteres!';
        } else {
            try {
                $conn = getConexao();
                $check = $conn->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ? OR email = ?");
                $check->execute([$usuario, $email]);
                
                if($check->rowCount() > 0) {
                    $erro = 'Usuário ou e-mail já existe!';
                } else {
                    $stmt = $conn->prepare("INSERT INTO usuarios (nome, usuario, email, senha, nivel, status) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$nome, $usuario, $email, $senha, $nivel, $status]);
                    $mensagem = 'Usuário cadastrado com sucesso!';
                }
            } catch(PDOException $e) {
                $erro = 'Erro ao cadastrar usuário!';
            }
        }
    }
    
    if($acao == 'editar') {
        $id = intval($_POST['id'] ?? 0);
        $nome = limparDados($_POST['nome'] ?? '');
        $usuario = limparDados($_POST['usuario'] ?? '');
        $email = limparDados($_POST['email'] ?? '');
        $nivel = $_POST['nivel'] ?? 'operador';
        $status = $_POST['status'] ?? 'ativo';
        
        try {
            $conn = getConexao();
            $stmt = $conn->prepare("UPDATE usuarios SET nome=?, usuario=?, email=?, nivel=?, status=? WHERE id_usuario=?");
            $stmt->execute([$nome, $usuario, $email, $nivel, $status, $id]);
            $mensagem = 'Usuário atualizado com sucesso!';
        } catch(PDOException $e) {
            $erro = 'Erro ao atualizar usuário!';
        }
    }
    
    if($acao == 'excluir') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $conn = getConexao();
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
            $stmt->execute([$id]);
            $mensagem = 'Usuário excluído com sucesso!';
        } catch(PDOException $e) {
            $erro = 'Erro ao excluir usuário!';
        }
    }
    
    if($acao == 'reset_senha') {
        $id = intval($_POST['id'] ?? 0);
        $nova_senha = $_POST['nova_senha'] ?? '';
        
        if(strlen($nova_senha) < 4) {
            $erro = 'A nova senha deve ter no mínimo 4 caracteres!';
        } else {
            try {
                $conn = getConexao();
                $stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id_usuario = ?");
                $stmt->execute([$nova_senha, $id]);
                $mensagem = 'Senha resetada com sucesso!';
            } catch(PDOException $e) {
                $erro = 'Erro ao resetar senha!';
            }
        }
    }
}

// Buscar usuários
try {
    $conn = getConexao();
    $stmt = $conn->query("SELECT * FROM usuarios ORDER BY id_usuario");
    $usuarios = $stmt->fetchAll();
} catch(PDOException $e) {
    $usuarios = [];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários - SGEB Admin</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .header {
            background: linear-gradient(135deg, #1a237e, #283593);
            color: white;
            padding: 15px 0;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .logo { display: flex; align-items: center; gap: 15px; }
        .logo-icon { font-size: 2rem; }
        .logo-text h1 { font-size: 1.3rem; }
        .nav-menu { display: flex; gap: 10px; flex-wrap: wrap; }
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
        }
        .nav-link:hover { background: rgba(255,255,255,0.2); }
        .main-content { padding: 40px 0; }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .page-header h1 { color: #1a237e; }
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        .card-header {
            padding: 20px 25px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .card-body { padding: 25px; }
        .table-responsive { overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        .table th { background: #f5f5f5; font-weight: 600; }
        .table tr:hover { background: #f8f9fa; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary { background: #1a237e; color: white; }
        .btn-danger { background: #c62828; color: white; }
        .btn-warning { background: #f57c00; color: white; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-admin { background: #1a237e; color: white; }
        .badge-gerente { background: #f57c00; color: white; }
        .badge-operador { background: #00897b; color: white; }
        .badge-ativo { background: #e8f5e9; color: #2e7d32; }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            padding: 20px;
            background: #1a237e;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-close { background: none; border: none; color: white; font-size: 24px; cursor: pointer; }
        .modal-body { padding: 25px; }
        .modal-footer { padding: 15px 25px; background: #f5f5f5; display: flex; justify-content: flex-end; gap: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .erro, .sucesso { padding: 12px 15px; border-radius: 10px; margin-bottom: 20px; }
        .erro { background: #ffebee; color: #c62828; border-left: 4px solid #c62828; }
        .sucesso { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #2e7d32; }
        .footer {
            background: #0d1b5e;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 40px;
        }
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; gap: 0; }
            .page-header { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <div class="logo-icon"><i class="fas fa-landmark"></i></div>
                    <div class="logo-text">
                        <h1>SGEB - ADMIN</h1>
                        <p>Painel Administrativo</p>
                    </div>
                </div>
                <nav class="nav-menu">
                    <a href="index.php" class="nav-link">Dashboard</a>
                    <a href="usuarios.php" class="nav-link">Usuários</a>
                    <a href="logout.php" class="nav-link">Sair</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-users-gear"></i> Gestão de Usuários</h1>
                <button class="btn btn-primary" onclick="abrirModalNovo()">
                    <i class="fas fa-user-plus"></i> Novo Usuário
                </button>
            </div>

            <?php if($mensagem): ?>
                <div class="sucesso"><i class="fas fa-check-circle"></i> <?php echo $mensagem; ?></div>
            <?php endif; ?>

            <?php if($erro): ?>
                <div class="erro"><i class="fas fa-exclamation-triangle"></i> <?php echo $erro; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>Lista de Usuários</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuário</th>
                                    <th>Nome</th>
                                    <th>E-mail</th>
                                    <th>Nível</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($usuarios as $user): ?>
                                <tr>
                                    <td><?php echo $user['id_usuario']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($user['usuario']); ?></strong>