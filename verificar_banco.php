<?php
/**
 * SGEB Angola - Verificar Dados do Banco
 */

require_once 'includes/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Verificar Banco - SGEB Angola</title>
    <style>
        body { font-family: Arial; background: #0a1a3a; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        h1, h2 { color: #0a1a3a; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #0a1a3a; color: white; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .gold { color: #c9a03d; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Verificação do Banco de Dados - SGEB Angola</h1>
        <hr>";

try {
    $conn = getConexao();
    echo "<p class='success'>✅ Conexão bem sucedida!</p>";
    
    // 1. Verificar tabelas
    echo "<h2>📊 Tabelas do Banco</h2>";
    $tabelas = ['bancos', 'clientes', 'emprestimos', 'parcelas', 'usuarios'];
    echo "<ul>";
    foreach($tabelas as $tabela) {
        $stmt = $conn->query("SHOW TABLES LIKE '$tabela'");
        if($stmt->rowCount() > 0) {
            $count = $conn->query("SELECT COUNT(*) FROM $tabela")->fetchColumn();
            echo "<li>✅ Tabela '<strong>$tabela</strong>' existe - <span class='gold'>$count registros</span></li>";
        } else {
            echo "<li class='error'>❌ Tabela '$tabela' NÃO existe!</li>";
        }
    }
    echo "</ul>";
    
    // 2. Mostrar bancos
    echo "<h2>🏦 Bancos Cadastrados</h2>";
    $bancos = $conn->query("SELECT * FROM bancos")->fetchAll();
    if(count($bancos) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Nome</th><th>Sigla</th><th>Taxa</th><th>Status</th></tr>";
        foreach($bancos as $banco) {
            echo "<tr>";
            echo "<td>{$banco['id_banco']}</td>";
            echo "<td>{$banco['nome']}</td>";
            echo "<td>{$banco['sigla']}</td>";
            echo "<td>{$banco['taxa_base']}%</td>";
            echo "<td>{$banco['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>⚠️ Nenhum banco cadastrado!</p>";
    }
    
    // 3. Mostrar usuários admin
    echo "<h2>👤 Usuários Administrativos</h2>";
    $usuarios = $conn->query("SELECT id_usuario, nome, usuario, email, nivel, status FROM usuarios")->fetchAll();
    if(count($usuarios) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Nome</th><th>Usuário</th><th>Email</th><th>Nível</th><th>Status</th></tr>";
        foreach($usuarios as $user) {
            echo "<tr>";
            echo "<td>{$user['id_usuario']}</td>";
            echo "<td>{$user['nome']}</td>";
            echo "<td>{$user['usuario']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['nivel']}</td>";
            echo "<td>{$user['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><strong>🔑 Credenciais de teste:</strong><br>";
        echo "Usuário: <strong>admin</strong> | Senha: <strong>123456</strong></p>";
    } else {
        echo "<p class='error'>⚠️ Nenhum usuário administrativo encontrado!</p>";
    }
    
    // 4. Mostrar clientes
    echo "<h2>👥 Clientes Cadastrados</h2>";
    $clientes = $conn->query("SELECT id_cliente, nome, nif, email, telefone, status FROM clientes")->fetchAll();
    if(count($clientes) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Nome</th><th>NIF</th><th>Email</th><th>Telefone</th><th>Status</th></tr>";
        foreach($clientes as $cliente) {
            echo "<tr>";
            echo "<td>{$cliente['id_cliente']}</td>";
            echo "<td>{$cliente['nome']}</td>";
            echo "<td>{$cliente['nif']}</td>";
            echo "<td>{$cliente['email']}</td>";
            echo "<td>{$cliente['telefone']}</td>";
            echo "<td>{$cliente['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>ℹ️ Nenhum cliente cadastrado ainda. <a href='cliente/cadastro.php'>Cadastre-se aqui</a></p>";
    }
    
    // 5. Mostrar empréstimos
    echo "<h2>💰 Empréstimos</h2>";
    $emprestimos = $conn->query("
        SELECT e.id_emprestimo, c.nome as cliente, b.nome as banco, e.valor_solicitado, e.status 
        FROM emprestimos e
        LEFT JOIN clientes c ON e.id_cliente = c.id_cliente
        LEFT JOIN bancos b ON e.id_banco = b.id_banco
    ")->fetchAll();
    if(count($emprestimos) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Cliente</th><th>Banco</th><th>Valor</th><th>Status</th></tr>";
        foreach($emprestimos as $emp) {
            echo "<tr>";
            echo "<td>{$emp['id_emprestimo']}</td>";
            echo "<td>{$emp['cliente']}</td>";
            echo "<td>{$emp['banco']}</td>";
            echo "<td>KZ " . number_format($emp['valor_solicitado'], 2, ',', '.') . "</td>";
            echo "<td>{$emp['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>ℹ️ Nenhum empréstimo solicitado ainda.</p>";
    }
    
    echo "<hr>";
    echo "<p class='success'>✅ Banco de dados funcionando corretamente!</p>";
    
} catch(PDOException $e) {
    echo "<p class='error'>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<p>Verifique se:<br>
          - O MySQL está rodando no XAMPP<br>
          - O banco de dados 'sgeb_angola' existe<br>
          - O script database.sql foi executado<br>
          - As credenciais em config.php estão corretas</p>";
}

echo "</div></body></html>";
?>