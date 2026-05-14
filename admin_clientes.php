<?php
session_start();
require_once 'config.php';
require_once 'auth.php';
require_once 'funcões.php';

verificarAdmin();

$mensagem = '';
$erro = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if($acao == 'cadastrar') {
        $nome = limparDados($_POST['nome'] ?? '');
        $nif = limparDados($_POST['nif'] ?? '');
        $email = limparDados($_POST['email'] ?? '');
        $telefone = limparDados($_POST['telefone'] ?? '');
        $usuario = limparDados($_POST['usuario'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $profissao = limparDados($_POST['profissao'] ?? '');
        $endereco = limparDados($_POST['endereco'] ?? '');
        $renda = floatval($_POST['renda'] ?? 0);
        $status = $_POST['status'] ?? 'ativo';
        
        if(empty($nome) || empty($nif) || empty($email) || empty($telefone) || empty($usuario)) {
            $erro = 'Preencha os campos obrigatórios!';
        } elseif(!validarNIF($nif)) {
            $erro = 'NIF inválido! Deve ter 9 dígitos.';
        } elseif(!validarEmail($email)) {
            $erro = 'E-mail inválido!';
        } elseif(empty($senha)) {
            $erro = 'A senha é obrigatória!';
        } elseif(strlen($senha) < 4) {
            $erro = 'A senha deve ter no mínimo 4 caracteres!';
        } else {
            try {
                $conn = getConexao();
                $stmt = $conn->prepare("INSERT INTO clientes (nome, nif, email, telefone, usuario, senha, profissao, endereco, renda_mensal, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $nif, $email, $telefone, $usuario, $senha, $profissao, $endereco, $renda, $status]);
                $mensagem = 'Cliente cadastrado com sucesso!';
            } catch(PDOException $e) {
                $erro = 'Erro ao cadastrar. E-mail ou usuário já existe!';
            }
        }
    }
    
    if($acao == 'editar') {
        $id = intval($_POST['id'] ?? 0);
        $nome = limparDados($_POST['nome'] ?? '');
        $nif = limparDados($_POST['nif'] ?? '');
        $email = limparDados($_POST['email'] ?? '');
        $telefone = limparDados($_POST['telefone'] ?? '');
        $usuario = limparDados($_POST['usuario'] ?? '');
        $profissao = limparDados($_POST['profissao'] ?? '');
        $endereco = limparDados($_POST['endereco'] ?? '');
        $renda = floatval($_POST['renda'] ?? 0);
        $status = $_POST['status'] ?? 'ativo';
        $senha = $_POST['senha'] ?? '';
        
        try {
            $conn = getConexao();
            if(!empty($senha)) {
                $stmt = $conn->prepare("UPDATE clientes SET nome=?, nif=?, email=?, telefone=?, usuario=?, senha=?, profissao=?, endereco=?, renda_mensal=?, status=? WHERE id_cliente=?");
                $stmt->execute([$nome, $nif, $email, $telefone, $usuario, $senha, $profissao, $endereco, $renda, $status, $id]);
            } else {
                $stmt = $conn->prepare("UPDATE clientes SET nome=?, nif=?, email=?, telefone=?, usuario=?, profissao=?, endereco=?, renda_mensal=?, status=? WHERE id_cliente=?");
                $stmt->execute([$nome, $nif, $email, $telefone, $usuario, $profissao, $endereco, $renda, $status, $id]);
            }
            $mensagem = 'Cliente atualizado com sucesso!';
        } catch(PDOException $e) {
            $erro = 'Erro ao atualizar cliente!';
        }
    }
    
    if($acao == 'excluir') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $conn = getConexao();
            $stmt = $conn->prepare("DELETE FROM clientes WHERE id_cliente = ?");
            $stmt->execute([$id]);
            $mensagem = 'Cliente excluído com sucesso!';
        } catch(PDOException $e) {
            $erro = 'Erro ao excluir cliente!';
        }
    }
}

