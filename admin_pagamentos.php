<?php
session_start();
require_once 'config.php';
require_once 'auth.php';
require_once 'funcões.php';

verificarAdmin();

$mensagem = '';
$erro = '';

// Processar pagamento
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar'])) {
    $id_parcela = intval($_POST['id_parcela'] ?? 0);
    $valor_pago = floatval($_POST['valor_pago'] ?? 0);
    $data_pagamento = $_POST['data_pagamento'] ?? date('Y-m-d');
    $forma_pagamento = $_POST['forma_pagamento'] ?? 'dinheiro';
    
    if($id_parcela <= 0 || $valor_pago <= 0) {
        $erro = 'Dados inválidos para pagamento!';
    } else {
        try {
            $conn = getConexao();
            
            // Buscar parcela
            $stmt = $conn->prepare("SELECT * FROM parcelas WHERE id_parcela = ?");
            $stmt->execute([$id_parcela]);
            $parcela = $stmt->fetch();
            
            if(!$parcela) {
                $erro = 'Parcela não encontrada!';
            } elseif($parcela['status'] == 'pago') {
                $erro = 'Esta parcela já foi paga!';
            } else {
                // Atualizar parcela
                $stmt = $conn->prepare("UPDATE parcelas SET status = 'pago', data_pagamento = ?, valor_pago = ?, forma_pagamento = ? WHERE id_parcela = ?");
                $stmt->execute([$data_pagamento, $valor_pago, $forma_pagamento, $id_parcela]);
                
                // Atualizar valor pago no empréstimo
                $stmt = $conn->prepare("UPDATE emprestimos SET valor_pago = valor_pago + ? WHERE id_emprestimo = ?");
                $stmt->execute([$valor_pago, $parcela['id_emprestimo']]);
                
                // Verificar se todas parcelas foram pagas
                $stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'pago' THEN 1 ELSE 0 END) as pagas FROM parcelas WHERE id_emprestimo = ?");
                $stmt->execute([$parcela['id_emprestimo']]);
                $result = $stmt->fetch();
                
                if($result['total'] == $result['pagas']) {
                    $stmt = $conn->prepare("UPDATE emprestimos SET status = 'quitado' WHERE id_emprestimo = ?");
                    $stmt->execute([$parcela['id_emprestimo']]);
                }
                
                $mensagem = 'Pagamento registrado com sucesso!';
            }
        } catch(PDOException $e) {
            $erro = 'Erro ao registrar pagamento!';
        }
    }
}

