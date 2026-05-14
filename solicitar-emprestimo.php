<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

verificarCliente();

$cliente = getClienteLogado();
$erro = '';
$sucesso = '';
$simulacao = null;

try {
    $conn = getConexao();
    $bancos = $conn->query("SELECT * FROM bancos WHERE status = 'ativo' ORDER BY nome")->fetchAll();
} catch(PDOException $e) {
    $bancos = [];
}

if(isset($_POST['simular'])) {
    $valor = floatval($_POST['valor'] ?? 0);
    $taxa = floatval($_POST['taxa'] ?? 0);
    $prazo = intval($_POST['prazo'] ?? 12);
    if($valor > 0 && $taxa > 0 && $prazo > 0) {
        $simulacao = simularEmprestimo($valor, $taxa, $prazo);
    }
}

if(isset($_POST['enviar'])) {
    $valor = floatval($_POST['valor'] ?? 0);
    $id_banco = intval($_POST['id_banco'] ?? 0);
    $prazo = intval($_POST['prazo'] ?? 12);
    $taxa = floatval($_POST['taxa'] ?? 0);
    $finalidade = limparDados($_POST['finalidade'] ?? '');
    $observacoes = limparDados($_POST['observacoes'] ?? '');
    $aceito_termos = isset($_POST['aceito_termos']) ? 1 : 0;
    
    if($valor <= 0) {
        $erro = 'Informe o valor desejado!';
    } elseif($id_banco <= 0) {
        $erro = 'Selecione um banco!';
    } elseif(!$aceito_termos) {
        $erro = 'Aceite os termos!';
    } else {
        try {
            $sim = simularEmprestimo($valor, $taxa, $prazo);
            $parcela = $sim['parcela'];
            $total = $sim['total'];
            $juros = $sim['juros'];
            
            $stmt = $conn->prepare("INSERT INTO emprestimos (id_cliente, id_banco, valor_solicitado, taxa_juros, prazo_meses, valor_parcela, total_pagar, total_juros, finalidade, observacoes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente')");
            $stmt->execute([$cliente['id'], $id_banco, $valor, $taxa, $prazo, $parcela, $total, $juros, $finalidade, $observacoes]);
            $sucesso = 'Solicitação enviada com sucesso! Aguarde análise.';
            $_POST = [];
        } catch(PDOException $e) {
            $erro = 'Erro ao enviar solicitação!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Empréstimo - SGEB Angola</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0a1a3a 0%, #0d1f44 100%); min-height: 100vh; }
        .container { max-width: 900px; margin: 0 auto; padding: 0 30px; }
        
        .header {
            background: linear-gradient(135deg, #0a1a3a 0%, #0d1f44 100%);
            color: white;
            padding: 18px 0;
            border-bottom: 3px solid #c9a03d;
        }
        .header-content { display: flex; flex-direction: column; align-items: center; text-align: center; }
        .logo { display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 15px; }
        .logo-icon { width: 50px; height: 50px; background: linear-gradient(135deg, #c9a03d, #e6c468); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: #0a1a3a; }
        .logo-text h1 { font-size: 1.6rem; font-weight: 700; background: linear-gradient(135deg, #fff, #c9a03d); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .logo-text p { font-size: 0.75rem; color: #c9a03d; letter-spacing: 2px; }
        .nav-menu { display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; margin-top: 5px; }
        .nav-link {
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            padding: 10px 22px;
            border-radius: 40px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
        }
        .nav-link:hover { background: rgba(201,160,61,0.2); color: #c9a03d; }
        .nav-link.active { background: linear-gradient(135deg, #c9a03d, #e6c468); color: #0a1a3a; font-weight: 600; }
        .btn-sair { background: #c62828; padding: 8px 20px; border-radius: 8px; }
        
        .main-content { padding: 40px 0; }
        .card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(201,160,61,0.2);
        }
        .card-header {
            padding: 22px 28px;
            background: linear-gradient(135deg, #f8f9fa, #fff);
            border-bottom: 2px solid #c9a03d;
        }
        .card-header h1 { font-size: 1.3rem; font-weight: 700; color: #0a1a3a; display: flex; align-items: center; gap: 10px; }
        .card-header h1 i { color: #c9a03d; }
        .card-body { padding: 28px; }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #0a1a3a; font-size: 13px; }
        label i { margin-right: 8px; color: #c9a03d; }
        input, select, textarea { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 14px; }
        input:focus { outline: none; border-color: #c9a03d; }
        
        .btn { display: inline-flex; align-items: center; gap: 10px; padding: 12px 24px; border-radius: 40px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s; border: none; }
        .btn-primary { background: linear-gradient(135deg, #c9a03d, #e6c468); color: #0a1a3a; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 5px 20px rgba(201,160,61,0.4); }
        .btn-outline { background: transparent; border: 2px solid #c9a03d; color: #c9a03d; }
        .btn-group { display: flex; gap: 15px; justify-content: flex-end; margin-top: 20px; flex-wrap: wrap; }
        
        .simulacao-resultado { background: #e8f5e9; border-left: 4px solid #2e7d32; padding: 20px; border-radius: 12px; margin: 20px 0; display: none; }
        .alert { padding: 15px 20px; border-radius: 15px; margin-bottom: 20px; }
        .alert-error { background: #ffebee; color: #c62828; border-left: 4px solid #c62828; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #2e7d32; }
        
        .info-cliente { background: #e3f2fd; padding: 15px 20px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .info-cliente i { font-size: 40px; color: #c9a03d; }
        
        .footer {
            background: #0a1a3a;
            color: white;
            text-align: center;
            padding: 30px;
            border-top: 2px solid #c9a03d;
            margin-top: 40px;
        }
        
        @media (max-width: 768px) {
            .container { padding: 0 20px; }
            .form-row { grid-template-columns: 1fr; gap: 0; }
            .btn-group { flex-direction: column; }
            .nav-link { padding: 8px 16px; font-size: 12px; }
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
                        <h1>SGEB Angola</h1>
                        <p>SOLICITAR EMPRÉSTIMO</p>
                    </div>
                </div>
                <nav class="nav-menu">
                    <a href="dashboard-cliente.php" class="nav-link"><i class="fas fa-home"></i> Início</a>
                    <a href="solicitar-emprestimo-cliente.php" class="nav-link active"><i class="fas fa-hand-holding-usd"></i> Solicitar</a>
                    <a href="meus-emprestimos.php" class="nav-link"><i class="fas fa-list"></i> Meus Empréstimos</a>
                    <a href="perfil-cliente.php" class="nav-link"><i class="fas fa-user"></i> Meu Perfil</a>
                    <a href="logout.php" class="nav-link btn-sair"><i class="fas fa-sign-out-alt"></i> Sair</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h1><i class="fas fa-hand-holding-usd"></i> Solicitar Empréstimo</h1>
                </div>
                <div class="card-body">
                    <?php if($erro): ?>
                        <div class="alert alert-error"><?php echo $erro; ?></div>
                    <?php endif; ?>
                    <?php if($sucesso): ?>
                        <div class="alert alert-success"><?php echo $sucesso; ?></div>
                    <?php endif; ?>
                    
                    <div class="info-cliente">
                        <i class="fas fa-user-circle"></i>
                        <div><strong><?php echo htmlspecialchars($cliente['nome']); ?></strong><br><small><?php echo htmlspecialchars($cliente['email']); ?></small></div>
                    </div>

                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group"><label><i class="fas fa-money-bill-wave"></i> Valor Desejado (KZ) *</label><input type="number" name="valor" id="valor" step="0.01" value="<?php echo htmlspecialchars($_POST['valor'] ?? ''); ?>" required></div>
                            <div class="form-group"><label><i class="fas fa-university"></i> Selecione o Banco *</label><select name="id_banco" id="id_banco" required><option value="">-- Selecione --</option><?php foreach($bancos as $banco): ?><option value="<?php echo $banco['id_banco']; ?>" data-taxa="<?php echo $banco['taxa_base']; ?>"><?php echo htmlspecialchars($banco['nome']); ?> (<?php echo $banco['taxa_base']; ?>%)</option><?php endforeach; ?></select></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label><i class="fas fa-calendar-alt"></i> Prazo (meses) *</label><select name="prazo" id="prazo" required><option value="6">6 meses</option><option value="12" selected>12 meses</option><option value="18">18 meses</option><option value="24">24 meses</option><option value="36">36 meses</option></select></div>
                            <div class="form-group"><label><i class="fas fa-percent"></i> Taxa de Juros (%)</label><input type="text" name="taxa" id="taxa" readonly style="background:#f5f5f5;"></div>
                        </div>
                        <div class="form-group"><label><i class="fas fa-tag"></i> Finalidade</label><textarea name="finalidade" rows="2" placeholder="Descreva a finalidade do empréstimo"></textarea></div>
                        
                        <div id="simulacaoResultado" class="simulacao-resultado" style="display: none;">
                            <strong><i class="fas fa-chart-line"></i> Resultado:</strong>
                            <div id="simulacaoTexto" style="margin-top: 10px;"></div>
                        </div>

                        <div class="form-group"><label><input type="checkbox" name="aceito_termos" value="1"> Declaro que as informações são verdadeiras</label></div>
                        
                        <div class="btn-group">
                            <button type="submit" name="simular" class="btn btn-outline"><i class="fas fa-calculator"></i> Simular</button>
                            <button type="submit" name="enviar" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Enviar Solicitação</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p><span class="gold">◆</span> Complexo Escolar Fenda da Tundavala | 12ª Classe | 2025/2026 <span class="gold">◆</span></p>
            <p>Ícolo e Bengo - Angola | SGEB Angola</p>
        </div>
    </footer>

    <script>
        document.getElementById('id_banco')?.addEventListener('change', function() {
            const taxa = this.options[this.selectedIndex]?.getAttribute('data-taxa');
            document.getElementById('taxa').value = taxa || '';
        });
        
        <?php if($simulacao): ?>
        document.getElementById('simulacaoTexto').innerHTML = `
            <strong>Valor:</strong> <?php echo formatarMoeda($valor_simular); ?><br>
            <strong>Taxa:</strong> <?php echo $taxa_simular; ?>% ao mês<br>
            <strong>Prazo:</strong> <?php echo $prazo_simular; ?> meses<br>
            <strong>Parcela:</strong> <span style="color:#2e7d32;"><?php echo formatarMoeda($simulacao['parcela']); ?></span><br>
            <strong>Total a pagar:</strong> <?php echo formatarMoeda($simulacao['total']); ?><br>
            <strong>Juros:</strong> <?php echo formatarMoeda($simulacao['juros']); ?>
        `;
        document.getElementById('simulacaoResultado').style.display = 'block';
        <?php endif; ?>
    </script>
</body>
</html>