<?php
session_start();
require_once 'config.php';
require_once 'auth.php';
require_once 'funções.php';

verificarAdmin();

// Buscar dados para relatórios
try {
    $conn = getConexao();
    
    // Resumo de empréstimos por status
    $sql = "SELECT status, COUNT(*) as total, SUM(valor_solicitado) as valor FROM emprestimos GROUP BY status";
    $statusStats = $conn->query($sql)->fetchAll();
    
    // Empréstimos por mês
    $sql = "
        SELECT DATE_FORMAT(data_solicitacao, '%Y-%m') as mes, 
               COUNT(*) as total, 
               SUM(valor_solicitado) as valor 
        FROM emprestimos 
        GROUP BY DATE_FORMAT(data_solicitacao, '%Y-%m')
        ORDER BY mes DESC LIMIT 12
    ";
    $mensalStats = $conn->query($sql)->fetchAll();
    
    // Pagamentos por mês
    $sql = "
        SELECT DATE_FORMAT(data_pagamento, '%Y-%m') as mes, 
               COUNT(*) as total, 
               SUM(valor_pago) as valor 
        FROM parcelas 
        WHERE status = 'pago' AND data_pagamento IS NOT NULL
        GROUP BY DATE_FORMAT(data_pagamento, '%Y-%m')
        ORDER BY mes DESC LIMIT 12
    ";
    $pagamentosStats = $conn->query($sql)->fetchAll();
    
    // Top bancos
    $sql = "
        SELECT b.nome, COUNT(e.id_emprestimo) as total, SUM(e.valor_solicitado) as valor
        FROM emprestimos e
        INNER JOIN bancos b ON e.id_banco = b.id_banco
        GROUP BY b.id_banco
        ORDER BY valor DESC
    ";
    $bancosStats = $conn->query($sql)->fetchAll();
    
} catch(PDOException $e) {
    $statusStats = [];
    $mensalStats = [];
    $pagamentosStats = [];
    $bancosStats = [];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - SGEB Angola</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .page-header { margin-bottom: 30px; }
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
        .btn-outline { background: transparent; border: 2px solid #c9a03d; color: #c9a03d; }
        .btn-group { display: flex; gap: 12px; flex-wrap: wrap; }
        
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
        
        .filters-bar { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 20px; }
        .filter-group { flex: 1; min-width: 180px; }
        .filter-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #0a1a3a; font-size: 12px; }
        .filter-group input, .filter-group select { width: 100%; padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 14px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; border-radius: 15px; padding: 20px; text-align: center; border-left: 4px solid #c9a03d; transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card .number { font-size: 28px; font-weight: 700; color: #0a1a3a; }
        
        .charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 25px; margin-bottom: 30px; }
        .chart-container { background: white; border-radius: 15px; padding: 20px; }
        .chart-container h3 { color: #0a1a3a; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .chart-container h3 i { color: #c9a03d; }
        canvas { max-height: 300px; width: 100%; }
        
        .table-responsive { overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; }
        .table th { text-align: left; padding: 15px; background: #f8f9fa; color: #0a1a3a; font-weight: 600; border-bottom: 2px solid #c9a03d; }
        .table td { padding: 12px 15px; border-bottom: 1px solid #eee; }
        
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
            .charts-grid { grid-template-columns: 1fr; }
            .page-header { flex-direction: column; text-align: center; }
            .btn-group { justify-content: center; }
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
                        <p>RELATÓRIOS GERENCIAIS</p>
                    </div>
                </div>
                <nav class="nav-menu">
                    <a href="dashboard.php" class="nav-link"><i class="fas fa-chart-pie"></i> Dashboard</a>
                    <a href="bancos.php" class="nav-link"><i class="fas fa-university"></i> Bancos</a>
                    <a href="clientes.php" class="nav-link"><i class="fas fa-users"></i> Clientes</a>
                    <a href="emprestimos.php" class="nav-link"><i class="fas fa-hand-holding-usd"></i> Empréstimos</a>
                    <a href="pagamentos.php" class="nav-link"><i class="fas fa-credit-card"></i> Pagamentos</a>
                    <a href="relatorios.php" class="nav-link active"><i class="fas fa-chart-line"></i> Relatórios</a>
                    <a href="usuarios.php" class="nav-link"><i class="fas fa-users-gear"></i> Usuários</a>
                    <a href="logout.php" class="nav-link btn-sair"><i class="fas fa-sign-out-alt"></i> Sair</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-chart-line"></i> Relatórios Gerenciais</h1>
                <div class="btn-group">
                    <button class="btn btn-outline" onclick="exportarRelatorio()"><i class="fas fa-file-excel"></i> Exportar</button>
                    <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Imprimir</button>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card"><i class="fas fa-hand-holding-usd"></i><div class="number"><?php echo array_sum(array_column($statusStats, 'total')); ?></div><p>Total Empréstimos</p></div>
                <div class="stat-card"><i class="fas fa-money-bill-wave"></i><div class="number">KZ <?php echo number_format(array_sum(array_column($statusStats, 'valor')), 0, ',', '.'); ?></div><p>Valor Total</p></div>
                <div class="stat-card"><i class="fas fa-check-circle"></i><div class="number"><?php 
                    $quitados = array_filter($statusStats, function($s) { return $s['status'] == 'quitado'; });
                    echo $quitados ? array_sum(array_column($quitados, 'total')) : 0;
                ?></div><p>Quitados</p></div>
                <div class="stat-card"><i class="fas fa-chart-line"></i><div class="number"><?php echo count($bancosStats); ?></div><p>Bancos Ativos</p></div>
            </div>

            <div class="charts-grid">
                <div class="chart-container"><h3><i class="fas fa-chart-pie"></i> Empréstimos por Status</h3><canvas id="chartStatus"></canvas></div>
                <div class="chart-container"><h3><i class="fas fa-chart-line"></i> Evolução Mensal</h3><canvas id="chartEvolucao"></canvas></div>
                <div class="chart-container"><h3><i class="fas fa-chart-bar"></i> Pagamentos por Mês</h3><canvas id="chartPagamentos"></canvas></div>
                <div class="chart-container"><h3><i class="fas fa-chart-bar"></i> Empréstimos por Banco</h3><canvas id="chartBancos"></canvas></div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-table"></i> Detalhamento Mensal</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Mês</th><th>Empréstimos</th><th>Valor Emprestado</th><th>Pagamentos</th><th>Valor Recebido</th></tr></thead>
                            <tbody>
                                <?php foreach($mensalStats as $m): ?>
                                <tr>
                                    <td><?php echo date('m/Y', strtotime($m['mes'] . '-01')); ?></td>
                                    <td><?php echo $m['total']; ?></td>
                                    <td>KZ <?php echo number_format($m['valor'], 2, ',', '.'); ?></td>
                                    <td><?php 
                                        $pag = array_filter($pagamentosStats, function($p) use ($m) { return $p['mes'] == $m['mes']; });
                                        echo $pag ? $pag[0]['total'] : 0;
                                    ?></td>
                                    <td>KZ <?php 
                                        echo $pag ? number_format($pag[0]['valor'], 2, ',', '.') : '0,00';
                                    ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($mensalStats)): ?>
                                <tr><td colspan="5" style="text-align:center;">Nenhum dado encontrado</td></tr>
                                <?php endif; ?>
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

    <script>
        // Gráfico de Status
        const statusLabels = <?php echo json_encode(array_column($statusStats, 'status')); ?>;
        const statusData = <?php echo json_encode(array_column($statusStats, 'total')); ?>;
        new Chart(document.getElementById('chartStatus'), {
            type: 'pie',
            data: { labels: statusLabels, datasets: [{ data: statusData, backgroundColor: ['#f57c00', '#0288d1', '#2e7d32', '#1a237e', '#c62828'] }] },
            options: { responsive: true }
        });
        
        // Gráfico de Evolução
        const mesesLabels = <?php echo json_encode(array_reverse(array_column($mensalStats, 'mes'))); ?>;
        const valoresEvolucao = <?php echo json_encode(array_reverse(array_column($mensalStats, 'valor'))); ?>;
        new Chart(document.getElementById('chartEvolucao'), {
            type: 'line',
            data: { labels: mesesLabels, datasets: [{ label: 'Valor Emprestado (KZ)', data: valoresEvolucao, borderColor: '#c9a03d', tension: 0.4, fill: true }] },
            options: { responsive: true }
        });
        
        // Gráfico de Pagamentos
        const pagamentosLabels = <?php echo json_encode(array_reverse(array_column($pagamentosStats, 'mes'))); ?>;
        const valoresPagamentos = <?php echo json_encode(array_reverse(array_column($pagamentosStats, 'valor'))); ?>;
        new Chart(document.getElementById('chartPagamentos'), {
            type: 'bar',
            data: { labels: pagamentosLabels, datasets: [{ label: 'Valor Recebido (KZ)', data: valoresPagamentos, backgroundColor: '#2e7d32' }] },
            options: { responsive: true }
        });
        
        // Gráfico de Bancos
        const bancosLabels = <?php echo json_encode(array_column($bancosStats, 'nome')); ?>;
        const bancosData = <?php echo json_encode(array_column($bancosStats, 'valor')); ?>;
        new Chart(document.getElementById('chartBancos'), {
            type: 'bar',
            data: { labels: bancosLabels, datasets: [{ label: 'Valor Emprestado (KZ)', data: bancosData, backgroundColor: '#c9a03d' }] },
            options: { responsive: true }
        });
        
        function exportarRelatorio() {
            let csv = "Relatório SGEB Angola\n\n";
            csv += "Empréstimos por Status\n";
            csv += "Status,Total,Valor\n";
            <?php foreach($statusStats as $s): ?>
            csv += "<?php echo $s['status']; ?>,<?php echo $s['total']; ?>,<?php echo $s['valor']; ?>\n";
            <?php endforeach; ?>
            csv += "\nEmpréstimos por Mês\n";
            csv += "Mês,Total,Valor\n";
            <?php foreach($mensalStats as $m): ?>
            csv += "<?php echo $m['mes']; ?>,<?php echo $m['total']; ?>,<?php echo $m['valor']; ?>\n";
            <?php endforeach; ?>
            const blob = new Blob([csv], {type: 'text/csv'});
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `relatorio_<?php echo date('Y-m-d'); ?>.csv`;
            link.click();
            alert('Relatório exportado com sucesso!');
        }
    </script>

</body>
</html>