<?php
echo "<h1>🔧 Teste de Conexão - SGEB Angola</h1>";
echo "<hr>";

// Dados de conexão
$host = 'localhost';
$dbname = 'sgeb_angola';
$username = 'root';
$password = '';

try {
    // 1. Testar conexão
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color:green'>✅ <strong>Passo 1:</strong> Conexão com o banco de dados - OK!</p>";
    
    // 2. Verificar tabelas
    $tabelas = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p style='color:green'>✅ <strong>Passo 2:</strong> Tabelas encontradas: " . implode(', ', $tabelas) . "</p>";
    
    // 3. Verificar clientes
    $clientes = $pdo->query("SELECT COUNT(*) as total FROM clientes")->fetch();
    echo "<p style='color:green'>✅ <strong>Passo 3:</strong> Total de clientes no banco: " . $clientes['total'] . "</p>";
    
    // 4. Listar clientes
    $listaClientes = $pdo->query("SELECT id_cliente, nome, usuario, email FROM clientes")->fetchAll();
    
    echo "<h3>📋 Clientes cadastrados:</h3>";
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr style='background:#0a1a3a; color:white;'><th>ID</th><th>Nome</th><th>Usuário</th><th>Email</th></tr>";
    foreach($listaClientes as $c) {
        echo "<tr>";
        echo "<td>{$c['id_cliente']}</td>";
        echo "<td>{$c['nome']}</td>";
        echo "<td>{$c['usuario']}</td>";
        echo "<td>{$c['email']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 5. Verificar usuários admin
    $usuarios = $pdo->query("SELECT id_usuario, nome, usuario, nivel FROM usuarios")->fetchAll();
    
    echo "<h3>👤 Usuários Administrativos:</h3>";
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr style='background:#0a1a3a; color:white;'><th>ID</th><th>Nome</th><th>Usuário</th><th>Nível</th></tr>";
    foreach($usuarios as $u) {
        echo "<tr>";
        echo "<td>{$u['id_usuario']}</td>";
        echo "<td>{$u['nome']}</td>";
        echo "<td>{$u['usuario']}</td>";
        echo "<td>{$u['nivel']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<p style='color:green; font-size:18px;'>🎉 <strong>TUDO ESTÁ FUNCIONANDO CORRETAMENTE!</strong></p>";
    
} catch(PDOException $e) {
    echo "<p style='color:red'>❌ <strong>ERRO:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Verifique se:<br>
          - O XAMPP está rodando<br>
          - O banco de dados 'sgeb_angola' existe<br>
          - As credenciais estão corretas</p>";
}
?>