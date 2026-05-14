<?php
session_start();
require_once 'config.php';
require_once 'auth.php';
require_once 'funcões.php';

verificarAdmin();

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
            $erro = 'Preencha os campos obrigatórios!';
        } elseif(strlen($senha) < 4) {
            $erro = 'A senha deve ter no mínimo 4 caracteres!';
        } else {
            try {
                $conn = getConexao();
                $stmt = $conn->prepare("INSERT INTO usuarios (nome, usuario, email, senha, nivel, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $usuario, $email, $senha, $nivel, $status]);
                $mensagem = 'Usuário cadastrado com sucesso!';
            } catch(PDOException $e) {
                $erro = 'Erro ao cadastrar. Usuário ou e-mail já existe!';
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
        $senha = $_POST['senha'] ?? '';
        
        try {
            $conn = getConexao();
            if(!empty($senha)) {
                $stmt = $conn->prepare("UPDATE usuarios SET nome=?, usuario=?, email=?, senha=?, nivel=?, status=? WHERE id_usuario=?");
                $stmt->execute([$nome, $usuario, $email, $senha, $nivel, $status, $id]);
            } else {
                $stmt = $conn->prepare("UPDATE usuarios SET nome=?, usuario=?, email=?, nivel=?, status=? WHERE id_usuario=?");
                $stmt->execute([$nome, $usuario, $email, $nivel, $status, $id]);
            }
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
    $usuarios = $conn->query("SELECT * FROM usuarios ORDER BY id_usuario")->fetchAll();
} catch(PDOException $e) {
    $usuarios = [];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários - SGEB Angola</title>
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
        .btn-danger { background: #c62828; color: white; }
        .btn-warning { background: #f57c00; color: white; }
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
        .badge-admin { background: linear-gradient(135deg, #c9a03d, #e6c468); color: #0a1a3a; }
        .badge-gerente { background: #e3f2fd; color: #1565c0; }
        .badge-operador { background: #e8f5e9; color: #2e7d32; }
        .badge-ativo { background: #e8f5e9; color: #2e7d32; }
        .badge-inativo { background: #ffebee; color: #c62828; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; border-radius: 15px; padding: 20px; text-align: center; border-left: 4px solid #c9a03d; }
        .stat-card .number { font-size: 28px; font-weight: 700; color: #0a1a3a; }
        
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
        input, select { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 14px; }
        
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
                        <p>GESTÃO DE USUÁRIOS</p>
                    </div>
                </div>
                <nav class="nav-menu">
                    <a href="dashboard.php" class="nav-link"><i class="fas fa-chart-pie"></i> Dashboard</a>
                    <a href="bancos.php" class="nav-link"><i class="fas fa-university"></i> Bancos</a>
                    <a href="clientes.php" class="nav-link"><i class="fas fa-users"></i> Clientes</a>
                    <a href="emprestimos.php" class="nav-link"><i class="fas fa-hand-holding-usd"></i> Empréstimos</a>
                    <a href="pagamentos.php" class="nav-link"><i class="fas fa-credit-card"></i> Pagamentos</a>
                    <a href="relatorios.php" class="nav-link"><i class="fas fa-chart-line"></i> Relatórios</a>
                    <a href="usuarios.php" class="nav-link active"><i class="fas fa-users-gear"></i> Usuários</a>
                    <a href="logout.php" class="nav-link btn-sair"><i class="fas fa-sign-out-alt"></i> Sair</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-users-gear"></i> Gestão de Usuários</h1>
                <button class="btn btn-primary" onclick="abrirModalCadastro()"><i class="fas fa-user-plus"></i> Novo Usuário</button>
            </div>

            <?php if($mensagem): ?>
                <div class="alert alert-success" style="display: block;"><?php echo $mensagem; ?></div>
            <?php endif; ?>
            <?php if($erro): ?>
                <div class="alert alert-error" style="display: block;"><?php echo $erro; ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card"><i class="fas fa-users"></i><div class="number"><?php echo count($usuarios); ?></div><p>Total</p></div>
                <div class="stat-card"><i class="fas fa-user-check"></i><div class="number"><?php echo count(array_filter($usuarios, function($u) { return $u['status'] == 'ativo'; })); ?></div><p>Ativos</p></div>
                <div class="stat-card"><i class="fas fa-user-shield"></i><div class="number"><?php echo count(array_filter($usuarios, function($u) { return $u['nivel'] == 'admin'; })); ?></div><p>Administradores</p></div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-filter"></i> Filtros</h2>
                </div>
                <div class="card-body">
                    <div class="filters-bar">
                        <div class="filter-group"><label>Nome ou Usuário</label><input type="text" id="searchUsuario" placeholder="Buscar..." onkeyup="filtrarUsuarios()"></div>
                        <div class="filter-group"><label>Nível</label><select id="filterNivel" onchange="filtrarUsuarios()"><option value="todos">Todos</option><option value="admin">Admin</option><option value="gerente">Gerente</option><option value="operador">Operador</option></select></div>
                        <div class="filter-group"><label>Status</label><select id="filterStatus" onchange="filtrarUsuarios()"><option value="todos">Todos</option><option value="ativo">Ativo</option><option value="inativo">Inativo</option></select></div>
                        <div class="filter-group"><button class="btn btn-outline" onclick="limparFiltros()" style="margin-top:24px;"><i class="fas fa-eraser"></i> Limpar</button></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Lista de Usuários</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="tabelaUsuarios">
                            <thead>
                                <tr><th>ID</th><th>Usuário</th><th>Nome</th><th>Email</th><th>Nível</th><th>Status</th><th>Ações</th></tr>
                            </thead>
                            <tbody id="usuariosTableBody">
                                <?php foreach($usuarios as $u): ?>
                                <tr data-nivel="<?php echo $u['nivel']; ?>" data-status="<?php echo $u['status']; ?>">
                                    <td><?php echo $u['id_usuario']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($u['usuario']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($u['nome']); ?></td>
                                    <td><?php echo $u['email']; ?></td>
                                    <td><span class="badge badge-<?php echo $u['nivel']; ?>"><?php echo ucfirst($u['nivel']); ?></span></td>
                                    <td><span class="badge badge-<?php echo $u['status']; ?>"><?php echo ucfirst($u['status']); ?></span></td>
                                    <td>
                                        <button class="btn-icon" onclick="editarUsuario(<?php echo $u['id_usuario']; ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn-icon" onclick="abrirResetSenha(<?php echo $u['id_usuario']; ?>)"><i class="fas fa-key"></i></button>
                                        <button class="btn-icon" onclick="excluirUsuario(<?php echo $u['id_usuario']; ?>)"><i class="fas fa-trash" style="color:#c62828;"></i></button>
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

    <div id="modalUsuario" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitulo"><i class="fas fa-user-plus"></i> Cadastrar Usuário</h3>
                <button class="modal-close" onclick="fecharModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formUsuario" method="POST">
                    <input type="hidden" name="acao" id="acao" value="cadastrar">
                    <input type="hidden" name="id" id="usuarioId" value="">
                    <div class="form-row">
                        <div class="form-group"><label>Nome Completo *</label><input type="text" name="nome" id="nome" required></div>
                        <div class="form-group"><label>Usuário *</label><input type="text" name="usuario" id="usuario" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>E-mail *</label><input type="email" name="email" id="email" required></div>
                    </div>
                    <div class="form-row" id="senhaRow">
                        <div class="form-group"><label id="senhaLabel">Senha *</label><input type="password" name="senha" id="senha"></div>
                        <div class="form-group"><label id="confirmarLabel">Confirmar Senha *</label><input type="password" id="confirmarSenha"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Nível</label><select name="nivel" id="nivel"><option value="operador">Operador</option><option value="gerente">Gerente</option><option value="admin">Administrador</option></select></div>
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

    <div id="modalResetSenha" class="modal">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #f57c00, #ff9800);">
                <h3><i class="fas fa-key"></i> Resetar Senha</h3>
                <button class="modal-close" onclick="fecharModalReset()">&times;</button>
            </div>
            <div class="modal-body">
                <div style="background:#fff3e0; padding:15px; border-radius:12px; margin-bottom:20px;">
                    <i class="fas fa-exclamation-triangle"></i> Tem certeza que deseja resetar a senha?
                </div>
                <input type="hidden" name="id" id="resetId" value="">
                <div class="form-group"><label>Nova Senha</label><input type="password" name="nova_senha" id="novaSenha" class="form-control" placeholder="Mínimo 4 caracteres"></div>
                <div class="form-group"><label>Confirmar Nova Senha</label><input type="password" id="confirmarNovaSenha" class="form-control"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="fecharModalReset()">Cancelar</button>
                    <button type="button" class="btn btn-warning" onclick="confirmarResetSenha()">Resetar Senha</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function filtrarUsuarios() {
            const search = document.getElementById('searchUsuario').value.toLowerCase();
            const nivel = document.getElementById('filterNivel').value;
            const status = document.getElementById('filterStatus').value;
            const rows = document.querySelectorAll('#usuariosTableBody tr');
            rows.forEach(row => {
                const nome = row.cells[2]?.innerText.toLowerCase() || '';
                const usuario = row.cells[1]?.innerText.toLowerCase() || '';
                const rowNivel = row.getAttribute('data-nivel');
                const rowStatus = row.getAttribute('data-status');
                const matchSearch = !search || nome.includes(search) || usuario.includes(search);
                const matchNivel = nivel === 'todos' || rowNivel === nivel;
                const matchStatus = status === 'todos' || rowStatus === status;
                row.style.display = matchSearch && matchNivel && matchStatus ? '' : 'none';
            });
        }
        
        function limparFiltros() {
            document.getElementById('searchUsuario').value = '';
            document.getElementById('filterNivel').value = 'todos';
            document.getElementById('filterStatus').value = 'todos';
            filtrarUsuarios();
        }
        
        let resetandoId = null;
        
        function abrirResetSenha(id) {
            resetandoId = id;
            document.getElementById('novaSenha').value = '';
            document.getElementById('confirmarNovaSenha').value = '';
            document.getElementById('modalResetSenha').classList.add('active');
        }
        
        function confirmarResetSenha() {
            const senha = document.getElementById('novaSenha').value;
            const confirmar = document.getElementById('confirmarNovaSenha').value;
            if(!senha || senha !== confirmar) { alert('Senhas não coincidem!'); return; }
            if(senha.length < 4) { alert('Mínimo 4 caracteres!'); return; }
            
            var form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="acao" value="reset_senha"><input type="hidden" name="id" value="' + resetandoId + '"><input type="hidden" name="nova_senha" value="' + senha + '">';
            document.body.appendChild(form);
            form.submit();
        }
        
        function fecharModalReset() {
            document.getElementById('modalResetSenha').classList.remove('active');
        }
        
        function abrirModalCadastro() {
            document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-user-plus"></i> Cadastrar Usuário';
            document.getElementById('acao').value = 'cadastrar';
            document.getElementById('formUsuario').reset();
            document.getElementById('usuarioId').value = '';
            document.getElementById('senhaLabel').innerHTML = 'Senha *';
            document.getElementById('confirmarLabel').innerHTML = 'Confirmar Senha *';
            document.getElementById('senha').required = true;
            document.getElementById('confirmarSenha').required = true;
            document.getElementById('senhaRow').style.display = 'grid';
            document.getElementById('modalUsuario').classList.add('active');
        }
        
        function editarUsuario(id) {
            <?php foreach($usuarios as $u): ?>
            if(id == <?php echo $u['id_usuario']; ?>) {
                document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-edit"></i> Editar Usuário';
                document.getElementById('acao').value = 'editar';
                document.getElementById('usuarioId').value = <?php echo $u['id_usuario']; ?>;
                document.getElementById('nome').value = '<?php echo addslashes($u['nome']); ?>';
                document.getElementById('usuario').value = '<?php echo $u['usuario']; ?>';
                document.getElementById('email').value = '<?php echo $u['email']; ?>';
                document.getElementById('nivel').value = '<?php echo $u['nivel']; ?>';
                document.getElementById('status').value = '<?php echo $u['status']; ?>';
                document.getElementById('senhaLabel').innerHTML = 'Senha (deixe em branco para manter)';
                document.getElementById('confirmarLabel').innerHTML = 'Confirmar Senha';
                document.getElementById('senha').required = false;
                document.getElementById('confirmarSenha').required = false;
                document.getElementById('senha').value = '';
                document.getElementById('confirmarSenha').value = '';
                document.getElementById('modalUsuario').classList.add('active');
            }
            <?php endforeach; ?>
        }
        
        function fecharModal() {
            document.getElementById('modalUsuario').classList.remove('active');
        }
        
        function excluirUsuario(id) {
            if(confirm('Excluir este usuário?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        window.onclick = function(e) {
            if(e.target.classList.contains('modal')) {
                fecharModal();
                fecharModalReset();
            }
        }
    </script>

</body>
</html>