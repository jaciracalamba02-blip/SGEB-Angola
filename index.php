<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGEB Angola - Sistema de Gestão de Empréstimos Bancários</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0a1a3a 0%, #0d1f44 100%);
            min-height: 100vh;
        }
        .container { max-width: 1280px; margin: 0 auto; padding: 0 30px; }
        
        .header {
            background: rgba(10, 26, 58, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            border-bottom: 1px solid rgba(201, 160, 61, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .header-content { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .logo { display: flex; align-items: center; gap: 12px; }
        .logo-icon { width: 45px; height: 45px; background: linear-gradient(135deg, #c9a03d, #e6c468); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #0a1a3a; }
        .logo-text h1 { font-size: 1.4rem; font-weight: 700; color: white; }
        .logo-text p { font-size: 0.7rem; color: #c9a03d; letter-spacing: 1.5px; }
        .nav-menu { display: flex; gap: 8px; flex-wrap: wrap; }
        .nav-link {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 40px;
            transition: all 0.3s;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav-link:hover { background: rgba(201, 160, 61, 0.2); color: #c9a03d; transform: translateY(-2px); }
        .nav-link.active { background: linear-gradient(135deg, #c9a03d, #e6c468); color: #0a1a3a; }
        
        .hero { padding: 80px 0; position: relative; overflow: hidden; }
        .hero-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center; }
        .hero-content h1 { font-size: 52px; font-weight: 800; line-height: 1.2; margin-bottom: 20px; background: linear-gradient(135deg, #fff, #c9a03d); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-content p { font-size: 18px; color: rgba(255, 255, 255, 0.7); line-height: 1.6; margin-bottom: 30px; }
        .hero-buttons { display: flex; gap: 20px; flex-wrap: wrap; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-primary { background: linear-gradient(135deg, #c9a03d, #e6c468); color: #0a1a3a; box-shadow: 0 4px 15px rgba(201, 160, 61, 0.3); }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(201, 160, 61, 0.4); }
        .btn-outline { border: 2px solid #c9a03d; color: #c9a03d; background: transparent; }
        .btn-outline:hover { background: linear-gradient(135deg, #c9a03d, #e6c468); color: #0a1a3a; transform: translateY(-3px); }
        .hero-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border-radius: 24px; padding: 30px; border: 1px solid rgba(255, 255, 255, 0.1); }
        .stat-item { text-align: center; }
        .stat-number { font-size: 36px; font-weight: 800; color: #c9a03d; margin-bottom: 8px; }
        .stat-label { font-size: 14px; color: rgba(255, 255, 255, 0.7); }
        
        .section-title { text-align: center; font-size: 32px; font-weight: 700; margin-bottom: 50px; color: white; }
        .section-title span { color: #c9a03d; }
        .services-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; margin-bottom: 60px; }
        .service-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 35px 25px;
            text-align: center;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .service-card:hover { transform: translateY(-10px); border-color: rgba(201, 160, 61, 0.5); }
        .service-icon { width: 80px; height: 80px; background: linear-gradient(135deg, rgba(201, 160, 61, 0.2), rgba(230, 196, 104, 0.1)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
        .service-icon i { font-size: 36px; color: #c9a03d; }
        .service-card h3 { font-size: 20px; margin-bottom: 15px; color: white; }
        .service-card p { color: rgba(255, 255, 255, 0.6); line-height: 1.6; }
        
        .bancos-section { background: rgba(255, 255, 255, 0.03); border-radius: 30px; padding: 50px; margin: 40px 0; }
        .bancos-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 25px; margin-top: 30px; }
        .banco-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 25px 20px;
            text-align: center;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .banco-card:hover { transform: translateY(-5px); border-color: #c9a03d; }
        .banco-logo { width: 60px; height: 60px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; }
        .banco-logo i { font-size: 28px; color: #c9a03d; }
        .banco-card h3 { font-size: 18px; margin-bottom: 5px; color: white; }
        .banco-card p { font-size: 12px; color: rgba(255, 255, 255, 0.5); margin-bottom: 8px; }
        .banco-taxa { font-size: 14px; font-weight: 700; color: #c9a03d; }
        
        .cta-section {
            background: linear-gradient(135deg, rgba(201, 160, 61, 0.1), rgba(10, 26, 58, 0.9));
            border-radius: 30px;
            padding: 50px;
            text-align: center;
            margin: 50px 0;
            border: 1px solid rgba(201, 160, 61, 0.3);
        }
        .cta-section h2 { font-size: 32px; margin-bottom: 15px; color: white; }
        .cta-section p { color: rgba(255, 255, 255, 0.7); margin-bottom: 25px; }
        
        .footer {
            background: #0a1a3a;
            border-top: 1px solid rgba(201, 160, 61, 0.3);
            padding: 50px 0 30px;
            margin-top: 60px;
        }
        .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 40px; }
        .footer-col h4 { font-size: 18px; margin-bottom: 20px; color: #c9a03d; }
        .footer-col p { color: rgba(255, 255, 255, 0.6); line-height: 1.6; }
        .footer-social { display: flex; gap: 15px; }
        .footer-social a { width: 40px; height: 40px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; transition: all 0.3s; }
        .footer-social a:hover { background: #c9a03d; color: #0a1a3a; }
        .footer-links { list-style: none; }
        .footer-links li { margin-bottom: 12px; }
        .footer-links a { color: rgba(255, 255, 255, 0.6); text-decoration: none; transition: all 0.3s; }
        .footer-links a:hover { color: #c9a03d; padding-left: 5px; }
        .footer-bottom { text-align: center; padding-top: 30px; border-top: 1px solid rgba(255, 255, 255, 0.1); color: rgba(255, 255, 255, 0.5); font-size: 14px; }
        .gold { color: #c9a03d; }
        
        @media (max-width: 992px) {
            .hero-grid { grid-template-columns: 1fr; text-align: center; }
            .hero-buttons { justify-content: center; }
            .hero-stats { max-width: 400px; margin: 0 auto; }
            .header-content { flex-direction: column; }
        }
        @media (max-width: 768px) {
            .container { padding: 0 20px; }
            .hero-content h1 { font-size: 36px; }
            .section-title { font-size: 28px; }
            .bancos-section { padding: 30px 20px; }
            .cta-section { padding: 30px 20px; }
            .cta-section h2 { font-size: 24px; }
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
                    <a href="index.php" class="nav-link active"><i class="fas fa-home"></i> Início</a>
                    <a href="cliente/login.php" class="nav-link"><i class="fas fa-user"></i> Área do Cliente</a>
                    <a href="login.php" class="nav-link"><i class="fas fa-shield-alt"></i> Área Admin</a>
                    <a href="cliente/cadastro.php" class="nav-link"><i class="fas fa-user-plus"></i> Cadastrar</a>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container">
                <div class="hero-grid">
                    <div class="hero-content">
                        <h1>Sistema de Gestão de Empréstimos Bancários</h1>
                        <p>Controle eficiente de crédito, clientes e pagamentos com tecnologia de ponta e segurança garantida.</p>
                        <div class="hero-buttons">
                            <a href="cliente/login.php" class="btn btn-primary"><i class="fas fa-hand-holding-usd"></i> Solicitar Empréstimo</a>
                            <a href="login.php" class="btn btn-outline"><i class="fas fa-shield-alt"></i> Área Administrativa</a>
                        </div>
                    </div>
                    <div class="hero-stats">
                        <?php
                        try {
                            $conn = getConexao();
                            $clientes = $conn->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
                            $bancos = $conn->query("SELECT COUNT(*) FROM bancos WHERE status='ativo'")->fetchColumn();
                            $emprestimos = $conn->query("SELECT SUM(valor_solicitado) FROM emprestimos")->fetchColumn();
                        } catch(PDOException $e) {
                            $clientes = 0; $bancos = 0; $emprestimos = 0;
                        }
                        ?>
                        <div class="stat-item"><div class="stat-number">+<?php echo number_format($clientes); ?></div><div class="stat-label">CLIENTES ATENDIDOS</div></div>
                        <div class="stat-item"><div class="stat-number"><?php echo $bancos; ?></div><div class="stat-label">BANCOS PARCEIROS</div></div>
                        <div class="stat-item"><div class="stat-number">KZ <?php echo number_format($emprestimos / 1000, 1); ?>K</div><div class="stat-label">EMPRESTADO</div></div>
                        <div class="stat-item"><div class="stat-number">98%</div><div class="stat-label">SATISFAÇÃO</div></div>
                    </div>
                </div>
            </div>
        </section>

        <div class="container">
            <h2 class="section-title">Nossos <span>Serviços</span></h2>
            <div class="services-grid">
                <div class="service-card"><div class="service-icon"><i class="fas fa-university"></i></div><h3>Gestão de Bancos</h3><p>Cadastre e gerencie instituições financeiras parceiras com taxas personalizadas.</p></div>
                <div class="service-card"><div class="service-icon"><i class="fas fa-users"></i></div><h3>Gestão de Clientes</h3><p>Cadastro completo de clientes com histórico de crédito e análise de perfil.</p></div>
                <div class="service-card"><div class="service-icon"><i class="fas fa-hand-holding-usd"></i></div><h3>Concessão de Empréstimos</h3><p>Cálculo automático de juros, parcelas e simulação personalizada.</p></div>
                <div class="service-card"><div class="service-icon"><i class="fas fa-credit-card"></i></div><h3>Controle de Pagamentos</h3><p>Registro de parcelas e identificação automática de inadimplência.</p></div>
                <div class="service-card"><div class="service-icon"><i class="fas fa-chart-line"></i></div><h3>Relatórios Gerenciais</h3><p>Gráficos e estatísticas completas para tomada de decisão.</p></div>
                <div class="service-card"><div class="service-icon"><i class="fas fa-mobile-alt"></i></div><h3>Solicitação Online</h3><p>Clientes podem solicitar empréstimos diretamente pelo site.</p></div>
            </div>
        </div>

        <div class="container">
            <div class="bancos-section">
                <h2 class="section-title" style="margin-bottom: 20px;">Bancos <span>Parceiros</span></h2>
                <div class="bancos-grid">
                    <?php
                    try {
                        $bancosLista = $conn->query("SELECT nome, sigla, taxa_base FROM bancos WHERE status='ativo' LIMIT 6")->fetchAll();
                        foreach($bancosLista as $banco):
                    ?>
                    <div class="banco-card"><div class="banco-logo"><i class="fas fa-university"></i></div><h3><?php echo $banco['sigla']; ?></h3><p><?php echo $banco['nome']; ?></p><span class="banco-taxa">Taxa: <?php echo $banco['taxa_base']; ?>%</span></div>
                    <?php endforeach; } catch(PDOException $e) { ?>
                    <div class="banco-card"><div class="banco-logo"><i class="fas fa-university"></i></div><h3>BAI</h3><p>Banco Angolano de Investimentos</p><span class="banco-taxa">Taxa: 2.5%</span></div>
                    <div class="banco-card"><div class="banco-logo"><i class="fas fa-university"></i></div><h3>BFA</h3><p>Banco de Fomento Angola</p><span class="banco-taxa">Taxa: 2.8%</span></div>
                    <div class="banco-card"><div class="banco-logo"><i class="fas fa-university"></i></div><h3>BIC</h3><p>Banco BIC</p><span class="banco-taxa">Taxa: 3.0%</span></div>
                    <div class="banco-card"><div class="banco-logo"><i class="fas fa-university"></i></div><h3>Banco Económico</h3><p>Banco Económico</p><span class="banco-taxa">Taxa: 2.7%</span></div>
                    <div class="banco-card"><div class="banco-logo"><i class="fas fa-university"></i></div><h3>BMA</h3><p>Banco Millennium Atlântico</p><span class="banco-taxa">Taxa: 2.9%</span></div>
                    <div class="banco-card"><div class="banco-logo"><i class="fas fa-university"></i></div><h3>BNI</h3><p>Banco de Negócios</p><span class="banco-taxa">Taxa: 3.2%</span></div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="cta-section">
                <h2>Pronto para solicitar seu empréstimo?</h2>
                <p>Cadastre-se agora e tenha acesso a simulações personalizadas e as melhores taxas do mercado.</p>
                <a href="cliente/cadastro.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Criar Conta Gratuita</a>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col"><h4><i class="fas fa-landmark"></i> SGEB Angola</h4><p>Sistema de Gestão de Empréstimos Bancários.</p><div class="footer-social"><a href="#"><i class="fab fa-facebook-f"></i></a><a href="#"><i class="fab fa-instagram"></i></a><a href="#"><i class="fab fa-linkedin-in"></i></a><a href="#"><i class="fab fa-whatsapp"></i></a></div></div>
                <div class="footer-col"><h4>Links Rápidos</h4><ul class="footer-links"><li><a href="index.php">Início</a></li><li><a href="cliente/login.php">Área do Cliente</a></li><li><a href="login.php">Área Admin</a></li><li><a href="cliente/cadastro.php">Cadastrar</a></li></ul></div>
                <div class="footer-col"><h4>Contato</h4><ul class="footer-links"><li><i class="fas fa-map-marker-alt"></i> Ícolo e Bengo, Angola</li><li><i class="fas fa-envelope"></i> contato@sgeb.ao</li><li><i class="fas fa-phone"></i> +244 923 123 456</li></ul></div>
            </div>
            <div class="footer-bottom"><p><span class="gold">◆</span> Complexo Escolar Fenda da Tundavala | 12ª Classe | 2025/2026 <span class="gold">◆</span></p><p>© 2025 SGEB Angola</p></div>
        </div>
    </footer>
</body>
</html>