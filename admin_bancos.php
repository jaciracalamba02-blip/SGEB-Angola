<?php
session_start();
require_once 'config.php';
require_once 'auth.php';
require_once 'funcões.php';

verificarAdmin();

$mensagem = '';
$erro = '';

// Processar formulário
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if($acao == 'cadastrar') {
        $nome = limparDados($_POST['nome'] ?? '');
        $sigla = limparDados($_POST['sigla'] ?? '');
        $telefone = limparDados($_POST['telefone'] ?? '');
        $email = limparDados($_POST['email'] ?? '');
        $site = limparDados($_POST['site'] ?? '');
        $taxa = floatval($_POST['taxa'] ?? 0);
        $endereco = limparDados($_POST['endereco'] ?? '');
        $status = $_POST['status'] ?? 'ativo';
        
        if(empty($nome) || empty($sigla)) {
            $erro = 'Preencha os campos obrigatórios!';
        } else {
            try {
                $conn = getConexao();
                $stmt = $conn->prepare("INSERT INTO bancos (nome, sigla, telefone, email, site, taxa_base, endereco, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $sigla, $telefone, $email, $site, $taxa, $endereco, $status]);
                $mensagem = 'Banco cadastrado com sucesso!';
            } catch(PDOException $e) {
                $erro = 'Erro ao cadastrar banco!';
            }
        }
    }
    
    if($acao == 'editar') {
        $id = intval($_POST['id'] ?? 0);
        $nome = limparDados($_POST['nome'] ?? '');
        $sigla = limparDados($_POST['sigla'] ?? '');
        $telefone = limparDados($_POST['telefone'] ?? '');
        $email = limparDados($_POST['email'] ?? '');
        $site = limparDados($_POST['site'] ?? '');
        $taxa = floatval($_POST['taxa'] ?? 0);
        $endereco = limparDados($_POST['endereco'] ?? '');
        $status = $_POST['status'] ?? 'ativo';
        
        try {
            $conn = getConexao();
            $stmt = $conn->prepare("UPDATE bancos SET nome=?, sigla=?, telefone=?, email=?, site=?, taxa_base=?, endereco=?, status=? WHERE id_banco=?");
            $stmt->execute([$nome, $sigla, $telefone, $email, $site, $taxa, $endereco, $status, $id]);
            $mensagem = 'Banco atualizado com sucesso!';
        } catch(PDOException $e) {
            $erro = 'Erro ao atualizar banco!';
        }
    }
    
    if($acao == 'excluir') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $conn = getConexao();
            $stmt = $conn->prepare("DELETE FROM bancos WHERE id_banco = ?");
            $stmt->execute([$id]);
            $mensagem = 'Banco excluído com sucesso!';
        } catch(PDOException $e) {
            $erro = 'Erro ao excluir banco!';
        }
    }
}

