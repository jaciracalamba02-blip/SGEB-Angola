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
        $id_cliente = intval($_POST['id_cliente'] ?? 0);
        $id_banco = intval($_POST['id_banco'] ?? 0);
        $valor = floatval($_POST['valor'] ?? 0);
        $taxa = floatval($_POST['taxa'] ?? 0);
        $prazo = intval($_POST['prazo'] ?? 12);
        $finalidade = limparDados($_POST['finalidade'] ?? '');
        $observacoes = limparDados($_POST['observacoes'] ?? '');
        $status = $_POST['status'] ?? 'pendente';
        
        if($id_cliente <= 0 || $id_banco <= 0 || $valor <= 0) {
            $erro = 'Preencha os campos obrigatórios!';
        } else {
            try {
                $sim = simularEmprestimo($valor, $taxa, $prazo);
                $parcela = $sim['parcela'];
                $total = $sim['total'];
                $juros = $sim['juros'];
                
                $conn = getConexao();
                $stmt = $conn->prepare("INSERT INTO emprestimos (id_cliente, id_banco, valor_solicitado, taxa_juros, prazo_meses, valor_parcela, total_pagar, total_juros, finalidade, observacoes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$id_cliente, $id_banco, $valor, $taxa, $prazo, $parcela, $total, $juros, $finalidade, $observacoes, $status]);
                $mensagem = 'Empréstimo cadastrado com sucesso!';
            } catch(PDOException $e) {
                $erro = 'Erro ao cadastrar empréstimo!';
            }
        }
    }
    
    if($acao == 'editar') {
        $id = intval($_POST['id'] ?? 0);
        $id_cliente = intval($_POST['id_cliente'] ?? 0);
        $id_banco = intval($_POST['id_banco'] ?? 0);
        $valor = floatval($_POST['valor'] ?? 0);
        $taxa = floatval($_POST['taxa'] ?? 0);
        $prazo = intval($_POST['prazo'] ?? 12);
        $finalidade = limparDados($_POST['finalidade'] ?? '');
        $observacoes = limparDados($_POST['observacoes'] ?? '');
        $status = $_POST['status'] ?? 'pendente';
        
        try {
            $sim = simularEmprestimo($valor, $taxa, $prazo);
            $parcela = $sim['parcela'];
            $total = $sim['total'];
            $juros = $sim['juros'];
            
            $conn = getConexao();
            $stmt = $conn->prepare("UPDATE emprestimos SET id_cliente=?, id_banco=?, valor_solicitado=?, taxa_juros=?, prazo_meses=?, valor_parcela=?, total_pagar=?, total_juros=?, finalidade=?, observacoes=?, status=? WHERE id_emprestimo=?");
            $stmt->execute([$id_cliente, $id_banco, $valor, $taxa, $prazo, $parcela, $total, $juros, $finalidade, $observacoes, $status, $id]);
            $mensagem = 'Empréstimo atualizado com sucesso!';
        } catch(PDOException $e) {
            $erro = 'Erro ao atualizar empréstimo!';
        }
    }
    
    if($acao == 'excluir') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $conn = getConexao();
            $stmt = $conn->prepare("DELETE FROM emprestimos WHERE id_emprestimo = ?");
            $stmt->execute([$id]);
            $mensagem = 'Empréstimo excluído com sucesso!';
        } catch(PDOException $e) {
            $erro = 'Erro ao excluir empréstimo!';
        }
    }
    
    if($acao == 'aprovar') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $conn = getConexao();
            $stmt = $conn->prepare("UPDATE emprestimos SET status = 'aprovado', data_aprovacao = NOW() WHERE id_emprestimo = ?");
            $stmt->execute([$id]);
            $mensagem = 'Empréstimo aprovado com sucesso!';
        } catch(PDOException $e) {
            $erro = 'Erro ao aprovar empréstimo!';
        }
    }
}