// Buscar parcelas com JOIN
try {
    $conn = getConexao();
    $sql = "
        SELECT p.*, e.id_emprestimo, e.valor_solicitado, c.nome as cliente_nome, c.nif as cliente_nif, b.nome as banco_nome
        FROM parcelas p
        INNER JOIN emprestimos e ON p.id_emprestimo = e.id_emprestimo
        INNER JOIN clientes c ON e.id_cliente = c.id_cliente
        INNER JOIN bancos b ON e.id_banco = b.id_banco
        ORDER BY p.data_vencimento ASC
    ";
    $parcelas = $conn->query($sql)->fetchAll();
    
    // Estatísticas
    $totalPrevisto = array_sum(array_column($parcelas, 'valor_parcela'));
    $totalPago = array_sum(array_filter(array_column($parcelas, 'valor_pago'), function($v) { return $v > 0; }));
    $pendentes = count(array_filter($parcelas, function($p) { return $p['status'] == 'pendente' && strtotime($p['data_vencimento']) >= time(); }));
    $atrasados = count(array_filter($parcelas, function($p) { return $p['status'] == 'pendente' && strtotime($p['data_vencimento']) < time(); }));
    
} catch(PDOException $e) {
    $parcelas = [];
    $totalPrevisto = 0;
    $totalPago = 0;
    $pendentes = 0;
    $atrasados = 0;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Pagamentos - SGEB Angola</title>
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
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        
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
        .badge-pago { background: #e8f5e9; color: #2e7d32; }
        .badge-pendente { background: #fff3e0; color: #e65100; }
        .badge-atrasado { background: #ffebee; color: #c62828; }
        
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
                        <p>GESTÃO DE PAGAMENTOS</p>
                    </div>
                </div>
                <nav class="nav-menu">
                    <a href="dashboard.php" class="nav-link"><i class="fas fa-chart-pie"></i> Dashboard</a>
                    <a href="bancos.php" class="nav-link"><i class="fas fa-university"></i> Bancos</a>
                    <a href="clientes.php" class="nav-link"><i class="fas fa-users"></i> Clientes</a>
                    <a href="emprestimos.php" class="nav-link"><i class="fas fa-hand-holding-usd"></i> Empréstimos</a>
                    <a href="pagamentos.php" class="nav-link active"><i class="fas fa-credit-card"></i> Pagamentos</a>
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
                <h1><i class="fas fa-credit-card"></i> Gestão de Pagamentos</h1>
            </div>

            <?php if($mensagem): ?>
                <div class="alert alert-success" style="display: block;"><?php echo $mensagem; ?></div>
            <?php endif; ?>
            <?php if($erro): ?>
                <div class="alert alert-error" style="display: block;"><?php echo $erro; ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card"><i class="fas fa-chart-line"></i><div class="number">KZ <?php echo number_format($totalPrevisto, 2, ',', '.'); ?></div><p>Total Previsto</p></div>
                <div class="stat-card"><i class="fas fa-check-circle"></i><div class="number">KZ <?php echo number_format($totalPago, 2, ',', '.'); ?></div><p>Total Recebido</p></div>
                <div class="stat-card"><i class="fas fa-clock"></i><div class="number"><?php echo $pendentes; ?></div><p>Pendentes</p></div>
                <div class="stat-card"><i class="fas fa-exclamation-triangle"></i><div class="number"><?php echo $atrasados; ?></div><p>Atrasados</p></div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-filter"></i> Filtros</h2>
                </div>
                <div class="card-body">
                    <div class="filters-bar">
                        <div class="filter-group"><label>Cliente</label><input type="text" id="searchCliente" placeholder="Nome do cliente..." onkeyup="filtrarTabela()"></div>
                        <div class="filter-group"><label>Status</label><select id="filterStatus" onchange="filtrarTabela()"><option value="todos">Todos</option><option value="pendente">Pendentes</option><option value="atrasado">Atrasados</option><option value="pago">Pagos</option></select></div>
                        <div class="filter-group"><button class="btn btn-outline" onclick="limparFiltros()" style="margin-top:24px;"><i class="fas fa-eraser"></i> Limpar</button></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Lista de Parcelas</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="tabelaParcelas">
                            <thead>
                                <tr><th>ID</th><th>Cliente</th><th>Empréstimo</th><th>Parcela</th><th>Valor</th><th>Vencimento</th><th>Status</th><th>Ações</th></tr>
                            </thead>
                            <tbody id="parcelasTableBody">
                                <?php foreach($parcelas as $p): ?>
                                <tr data-status="<?php echo $p['status']; ?>" data-cliente="<?php echo strtolower($p['cliente_nome']); ?>">
                                    <td>#<?php echo $p['id_parcela']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($p['cliente_nome']); ?></strong><br><small><?php echo $p['cliente_nif']; ?></small></td>
                                    <td>#<?php echo $p['id_emprestimo']; ?></td>
                                    <td><?php echo $p['numero_parcela']; ?>ª parcela</td>
                                    <td>KZ <?php echo number_format($p['valor_parcela'], 2, ',', '.'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($p['data_vencimento'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $p['status']; ?>">
                                            <?php echo $p['status'] == 'pago' ? 'Pago' : (strtotime($p['data_vencimento']) < time() ? 'Atrasado' : 'Pendente'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($p['status'] != 'pago'): ?>
                                        <button class="btn btn-sm btn-primary" onclick="abrirModalPagamento(<?php echo $p['id_parcela']; ?>, <?php echo $p['valor_parcela']; ?>)"><i class="fas fa-money-bill"></i> Pagar</button>
                                        <?php else: ?>
                                        <button class="btn btn-sm btn-outline" onclick="verComprovante(<?php echo $p['id_parcela']; ?>)"><i class="fas fa-receipt"></i> Comprovante</button>
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

    <div id="modalPagamento" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-receipt"></i> Registrar Pagamento</h3>
                <button class="modal-close" onclick="fecharModalPagamento()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="id_parcela" id="id_parcela">
                    <div class="form-group"><label>Valor da Parcela</label><input type="text" id="valor_parcela_display" class="form-control" readonly style="background:#f5f5f5;"></div>
                    <div class="form-group"><label>Valor a Pagar (KZ)</label><input type="number" name="valor_pago" id="valor_pago" step="0.01" class="form-control" required></div>
                    <div class="form-group"><label>Data do Pagamento</label><input type="date" name="data_pagamento" value="<?php echo date('Y-m-d'); ?>" class="form-control" required></div>
                    <div class="form-group"><label>Forma de Pagamento</label><select name="forma_pagamento" class="form-control"><option value="dinheiro">Dinheiro</option><option value="transferencia">Transferência Bancária</option><option value="cartao">Cartão</option><option value="cheque">Cheque</option></select></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="fecharModalPagamento()">Cancelar</button>
                        <button type="submit" name="registrar" class="btn btn-success">Registrar Pagamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modalComprovante" class="modal">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #2e7d32, #43a047);">
                <h3><i class="fas fa-print"></i> Comprovante de Pagamento</h3>
                <button class="modal-close" onclick="fecharModalComprovante()">&times;</button>
            </div>
            <div class="modal-body" id="comprovanteConteudo"></div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="imprimirComprovante()"><i class="fas fa-print"></i> Imprimir</button>
                <button class="btn btn-outline" onclick="fecharModalComprovante()">Fechar</button>
            </div>
        </div>
    </div>

    <script>
        function filtrarTabela() {
            const search = document.getElementById('searchCliente').value.toLowerCase();
            const status = document.getElementById('filterStatus').value;
            const rows = document.querySelectorAll('#parcelasTableBody tr');
            rows.forEach(row => {
                const cliente = row.getAttribute('data-cliente') || '';
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
        
        let parcelaSelecionada = null;
        
        function abrirModalPagamento(id, valor) {
            document.getElementById('id_parcela').value = id;
            document.getElementById('valor_parcela_display').value = `KZ ${valor.toLocaleString('pt-BR', {minimumFractionDigits:2})}`;
            document.getElementById('valor_pago').value = valor;
            document.getElementById('modalPagamento').classList.add('active');
        }
        
        function fecharModalPagamento() {
            document.getElementById('modalPagamento').classList.remove('active');
        }
        
        function verComprovante(id) {
            <?php foreach($parcelas as $p): ?>
            if(id == <?php echo $p['id_parcela']; ?>) {
                document.getElementById('comprovanteConteudo').innerHTML = `
                    <div style="text-align:center;">
                        <i class="fas fa-landmark" style="font-size:50px; color:#0a1a3a;"></i>
                        <h2>SGEB Angola</h2>
                        <h3>COMPROVANTE DE PAGAMENTO</h3>
                        <hr>
                        <p><strong>Data:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
                        <p><strong>Cliente:</strong> <?php echo addslashes($p['cliente_nome']); ?></p>
                        <p><strong>NIF:</strong> <?php echo $p['cliente_nif']; ?></p>
                        <p><strong>Empréstimo:</strong> #<?php echo $p['id_emprestimo']; ?></p>
                        <p><strong>Parcela:</strong> <?php echo $p['numero_parcela']; ?>ª</p>
                        <p><strong>Valor Pago:</strong> KZ <?php echo number_format($p['valor_pago'] ?: $p['valor_parcela'], 2, ',', '.'); ?></p>
                        <p><strong>Data Pagamento:</strong> <?php echo date('d/m/Y', strtotime($p['data_pagamento'] ?: 'now')); ?></p>
                        <hr>
                        <p>Obrigado pela preferência!</p>
                    </div>
                `;
                document.getElementById('modalComprovante').classList.add('active');
            }
            <?php endforeach; ?>
        }
        
        function fecharModalComprovante() {
            document.getElementById('modalComprovante').classList.remove('active');
        }
        
        function imprimirComprovante() {
            const conteudo = document.getElementById('comprovanteConteudo').innerHTML;
            const win = window.open('', '_blank');
            win.document.write('<html><head><title>Comprovante</title><style>body{font-family:Arial;padding:20px;}</style></head><body>' + conteudo + '</body></html>');
            win.document.close();
            win.print();
        }
        
        window.onclick = function(e) {
            if(e.target.classList.contains('modal')) {
                fecharModalPagamento();
                fecharModalComprovante();
            }
        }
    </script>

</body>
</html>