<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

if(isset($_SESSION['cliente_id']) && isset($_SESSION['cliente_logado'])) {
    header("Location: dashboard.php");
    exit();
}

$erro = '';
$sucesso = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = limparDados($_POST['nome'] ?? '');
    $nif = limparDados($_POST['nif'] ?? '');
    $email = limparDados($_POST['email'] ?? '');
    $telefone = limparDados($_POST['telefone'] ?? '');
    $usuario = limparDados($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $data_nascimento = $_POST['data_nascimento'] ?? '';
    $profissao = limparDados($_POST['profissao'] ?? '');
    $endereco = limparDados($_POST['endereco'] ?? '');
    $renda = floatval($_POST['renda'] ?? 0);
    $aceito_termos = isset($_POST['aceito_termos']) ? 1 : 0;
    
    if(empty($nome) || empty($nif) || empty($email) || empty($telefone) || empty($usuario) || empty($senha)) {
        $erro = 'Preencha todos os campos obrigatórios!';
    } elseif(strlen($senha) < 4) {
        $erro = 'A senha deve ter no mínimo 4 caracteres!';
    } elseif($senha !== $confirmar_senha) {
        $erro = 'As senhas não coincidem!';
    } elseif(!validarEmail($email)) {
        $erro = 'Digite um e-mail válido!';
    } elseif(!validarNIF($nif)) {
        $erro = 'NIF inválido! Deve ter 9 dígitos.';
    } elseif(!validarTelefoneAngola($telefone)) {
        $erro = 'Telefone inválido! Deve ter 9 dígitos e começar com 9.';
    } elseif(!$aceito_termos) {
        $erro = 'Você precisa aceitar os termos e condições!';
    } else {
        try {
            $conn = getConexao();
            
            $check = $conn->prepare("SELECT id_cliente FROM clientes WHERE email = ? OR usuario = ? OR nif = ?");
            $check->execute([$email, $usuario, $nif]);
            
            if($check->rowCount() > 0) {
                $erro = 'E-mail, usuário ou NIF já cadastrado!';
            } else {
                $stmt = $conn->prepare("INSERT INTO clientes (nome, nif, email, telefone, usuario, senha, data_nascimento, profissao, endereco, renda_mensal, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ativo')");
                $stmt->execute([$nome, $nif, $email, $telefone, $usuario, $senha, $data_nascimento, $profissao, $endereco, $renda]);
                
                $sucesso = 'Cadastro realizado com sucesso! Faça login para continuar.';
                $_POST = [];
            }
        } catch(PDOException $e) {
            $erro = 'Erro ao cadastrar. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Cliente - SGEB Angola</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0a1a3a 0%, #0d1f44 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container { max-width: 650px; margin: 0 auto; }
        .cadastro-card {
            background: white;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            border: 1px solid rgba(201,160,61,0.3);
        }
        .cadastro-header {
            background: linear-gradient(135deg, #0a1a3a, #0d1f44);
            color: white;
            text-align: center;
            padding: 35px 30px;
            border-bottom: 3px solid #c9a03d;
        }
        .cadastro-header i { font-size: 60px; margin-bottom: 15px; }
        .cadastro-header h1 { font-size: 28px; font-weight: 700; }
        .cadastro-body { padding: 35px 30px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #0a1a3a; font-size: 13px; }
        label i { margin-right: 8px; color: #c9a03d; }
        label .required { color: #c62828; margin-left: 3px; }
        input, select { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 14px; transition: all 0.3s; }
        input:focus { outline: none; border-color: #c9a03d; }
        .termos-area {
            max-height: 150px;
            overflow-y: auto;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 12px;
            font-size: 12px;
            margin: 20px 0;
            border: 1px solid #e0e0e0;
        }
        .checkbox-group { display: flex; align-items: center; gap: 10px; margin: 20px 0; }
        .btn-cadastrar { width: 100%; padding: 14px; background: linear-gradient(135deg, #c9a03d, #e6c468); color: #0a1a3a; border: none; border-radius: 40px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s; margin-top: 10px; }
        .btn-cadastrar:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(201,160,61,0.4); }
        .links { text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #e0e0e0; }
        .links a { color: #0a1a3a; text-decoration: none; font-weight: bold; }
        .alert { padding: 15px 20px; border-radius: 15px; margin-bottom: 20px; }
        .alert-error { background: #ffebee; color: #c62828; border-left: 4px solid #c62828; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #2e7d32; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; gap: 0; } .cadastro-body { padding: 25px 20px; } }
    </style>
</head>
<body>

    <div class="container">
        <div class="cadastro-card">
            <div class="cadastro-header">
                <i class="fas fa-user-plus"></i>
                <h1>Criar Conta</h1>
                <p>Cadastre-se para solicitar seu empréstimo</p>
            </div>
            <div class="cadastro-body">
                <?php if($erro): ?>
                    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $erro; ?></div>
                <?php endif; ?>
                <?php if($sucesso): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $sucesso; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group"><label><i class="fas fa-user"></i> Nome Completo *</label><input type="text" name="nome" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required></div>
                        <div class="form-group"><label><i class="fas fa-id-card"></i> NIF *</label><input type="text" name="nif" id="nif" value="<?php echo htmlspecialchars($_POST['nif'] ?? ''); ?>" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label><i class="fas fa-envelope"></i> E-mail *</label><input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required></div>
                        <div class="form-group"><label><i class="fas fa-phone"></i> Telefone *</label><input type="text" name="telefone" id="telefone" value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label><i class="fas fa-user-tag"></i> Usuário *</label><input type="text" name="usuario" value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>" required></div>
                        <div class="form-group"><label><i class="fas fa-calendar-alt"></i> Data Nascimento</label><input type="date" name="data_nascimento" value="<?php echo htmlspecialchars($_POST['data_nascimento'] ?? ''); ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label><i class="fas fa-lock"></i> Senha *</label><input type="password" name="senha" required></div>
                        <div class="form-group"><label><i class="fas fa-lock"></i> Confirmar Senha *</label><input type="password" name="confirmar_senha" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label><i class="fas fa-briefcase"></i> Profissão</label><input type="text" name="profissao" value="<?php echo htmlspecialchars($_POST['profissao'] ?? ''); ?>"></div>
                        <div class="form-group"><label><i class="fas fa-money-bill-wave"></i> Renda Mensal (KZ)</label><input type="number" name="renda" step="0.01" value="<?php echo htmlspecialchars($_POST['renda'] ?? ''); ?>"></div>
                    </div>
                    <div class="form-group"><label><i class="fas fa-map-marker-alt"></i> Endereço</label><input type="text" name="endereco" value="<?php echo htmlspecialchars($_POST['endereco'] ?? ''); ?>"></div>
                    
                    <div class="termos-area">
                        <h4><i class="fas fa-file-contract"></i> Termos e Condições</h4>
                        <p>1. Declaro que as informações são verdadeiras.</p>
                        <p>2. Autorizo consulta aos meus dados creditícios.</p>
                        <p>3. Aprovação sujeita à análise de crédito.</p>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" name="aceito_termos" id="aceito_termos" value="1">
                        <label for="aceito_termos">Aceito os termos e condições</label>
                    </div>
                    
                    <button type="submit" class="btn-cadastrar"><i class="fas fa-user-plus"></i> Criar Conta</button>
                </form>
                <div class="links"><p>Já tem conta? <a href="login.php"><strong>Faça login</strong></a></p></div>
            </div>
        </div>
    </div>

    <script>
        function mascaraNIF(input) {
            let valor = input.value.replace(/\D/g, '');
            if(valor.length <= 9) {
                if(valor.length > 3 && valor.length <= 6) valor = valor.replace(/(\d{3})(\d+)/, '$1 $2');
                else if(valor.length > 6) valor = valor.replace(/(\d{3})(\d{3})(\d+)/, '$1 $2 $3');
                input.value = valor;
            }
        }
        function mascaraTelefone(input) {
            let valor = input.value.replace(/\D/g, '');
            if(valor.length <= 9) {
                if(valor.length > 3 && valor.length <= 6) valor = valor.replace(/(\d{3})(\d+)/, '$1 $2');
                else if(valor.length > 6) valor = valor.replace(/(\d{3})(\d{3})(\d+)/, '$1 $2 $3');
                input.value = valor;
            }
        }
        document.getElementById('nif')?.addEventListener('input', function() { mascaraNIF(this); });
        document.getElementById('telefone')?.addEventListener('input', function() { mascaraTelefone(this); });
    </script>
</body>
</html>