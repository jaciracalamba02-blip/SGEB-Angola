<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if(isset($_SESSION['admin_id']) && isset($_SESSION['admin_logado'])) {
    header("Location: admin-dashboard.php");
    exit();
}

$erro = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = limparDados($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if(empty($usuario) || empty($senha)) {
        $erro = 'Preencha todos os campos!';
    } else {
        try {
            $conn = getConexao();
            $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = ? AND status = 'ativo'");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch();
            
            if($user && $senha === $user['senha']) {
                $_SESSION['admin_id'] = $user['id_usuario'];
                $_SESSION['admin_nome'] = $user['nome'];
                $_SESSION['admin_usuario'] = $user['usuario'];
                $_SESSION['admin_nivel'] = $user['nivel'];
                $_SESSION['admin_logado'] = true;
                
                header("Location: admin/dashboard.php");
                exit();
            } else {
                $erro = 'Usuário ou senha inválidos!';
            }
        } catch(PDOException $e) {
            $erro = 'Erro ao conectar. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - SGEB Angola</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0a1a3a 0%, #0d1f44 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container { max-width: 450px; width: 90%; margin: 20px; }
        .login-card {
            background: white;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            border: 1px solid rgba(201,160,61,0.3);
        }
        .login-header {
            background: linear-gradient(135deg, #0a1a3a, #0d1f44);
            color: white;
            text-align: center;
            padding: 40px 30px;
            border-bottom: 3px solid #c9a03d;
        }
        .login-icon { width: 80px; height: 80px; background: linear-gradient(135deg, #c9a03d, #e6c468); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
        .login-icon i { font-size: 40px; color: #0a1a3a; }
        .login-header h1 { font-size: 28px; font-weight: 700; }
        .login-body { padding: 35px 30px; }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 10px; font-weight: 600; color: #0a1a3a; }
        .input-group { position: relative; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #c9a03d; }
        .input-group input { width: 100%; padding: 14px 15px 14px 45px; border: 2px solid #e0e0e0; border-radius: 15px; font-size: 15px; }
        .input-group input:focus { outline: none; border-color: #c9a03d; }
        .btn-login { width: 100%; padding: 14px; background: linear-gradient(135deg, #c9a03d, #e6c468); color: #0a1a3a; border: none; border-radius: 40px; font-size: 16px; font-weight: 700; cursor: pointer; }
        .btn-login:hover { transform: translateY