// Buscar bancos
try {
    $conn = getConexao();
    $bancos = $conn->query("SELECT * FROM bancos ORDER BY nome")->fetchAll();
} catch(PDOException $e) {
    $bancos = [];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Bancos - SGEB Angola</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0a1a3a 0%, #0d1f44 100%); min-height: 100vh; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 30px; }
        
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
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px; }
        .page-header h1 { color: white; font-size: 28px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
        .page-header h1 i { color: #c9a03d; }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            border: none;
        }
        .btn-primary { background: linear-gradient(135deg, #c9a03d, #e6c468); color: #0a1a3a; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 5px 20px rgba(201,160,61,0.4); }
        .btn-outline { background: transparent; border: 2px solid #c9a03d; color: #c9a03d; }
        .btn-icon { background: none; border: none; cursor: pointer; padding: 8px; margin: 0 4px; border-radius: 8px; color: #666; transition: all 0.3s; }
        .btn-icon:hover { background: rgba(201,160,61,0.2); color: #c9a03d; }
        
        .card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 30px;
            border: 1px solid rgba(201,160,61,0.2);
        }
        .card-header {
            padding: 22px 28px;
            background: linear-gradient(135deg, #f8f9fa, #fff);
            border-bottom: 2px solid #c9a03d;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .card-header h2 { font-size: 1.2rem; font-weight: 700; color: #0a1a3a; display: flex; align-items: center; gap: 10px; }
        .card-body { padding: 28px; }
        
        .table-responsive { overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; }
        .table th { text-align: left; padding: 15px; background: #f8f9fa; color: #0a1a3a; font-weight: 600; border-bottom: 2px solid #c9a03d; }
        .table td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .table tr:hover { background: #f5f7fa; }
        
        .badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-ativo { background: #e8f5e9; color: #2e7d32; }
        .badge-inativo { background: #ffebee; color: #c62828; }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 550px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            padding: 20px 25px;
            background: linear-gradient(135deg, #0a1a3a, #0d1f44);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #c9a03d;
        }
        .modal-close { background: none; border: none; color: white; font-size: 24px; cursor: pointer; }
        .modal-body { padding: 25px; }
        .modal-footer { padding: 15px 25px; background: #f8f9fa; display: flex; justify-content: flex-end; gap: 12px; }
        
        .form-group { margin-bottom: 20px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #0a1a3a; font-size: 13px; }
        label i { margin-right: 8px; color: #c9a03d; }
        input, select, textarea { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 14px; }
        input:focus { outline: none; border-color: #c9a03d; }
        
        .alert { padding: 15px 20px; border-radius: 15px; margin-bottom: 20px; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #2e7d32; }
        .alert-error { background: #ffebee; color: #c62828; border-left: 4px solid #c62828; }
        
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
                        <p>GESTÃO DE BANCOS</p>
                    </div>
                </div>
                <nav class="nav-menu">
                    <a href="dashboard.php" class="nav-link"><i class="fas fa-chart-pie"></i> Dashboard</a>
                    <a href="bancos.php" class="nav-link active"><i class="fas fa-university"></i> Bancos</a>
                    <a href="clientes.php" class="nav-link"><i class="fas fa-users"></i> Clientes</a>
                    <a href="emprestimos.php" class="nav-link"><i class="fas fa-hand-holding-usd"></i> Empréstimos</a>
                    <a href="pagamentos.php" class="nav-link"><i class="fas fa-credit-card"></i> Pagamentos</a>
                    <a href="relatorios.php" class="nav-link"><i class="fas fa-chart-line"></i> Relatórios</a>
                    <a href="usuarios.php" class="nav-link"><i class="fas fa-users-gear"></i> Usuários</a>
                    <a href="logout.php" class="nav-link btn-sair"><i class="fas fa-sign-out-alt"></i> Sair</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-university"></i> Gestão de Bancos Parceiros</h1>
                <button class="btn btn-primary" onclick="abrirModalCadastro()"><i class="fas fa-plus-circle"></i> Novo Banco</button>
            </div>

            <?php if($mensagem): ?>
                <div class="alert alert-success" style="display: block;"><?php echo $mensagem; ?></div>
            <?php endif; ?>
            <?php if($erro): ?>
                <div class="alert alert-error" style="display: block;"><?php echo $erro; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Lista de Bancos</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr><th>ID</th><th>Banco</th><th>Sigla</th><th>Telefone</th><th>Email</th><th>Taxa</th><th>Status</th><th>Ações</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($bancos as $banco): ?>
                                <tr>
                                    <td><?php echo $banco['id_banco']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($banco['nome']); ?></strong></td>
                                    <td><?php echo $banco['sigla']; ?></td>
                                    <td><?php echo $banco['telefone'] ?? '-'; ?></td>
                                    <td><?php echo $banco['email'] ?? '-'; ?></td>
                                    <td><span style="color:#c9a03d; font-weight:700;"><?php echo $banco['taxa_base']; ?>%</span></td>
                                    <td><span class="badge <?php echo $banco['status'] == 'ativo' ? 'badge-ativo' : 'badge-inativo'; ?>"><?php echo $banco['status']; ?></span></td>
                                    <td>
                                        <button class="btn-icon" onclick="editarBanco(<?php echo $banco['id_banco']; ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn-icon" onclick="excluirBanco(<?php echo $banco['id_banco']; ?>)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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

    <!-- Modal -->
    <div id="modalBanco" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitulo"><i class="fas fa-plus-circle"></i> Cadastrar Banco</h3>
                <button class="modal-close" onclick="fecharModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formBanco" method="POST">
                    <input type="hidden" name="acao" id="acao" value="cadastrar">
                    <input type="hidden" name="id" id="bancoId" value="">
                    <div class="form-row">
                        <div class="form-group"><label>Nome do Banco *</label><input type="text" name="nome" id="nome" required></div>
                        <div class="form-group"><label>Sigla *</label><input type="text" name="sigla" id="sigla" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Telefone</label><input type="text" name="telefone" id="telefone"></div>
                        <div class="form-group"><label>Email</label><input type="email" name="email" id="email"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Site</label><input type="text" name="site" id="site"></div>
                        <div class="form-group"><label>Taxa Base (%)</label><input type="number" name="taxa" id="taxa" step="0.1" value="2.5"></div>
                    </div>
                    <div class="form-group"><label>Endereço</label><textarea name="endereco" id="endereco" rows="2"></textarea></div>
                    <div class="form-row">
                        <div class="form-group"><label>Status</label><select name="status" id="status"><option value="ativo">Ativo</option><option value="inativo">Inativo</option></select></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="fecharModal()">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function abrirModalCadastro() {
            document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-plus-circle"></i> Cadastrar Banco';
            document.getElementById('acao').value = 'cadastrar';
            document.getElementById('formBanco').reset();
            document.getElementById('bancoId').value = '';
            document.getElementById('modalBanco').classList.add('active');
        }
        
        function editarBanco(id) {
            <?php foreach($bancos as $banco): ?>
            if(id == <?php echo $banco['id_banco']; ?>) {
                document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-edit"></i> Editar Banco';
                document.getElementById('acao').value = 'editar';
                document.getElementById('bancoId').value = <?php echo $banco['id_banco']; ?>;
                document.getElementById('nome').value = '<?php echo addslashes($banco['nome']); ?>';
                document.getElementById('sigla').value = '<?php echo $banco['sigla']; ?>';
                document.getElementById('telefone').value = '<?php echo $banco['telefone']; ?>';
                document.getElementById('email').value = '<?php echo $banco['email']; ?>';
                document.getElementById('site').value = '<?php echo $banco['site']; ?>';
                document.getElementById('taxa').value = '<?php echo $banco['taxa_base']; ?>';
                document.getElementById('endereco').value = '<?php echo addslashes($banco['endereco']); ?>';
                document.getElementById('status').value = '<?php echo $banco['status']; ?>';
                document.getElementById('modalBanco').classList.add('active');
            }
            <?php endforeach; ?>
        }
        
        function excluirBanco(id) {
            if(confirm('Tem certeza que deseja excluir este banco?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function fecharModal() {
            document.getElementById('modalBanco').classList.remove('active');
        }
        
        window.onclick = function(e) {
            if(e.target.classList.contains('modal')) fecharModal();
        }
    </script>

</body>
</html>