try {
    $conn = getConexao();
    $clientes = $conn->query("SELECT * FROM clientes ORDER BY id_cliente DESC")->fetchAll();
} catch(PDOException $e) {
    $clientes = [];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Clientes - SGEB Angola</title>
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
        
        .filters-bar { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 25px; align-items: flex-end; }
        .filter-group { flex: 1; min-width: 180px; }
        .filter-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #0a1a3a; font-size: 12px; }
        .filter-group input, .filter-group select { width: 100%; padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 14px; }
        
        .table-responsive { overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; }
        .table th { text-align: left; padding: 15px; background: #f8f9fa; color: #0a1a3a; font-weight: 600; border-bottom: 2px solid #c9a03d; }
        .table td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
        
        .badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-ativo { background: #e8f5e9; color: #2e7d32; }
        .badge-inativo { background: #ffebee; color: #c62828; }
        .badge-bloqueado { background: #fff3e0; color: #e65100; }
        
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
            max-width: 650px;
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
        input, select, textarea { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 14px; }
        
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
            .page-header { flex-direction: column; text-align: center; }
            .filters-bar { flex-direction: column; }
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
                        <p>GESTÃO DE CLIENTES</p>
                    </div>
                </div>
                <nav class="nav-menu">
                    <a href="dashboard.php" class="nav-link"><i class="fas fa-chart-pie"></i> Dashboard</a>
                    <a href="bancos.php" class="nav-link"><i class="fas fa-university"></i> Bancos</a>
                    <a href="clientes.php" class="nav-link active"><i class="fas fa-users"></i> Clientes</a>
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
                <h1><i class="fas fa-users"></i> Gestão de Clientes</h1>
                <button class="btn btn-primary" onclick="abrirModalCadastro()"><i class="fas fa-user-plus"></i> Novo Cliente</button>
            </div>

            <?php if($mensagem): ?>
                <div class="alert alert-success" style="display: block;"><?php echo $mensagem; ?></div>
            <?php endif; ?>
            <?php if($erro): ?>
                <div class="alert alert-error" style="display: block;"><?php echo $erro; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-filter"></i> Filtros</h2>
                </div>
                <div class="card-body">
                    <div class="filters-bar">
                        <div class="filter-group"><label>Nome ou NIF</label><input type="text" id="searchCliente" placeholder="Digite o nome ou NIF..." onkeyup="filtrarClientes()"></div>
                        <div class="filter-group"><label>Status</label><select id="filterStatus" onchange="filtrarClientes()"><option value="todos">Todos</option><option value="ativo">Ativos</option><option value="inativo">Inativos</option><option value="bloqueado">Bloqueados</option></select></div>
                        <div class="filter-group"><button class="btn btn-outline" onclick="limparFiltros()" style="margin-top:24px;"><i class="fas fa-eraser"></i> Limpar</button></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Lista de Clientes</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="tabelaClientes">
                            <thead>
                                <tr><th>ID</th><th>Nome</th><th>NIF</th><th>Telefone</th><th>Email</th><th>Profissão</th><th>Renda</th><th>Status</th><th>Ações</th></tr>
                            </thead>
                            <tbody id="clientesTableBody">
                                <?php foreach($clientes as $cliente): ?>
                                <tr data-status="<?php echo $cliente['status']; ?>">
                                    </table><?php echo $cliente['id_cliente']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($cliente['nome']); ?></strong></td>
                                    <td><?php echo $cliente['nif']; ?></td>
                                    <td><?php echo $cliente['telefone']; ?></td>
                                    <td><?php echo $cliente['email']; ?></td>
                                    <td><?php echo $cliente['profissao'] ?? '-'; ?></td>
                                    <td>KZ <?php echo number_format($cliente['renda_mensal'], 2, ',', '.'); ?></td>
                                    <td><span class="badge <?php echo $cliente['status'] == 'ativo' ? 'badge-ativo' : ($cliente['status'] == 'inativo' ? 'badge-inativo' : 'badge-bloqueado'); ?>"><?php echo $cliente['status']; ?></span></td>
                                    <td>
                                        <button class="btn-icon" onclick="editarCliente(<?php echo $cliente['id_cliente']; ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn-icon" onclick="excluirCliente(<?php echo $cliente['id_cliente']; ?>)"><i class="fas fa-trash"></i></button>
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

    <div id="modalCliente" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitulo"><i class="fas fa-user-plus"></i> Cadastrar Cliente</h3>
                <button class="modal-close" onclick="fecharModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formCliente" method="POST">
                    <input type="hidden" name="acao" id="acao" value="cadastrar">
                    <input type="hidden" name="id" id="clienteId" value="">
                    <div class="form-row">
                        <div class="form-group"><label>Nome Completo *</label><input type="text" name="nome" id="nome" required></div>
                        <div class="form-group"><label>NIF *</label><input type="text" name="nif" id="nif" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Email *</label><input type="email" name="email" id="email" required></div>
                        <div class="form-group"><label>Telefone *</label><input type="text" name="telefone" id="telefone" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Usuário *</label><input type="text" name="usuario" id="usuario" required></div>
                        <div class="form-group"><label>Senha</label><input type="password" name="senha" id="senha"><small>Mínimo 4 caracteres</small></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Profissão</label><input type="text" name="profissao" id="profissao"></div>
                        <div class="form-group"><label>Renda Mensal (KZ)</label><input type="number" name="renda" id="renda" step="0.01"></div>
                    </div>
                    <div class="form-group"><label>Endereço</label><textarea name="endereco" id="endereco" rows="2"></textarea></div>
                    <div class="form-row">
                        <div class="form-group"><label>Status</label><select name="status" id="status"><option value="ativo">Ativo</option><option value="inativo">Inativo</option><option value="bloqueado">Bloqueado</option></select></div>
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
        function filtrarClientes() {
            const search = document.getElementById('searchCliente').value.toLowerCase();
            const status = document.getElementById('filterStatus').value;
            const rows = document.querySelectorAll('#clientesTableBody tr');
            rows.forEach(row => {
                const nome = row.cells[1]?.innerText.toLowerCase() || '';
                const nif = row.cells[2]?.innerText.toLowerCase() || '';
                const rowStatus = row.getAttribute('data-status');
                const matchSearch = nome.includes(search) || nif.includes(search);
                const matchStatus = status === 'todos' || rowStatus === status;
                row.style.display = matchSearch && matchStatus ? '' : 'none';
            });
        }
        
        function limparFiltros() {
            document.getElementById('searchCliente').value = '';
            document.getElementById('filterStatus').value = 'todos';
            filtrarClientes();
        }
        
        function abrirModalCadastro() {
            document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-user-plus"></i> Cadastrar Cliente';
            document.getElementById('acao').value = 'cadastrar';
            document.getElementById('formCliente').reset();
            document.getElementById('clienteId').value = '';
            document.getElementById('senha').required = true;
            document.getElementById('modalCliente').classList.add('active');
        }
        
        function editarCliente(id) {
            <?php foreach($clientes as $cliente): ?>
            if(id == <?php echo $cliente['id_cliente']; ?>) {
                document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-edit"></i> Editar Cliente';
                document.getElementById('acao').value = 'editar';
                document.getElementById('clienteId').value = <?php echo $cliente['id_cliente']; ?>;
                document.getElementById('nome').value = '<?php echo addslashes($cliente['nome']); ?>';
                document.getElementById('nif').value = '<?php echo $cliente['nif']; ?>';
                document.getElementById('email').value = '<?php echo $cliente['email']; ?>';
                document.getElementById('telefone').value = '<?php echo $cliente['telefone']; ?>';
                document.getElementById('usuario').value = '<?php echo $cliente['usuario']; ?>';
                document.getElementById('profissao').value = '<?php echo addslashes($cliente['profissao']); ?>';
                document.getElementById('endereco').value = '<?php echo addslashes($cliente['endereco']); ?>';
                document.getElementById('renda').value = '<?php echo $cliente['renda_mensal']; ?>';
                document.getElementById('status').value = '<?php echo $cliente['status']; ?>';
                document.getElementById('senha').required = false;
                document.getElementById('senha').value = '';
                document.getElementById('modalCliente').classList.add('active');
            }
            <?php endforeach; ?>
        }
        
        function excluirCliente(id) {
            if(confirm('Excluir este cliente?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function fecharModal() {
            document.getElementById('modalCliente').classList.remove('active');
        }
        
        window.onclick = function(e) {
            if(e.target.classList.contains('modal')) fecharModal();
        }
    </script>

</body>
</html>