<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

verificarCliente();

$cliente = getClienteLogado();

try {
    $conn = getConexao();
    
    $totalEmprestimos = $conn->prepare("SELECT COUNT(*) FROM emprestimos WHERE id_cliente = ?");
    $totalEmprestimos->execute([$cliente['id']]);
    $totalEmprestimos = $totalEmprestimos->fetchColumn();
    
    $pendentes = $conn->prepare("SELECT COUNT(*) FROM emprestimos WHERE id_cliente = ? AND status = 'pendente'");
    $pendentes->execute([$cliente['id']]);
    $pendentes = $pendentes->fetchColumn();
    
    $ativos = $conn->prepare("SELECT COUNT(*) FROM emprestimos WHERE id_cliente = ? AND status IN ('aprovado', 'ativo')");
    $ativos->execute([$cliente['id']]);
    $ativos = $ativos->fetchColumn();
    
    $totalValor = $conn->prepare("SELECT SUM(valor_solicitado) FROM emprestimos WHERE id_cliente = ?");
    $totalValor->execute([$cliente['id']]);
    $totalValor = $totalValor->fetchColumn() ?? 0;
    
    $ultimos = $conn->prepare("SELECT e.*, b.nome as banco_nome FROM emprestimos e JOIN bancos b ON e.id_banco = b.id_banco WHERE e.id_cliente = ? ORDER BY e.id_emprestimo DESC LIMIT 5");
    $ultimos->execute([$cliente['id']]);
    $ultimosEmprestimos = $ultimos->fetchAll();
    
} catch(PDOException $e) {
    $totalEmprestimos = 0;
    $pendentes = 0;
    $ativos = 0;
    $totalValor = 0;
    $ultimosEmprestimos = [];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SGEB Angola</title>
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
        
        .actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .action-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
            border: 1px solid rgba(201,160,61,0.2);
        }
        .action-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-color: #c9a03d; }
        .action-card i { font-size: 50px; color: #c9a03d; margin-bottom: 15px; }
        .action-card h3 { color: #0a1a3a; margin-bottom: 10px; }
        .action-card p { color: #666; font-size: 14px; }
        
        .ultimos-emprestimos {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: 1px solid rgba(201,160,61,0.2);
        }
        .ultimos-emprestimos h3 { color: #0a1a3a; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .emprestimo-item {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-pendente { background: #fff3e0; color: #e65100; }
        .badge-aprovado { background: #e8f5e9; color: #2e7d32; }
        .badge-ativo { background: #e3f2fd; color: #1565c0; }
        
        .footer {
            background: #0a1a3a;
            color: white;
            text-align: center;
            padding: 30px;
            border-top: 2px solid #c9a03d;
            margin-top: 40px;
        }
        .footer .gold { color: #c9a03d; }
        
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
                        <p>ÁREA DO CLIENTE</p>
                    </div>
                </div>
                <nav class="nav-menu">
                    <a href="dashboard.php" class="nav-link active"><i class="fas fa-home"></i> Início</a>
                    <a href="solicitar-emprestimo.php" class="nav-link"><i class="fas fa-hand-holding-usd"></i> Solicitar</a>
                    <a href="meus-emprestimos.php" class="nav-link"><i class="fas fa-list"></i> Meus Empréstimos</a>
                    <a href="perfil.php" class="nav-link"><i class="fas fa-user"></i> Meu Perfil</a>
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
                        <h1>Olá, <span><?php echo htmlspecialchars($cliente['nome']); ?></span>! 👋</h1>
                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($cliente['email']); ?></p>
                        <p><small><i class="fas fa-calendar-alt"></i> Cliente desde: <?php echo date('d/m/Y'); ?></small></p>
                    </div>
                    <div class="welcome-avatar"><i class="fas fa-user-circle"></i></div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card"><div class="stat-info"><h3>Meus Empréstimos</h3><div class="stat-number"><?php echo $totalEmprestimos; ?></div></div><div class="stat-icon"><i class="fas fa-hand-holding-usd"></i></div></div>
                <div class="stat-card"><div class="stat-info"><h3>Em Análise</h3><div class="stat-number"><?php echo $pendentes; ?></div></div><div class="stat-icon"><i class="fas fa-clock"></i></div></div>
                <div class="stat-card"><div class="stat-info"><h3>Aprovados/Ativos</h3><div class="stat-number"><?php echo $ativos; ?></div></div><div class="stat-icon"><i class="fas fa-check-circle"></i></div></div>
                <div class="stat-card"><div class="stat-info"><h3>Total Emprestado</h3><div class="stat-number">KZ <?php echo number_format($totalValor, 2, ',', '.'); ?></div></div><div class="stat-icon"><i class="fas fa-chart-line"></i></div></div>
            </div>

            <div class="actions-grid">
                <a href="solicitar-emprestimo.php" class="action-card"><i class="fas fa-hand-holding-usd"></i><h3>Solicitar Empréstimo</h3><p>Simule e solicite seu crédito</p></a>
                <a href="meus-emprestimos.php" class="action-card"><i class="fas fa-list"></i><h3>Meus Empréstimos</h3><p>Acompanhe suas solicitações</p></a>
                <a href="perfil.php" class="action-card"><i class="fas fa-user-edit"></i><h3>Meu Perfil</h3><p>Atualize seus dados pessoais</p></a>
            </div>

            <div class="ultimos-emprestimos">
                <h3><i class="fas fa-history"></i> Últimas Solicitações</h3>
                <?php if(count($ultimosEmprestimos) > 0): ?>
                    <?php foreach($ultimosEmprestimos as $emp): ?>
                    <div class="emprestimo-item">
                        <div><strong>#<?php echo $emp['id_emprestimo']; ?></strong><br><small><?php echo date('d/m/Y', strtotime($emp['data_solicitacao'])); ?></small></div>
                        <div><strong>KZ <?php echo number_format($emp['valor_solicitado'], 2, ',', '.'); ?></strong><br><small><?php echo $emp['prazo_meses']; ?> meses</small></div>
                        <div><span class="badge badge-<?php echo $emp['status']; ?>"><?php echo $emp['status'] == 'pendente' ? 'Em análise' : ($emp['status'] == 'aprovado' ? 'Aprovado' : 'Ativo'); ?></span></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #666;">Você ainda não tem solicitações. <a href="solicitar-emprestimo.php">Clique aqui para solicitar!</a></p>
                <?php endif; ?>
                <div style="margin-top: 15px; text-align: center;"><a href="meus-emprestimos.php" style="color: #c9a03d; text-decoration: none;">Ver todos →</a></div>
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