// Buscar dados para os selects
try {
    $conn = getConexao();
    $clientes = $conn->query("SELECT id_cliente, nome FROM clientes WHERE status = 'ativo' ORDER BY nome")->fetchAll();
    $bancos = $conn->query("SELECT id_banco, nome, taxa_base FROM bancos WHERE status = 'ativo' ORDER BY nome")->fetchAll();
    
    // Buscar empréstimos com JOIN
    $sql = "
        SELECT e.*, c.nome as cliente_nome, b.nome as banco_nome, b.sigla
        FROM emprestimos e
        INNER JOIN clientes c ON e.id_cliente = c.id_cliente
        INNER JOIN bancos b ON e.id_banco = b.id_banco
        ORDER BY e.id_emprestimo DESC
    ";
    $emprestimos = $conn->query($sql)->fetchAll();
} catch(PDOException $e) {
    $clientes = [];
    $bancos = [];
    $emprestimos = [];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Empréstimos - SGEB Angola</title>
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
        .btn-success { background: #2e7d32; color: white; }
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
        .badge-pendente { background: #fff3e0; color: #e65100; }
        .badge-aprovado { background: #e8f5e9; color: #2e7d32; }
        .badge-ativo { background: #e3f2fd; color: #1565c0; }
        .badge-quitado { background: #e0e0e0; color: #424242; }
        .badge-inadimplente { background: #ffebee; color: #c62828; }
        
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
            max-width: 700px;
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
            .filters-bar { flex-direction: column; }
            .nav-link { padding: 8px 16px; font-size: 12px; }
            .table th, .table td { padding: 10px; font-size: 12px; }
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
                        <p>GESTÃO DE EMPRÉSTIMOS</p>
                    </div>
                </div>
                <nav class="nav-menu">
                    <a href="dashboard.php" class="nav-link"><i class="fas fa-chart-pie"></i> Dashboard</a>
                    <a href="bancos.php" class="nav-link"><i class="fas fa-university"></i> Bancos</a>
                    <a href="clientes.php" class="nav-link"><i class="fas fa-users"></i> Clientes</a>
                    <a href="emprestimos.php" class="nav-link active"><i class="fas fa-hand-holding-usd"></i> Empréstimos</a>
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
                <h1><i class="fas fa-hand-holding-usd"></i> Gestão de Empréstimos</h1>
                <button class="btn btn-primary" onclick="abrirModalCadastro()"><i class="fas fa-plus-circle"></i> Novo Empréstimo</button>
            </div>

            <?php if($mensagem): ?>
                <div class="alert alert-success" style="display: block;"><?php echo $mensagem; ?></div>
            <?php endif; ?>
            <?php if($erro): ?>
                <div class="alert alert-error" style="display: block;"><?php echo $erro; ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card"><i class="fas fa-chart-line"></i><div class="number" id="totalEmprestimos"><?php echo count($emprestimos); ?></div><p>Total</p></div>
                <div class="stat-card"><i class="fas fa-clock"></i><div class="number" id="totalPendentes"><?php echo count(array_filter($emprestimos, function($e) { return $e['status'] == 'pendente'; })); ?></div><p>Pendentes</p></div>
                <div class="stat-card"><i class="fas fa-check-circle"></i><div class="number" id="totalAtivos"><?php echo count(array_filter($emprestimos, function($e) { return $e['status'] == 'aprovado' || $e['status'] == 'ativo'; })); ?></div><p>Ativos</p></div>
                <div class="stat-card"><i class="fas fa-exclamation-triangle"></i><div class="number" id="totalInadimplentes"><?php echo count(array_filter($emprestimos, function($e) { return $e['status'] == 'inadimplente'; })); ?></div><p>Inadimplentes</p></div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-filter"></i> Filtros</h2>
                </div>
                <div class="card-body">
                    <div class="filters-bar">
                        <div class="filter-group"><label>Cliente</label><input type="text" id="searchCliente" placeholder="Nome do cliente..." onkeyup="filtrarTabela()"></div>
                        <div class="filter-group"><label>Status</label><select id="filterStatus" onchange="filtrarTabela()"><option value="todos">Todos</option><option value="pendente">Pendentes</option><option value="aprovado">Aprovados</option><option value="ativo">Ativos</option><option value="quitado">Quitados</option><option value="inadimplente">Inadimplentes</option></select></div>
                        <div class="filter-group"><button class="btn btn-outline" onclick="limparFiltros()" style="margin-top:24px;"><i class="fas fa-eraser"></i> Limpar</button></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Lista de Empréstimos</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="tabelaEmprestimos">
                            <thead>
                                <tr><th>ID</th><th>Cliente</th><th>Banco</th><th>Valor</th><th>Taxa</th><th>Prazo</th><th>Parcela</th><th>Status</th><th>Data</th><th>Ações</th></tr>
                            </thead>
                            <tbody id="emprestimosTableBody">
                                <?php foreach($emprestimos as $emp): ?>
                                <tr data-status="<?php echo $emp['status']; ?>">
                                    <td>#<?php echo $emp['id_emprestimo']; ?></td>
                                    <td><?php echo htmlspecialchars($emp['cliente_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['banco_nome']); ?> (\(<?php echo $emp['sigla']; ?>)</td>
                                    <td>KZ <?php echo number_format($emp['valor_solicitado'], 2, ',', '.'); ?></td>
                                    <td><?php echo $emp['taxa_juros']; ?>%</td>
                                    <td><?php echo $emp['prazo_meses']; ?> meses</td>
                                    <td>KZ <?php echo number_format($emp['valor_parcela'], 2, ',', '.'); ?></td>
                                    <td><span class="badge badge-<?php echo $emp['status']; ?>">
                                        <?php 
                                            $status_texto = ['pendente'=>'Pendente','aprovado'=>'Aprovado','ativo'=>'Ativo','quitado'=>'Quitado','inadimplente'=>'Inadimplente'];
                                            echo $status_texto[$emp['status']];
                                        ?>
                                    </span></td>
                                    <td><?php echo date('d/m/Y', strtotime($emp['data_solicitacao'])); ?></td>
                                    <td>
                                        <button class="btn-icon" onclick="editarEmprestimo(<?php echo $emp['id_emprestimo']; ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn-icon" onclick="excluirEmprestimo(<?php echo $emp['id_emprestimo']; ?>)"><i class="fas fa-trash"></i></button>
                                        <?php if($emp['status'] == 'pendente'): ?>
                                        <button class="btn-icon" onclick="aprovarEmprestimo(<?php echo $emp['id_emprestimo']; ?>)"><i class="fas fa-check-circle" style="color:#2e7d32;"></i></button>
                                        <?php endif; ?>
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

    <div id="modalEmprestimo" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitulo"><i class="fas fa-plus-circle"></i> Novo Empréstimo</h3>
                <button class="modal-close" onclick="fecharModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formEmprestimo" method="POST">
                    <input type="hidden" name="acao" id="acao" value="cadastrar">
                    <input type="hidden" name="id" id="emprestimoId" value="">
                    <div class="form-row">
                        <div class="form-group"><label>Cliente *</label><select name="id_cliente" id="id_cliente" required>
                            <option value="">-- Selecione --</option>
                            <?php foreach($clientes as $c): ?>
                            <option value="<?php echo $c['id_cliente']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
                            <?php endforeach; ?>
                        </select></div>
                        <div class="form-group"><label>Banco *</label><select name="id_banco" id="id_banco" required>
                            <option value="">-- Selecione --</option>
                            <?php foreach($bancos as $b): ?>
                            <option value="<?php echo $b['id_banco']; ?>" data-taxa="<?php echo $b['taxa_base']; ?>"><?php echo htmlspecialchars($b['nome']); ?> (<?php echo $b['taxa_base']; ?>%)</option>
                            <?php endforeach; ?>
                        </select></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Valor (KZ) *</label><input type="number" name="valor" id="valor" step="0.01" required></div>
                        <div class="form-group"><label>Taxa (%)</label><input type="text" name="taxa" id="taxa" readonly style="background:#f5f5f5;"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Prazo (meses)</label><input type="number" name="prazo" id="prazo" value="12" required></div>
                        <div class="form-group"><label>Valor Parcela</label><input type="text" id="valor_parcela" readonly style="background:#f5f5f5;"></div>
                    </div>
                    <div class="form-group"><label>Finalidade</label><textarea name="finalidade" id="finalidade" rows="2"></textarea></div>
                    <div class="form-group"><label>Observações</label><textarea name="observacoes" id="observacoes" rows="2"></textarea></div>
                    <div class="form-row">
                        <div class="form-group"><label>Status</label><select name="status" id="status"><option value="pendente">Pendente</option><option value="aprovado">Aprovado</option><option value="ativo">Ativo</option><option value="quitado">Quitado</option><option value="inadimplente">Inadimplente</option></select></div>
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
        document.getElementById('id_banco')?.addEventListener('change', function() {
            const taxa = this.options[this.selectedIndex]?.getAttribute('data-taxa');
            document.getElementById('taxa').value = taxa || '';
            calcularParcela();
        });
        
        function calcularParcela() {
            const valor = parseFloat(document.getElementById('valor').value);
            const taxa = parseFloat(document.getElementById('taxa').value);
            const prazo = parseInt(document.getElementById('prazo').value);
            if(valor && taxa && prazo) {
                const tm = taxa / 100;
                const parcela = valor * (tm * Math.pow(1+tm, prazo)) / (Math.pow(1+tm, prazo)-1);
                document.getElementById('valor_parcela').value = `KZ ${parcela.toLocaleString('pt-BR', {minimumFractionDigits:2})}`;
            }
        }
        
        document.getElementById('valor')?.addEventListener('input', calcularParcela);
        document.getElementById('prazo')?.addEventListener('input', calcularParcela);
        
        function abrirModalCadastro() {
            document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-plus-circle"></i> Novo Empréstimo';
            document.getElementById('acao').value = 'cadastrar';
            document.getElementById('formEmprestimo').reset();
            document.getElementById('emprestimoId').value = '';
            document.getElementById('modalEmprestimo').classList.add('active');
        }
        
        function editarEmprestimo(id) {
            <?php foreach($emprestimos as $emp): ?>
            if(id == <?php echo $emp['id_emprestimo']; ?>) {
                document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-edit"></i> Editar Empréstimo';
                document.getElementById('acao').value = 'editar';
                document.getElementById('emprestimoId').value = <?php echo $emp['id_emprestimo']; ?>;
                document.getElementById('id_cliente').value = <?php echo $emp['id_cliente']; ?>;
                document.getElementById('id_banco').value = <?php echo $emp['id_banco']; ?>;
                document.getElementById('valor').value = <?php echo $emp['valor_solicitado']; ?>;
                document.getElementById('taxa').value = <?php echo $emp['taxa_juros']; ?>;
                document.getElementById('prazo').value = <?php echo $emp['prazo_meses']; ?>;
                document.getElementById('finalidade').value = '<?php echo addslashes($emp['finalidade']); ?>';
                document.getElementById('observacoes').value = '<?php echo addslashes($emp['observacoes']); ?>';
                document.getElementById('status').value = '<?php echo $emp['status']; ?>';
                calcularParcela();
                document.getElementById('modalEmprestimo').classList.add('active');
            }
            <?php endforeach; ?>
        }
        
        function excluirEmprestimo(id) {
            if(confirm('Excluir este empréstimo?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function aprovarEmprestimo(id) {
            if(confirm('Aprovar este empréstimo?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="acao" value="aprovar"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function fecharModal() {
            document.getElementById('modalEmprestimo').classList.remove('active');
        }
        
        function filtrarTabela() {
            const search = document.getElementById('searchCliente').value.toLowerCase();
            const status = document.getElementById('filterStatus').value;
            const rows = document.querySelectorAll('#emprestimosTableBody tr');
            rows.forEach(row => {
                const cliente = row.cells[1]?.innerText.toLowerCase() || '';
                const rowStatus = row.getAttribute('data-status');
                const matchSearch = !search || cliente.includes(search);
                const matchStatus = status === 'todos' || rowStatus === status;
                row.style.display = matchSearch && matchStatus ? '' : 'none';
            });
        }
        
        function limparFiltros() {
            document.getElementById('searchCliente').value = '';
            document.getElementById('filterStatus').value = 'todos';
            filtrarTabela();
        }
        
        window.onclick = function(e) {
            if(e.target.classList.contains('modal')) fecharModal();
        }
    </script>

</body>
</html>