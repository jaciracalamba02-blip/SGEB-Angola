<?php
session_start();
require_once 'config.php';
require_once 'auth.php';
require_once 'funcões.php';

verificarAdmin();
$admin = getAdminLogado();

try {
    $conn = getConexao();
    
    // Estatísticas com JOIN
    $totalClientes = $conn->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
    $totalBancos = $conn->query("SELECT COUNT(*) FROM bancos WHERE status='ativo'")->fetchColumn();
    $totalEmprestimos = $conn->query("SELECT COUNT(*) FROM emprestimos")->fetchColumn();
    $totalEmprestado = $conn->query("SELECT SUM(valor_solicitado) FROM emprestimos")->fetchColumn();
    $totalUsuarios = $conn->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $inadimplentes = $conn->query("SELECT COUNT(*) FROM emprestimos WHERE status='inadimplente'")->fetchColumn();
    
    // Últimos empréstimos com JOIN
    $sql = "
        SELECT e.*, c.nome as cliente_nome, b.nome as banco_nome
        FROM emprestimos e
        INNER JOIN clientes c ON e.id_cliente = c.id_cliente
        INNER JOIN bancos b ON e.id_banco = b.id_banco
        ORDER BY e.id_emprestimo DESC LIMIT 5
    ";
    $ultimos = $conn->query($sql)->fetchAll();
    
} catch(PDOException $e) {
    $totalClientes = 0; $totalBancos = 0; $totalEmprestimos = 0;
    $totalEmprestado = 0; $totalUsuarios = 0; $inadimplentes = 0;
    $ultimos = [];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SGEB Angola</title>
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
            position: sticky;
            top: 0;
            z-index: 1000;
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
        .welcome-card {
            background: linear-gradient(135deg, #0d1f44, #0a1a3a);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(201,160,61,0.3);
        }
        .welcome-content { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .welcome-text h1 { font-size: 28px; color: white; margin-bottom: 10px; }
        .welcome-text h1 span { color: #c9a03d; }
        .welcome-avatar { width: 80px; height: 80px; background: linear-gradient(135deg, #c9a03d, #e6c468); border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .welcome-avatar i { font-size: 40px; color: #0a1a3a; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 30px; }
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
            border: 1px solid rgba(201,160,61,0.2);
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .stat-info h3 { font-size: 14px; color: #666; margin-bottom: 8px; }
        .stat-number { font-size: 32px; font-weight: 800; color: #0a1a3a; }
        .stat-icon { font-size: 45px; opacity: 0.3; color: #0a1a3a; }
        
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
        }
        .card-header h2 { font-size: 1.2rem; font-weight: 700; color: #0a1a3a; display: flex; align-items: center; gap: 10px; }
        .card-header h2 i { color: #c9a03d; }
        .card-body { padding: 28px; }
        
        .btn-group { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; gap: 10px; padding: 12px 24px; border-radius: 40px; font-size: 14px; font-weight: 600; text-decoration: none; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #c9a03d, #e6c468); color: #0a1a3a; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 5px 20px rgba(201,160,61,0.4); }
        .btn-outline { background: transparent; border: 2px solid #c9a03d; color: #c9a03d; }
        
        .table-responsive { overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; }
        .table th { text-align: left; padding: 12px; background: #f8f9fa; border-bottom: 2px solid #c9a03d; }
        .table td { padding: 12px; border-bottom: 1px solid #eee; }
        
        .restricted-card {
            background: linear-gradient(135deg, #fff9e6, #fff);
            border-radius: 20px;
            border: 1px solid #c9a03d;
        }
        .restricted-header {
            padding: 22px 28px;
            background: linear-gradient(135deg, #c9a03d, #e6c468);
            color: #0a1a3a;
        }
        .restricted-body { padding: 28px; }
        .restricted-body li { padding: 12px 0; border-bottom: 1px solid #eee; list-style: none; display: flex; align-items: center; gap: 12px; }
        .restricted-body li i { color: #c9a03d; }
        
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
            .welcome-content { flex-direction: column; text-align: center; }
            .stats-grid { grid-template-columns: 1fr; }
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
                        <p>GESTÃO DE EMPRÉSTIMOS BANCÁRIOS</p>
                    </div>
                </div>
                <nav class="nav-menu">
                    <a href="dashboard.php" class="nav-link active"><i class="fas fa-chart-pie"></i> Dashboard</a>
                    <a href="bancos.php" class="nav-link"><i class="fas fa-university"></i> Bancos</a>
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
            <div class="welcome-card">
                <div class="welcome-content">
                    <div class="welcome-text">
                        <h1>Bem-vindo, <span><?php echo htmlspecialchars($admin['nome']); ?></span>! 👋</h1>
                        <p><i class="fas fa-calendar-alt"></i> Hoje é <?php echo date('d \d\e F \d\e Y'); ?></p>
                        <p><i class="fas fa-chart-line"></i> Painel de controlo completo</p>
                    </div>
                    <div class="welcome-avatar"><i class="fas fa-user-crown"></i></div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card"><div class="stat-info"><h3>Total Clientes</h3><div class="stat-number"><?php echo number_format($totalClientes); ?></div></div><div class="stat-icon"><i class="fas fa-users"></i></div></div>
                <div class="stat-card"><div class="stat-info"><h3>Bancos Parceiros</h3><div class="stat-number"><?php echo $totalBancos; ?></div></div><div class="stat-icon"><i class="fas fa-university"></i></div></div>
                <div class="stat-card"><div class="stat-info"><h3>Empréstimos</h3><div class="stat-number"><?php echo $totalEmprestimos; ?></div></div><div class="stat-icon"><i class="fas fa-hand-holding-usd"></i></div></div>
                <div class="stat-card"><div class="stat-info"><h3>Total Emprestado</h3><div class="stat-number">KZ <?php echo number_format($totalEmprestado, 0, ',', '.'); ?></div></div><div class="stat-icon"><i class="fas fa-chart-line"></i></div></div>
                <div class="stat-card"><div class="stat-info"><h3>Inadimplência</h3><div class="stat-number"><?php echo $inadimplentes; ?></div></div><div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div></div>
                <div class="stat-card"><div class="stat-info"><h3>Usuários</h3><div class="stat-number"><?php echo $totalUsuarios; ?></div></div><div class="stat-icon"><i class="fas fa-users-gear"></i></div></div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-bolt"></i> Ações Administrativas</h2>
                    <span><i class="fas fa-crown"></i> Nível: <?php echo ucfirst($admin['nivel']); ?></span>
                </div>
                <div class="card-body">
                    <div class="btn-group">
                        <a href="bancos.php" class="btn btn-primary"><i class="fas fa-university"></i> Gerenciar Bancos</a>
                        <a href="clientes.php" class="btn btn-primary"><i class="fas fa-users"></i> Gerenciar Clientes</a>
                        <a href="emprestimos.php" class="btn btn-primary"><i class="fas fa-hand-holding-usd"></i> Gerenciar Empréstimos</a>
                        <a href="pagamentos.php" class="btn btn-primary"><i class="fas fa-credit-card"></i> Registrar Pagamentos</a>
                        <a href="relatorios.php" class="btn btn-outline"><i class="fas fa-chart-line"></i> Relatórios</a>
                        <a href="usuarios.php" class="btn btn-outline"><i class="fas fa-users-gear"></i> Usuários</a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-history"></i> Últimos Empréstimos</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>ID</th><th>Cliente</th><th>Banco</th><th>Valor</th><th>Status</th><th>Data</th></tr></thead>
                            <tbody>
                                <?php foreach($ultimos as $emp): ?>
                                <tr>
                                    <td>#<?php echo $emp['id_emprestimo']; ?></td>
                                    <td><?php echo htmlspecialchars($emp['cliente_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['banco_nome']); ?></td>
                                    <td>KZ <?php echo number_format($emp['valor_solicitado'], 2, ',', '.'); ?></td>
                                    <td><?php echo $emp['status']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($emp['data_solicitacao'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="restricted-card">
                <div class="restricted-header">
                    <h2><i class="fas fa-shield-alt"></i> Área Restrita - Administração</h2>
                </div>
                <div class="restricted-body">
                    <p><i class="fas fa-lock"></i> Esta área é visível APENAS para administradores.</p>
                    <ul>
                        <li><i class="fas fa-chart-line"></i> Logs de auditoria completos</li>
                        <li><i class="fas fa-database"></i> Backup do banco de dados</li>
                        <li><i class="fas fa-cog"></i> Configurações avançadas</li>
                        <li><i class="fas fa-users-gear"></i> Gestão de permissões</li>
                    </ul>
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

</body>
</html>