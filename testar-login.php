<?php
require_once 'includes/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico Login - SGEB</title>
    <style>
        body { font-family: Arial; background: #0a1a3a; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        h1 { color: #0a1a3a; }
        .success { color: green; }
        .error { color: red; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #0a1a3a; color: white; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Diagnóstico - Login do Cliente</h1>
        <hr>";

try {
    $conn = getConexao();
    
    // Verificar clientes no banco
    $stmt = $conn->query("SELECT id_cliente, nome, usuario, email, senha, status FROM clientes");
    $clientes = $stmt->fetchAll();
    
    if(count($clientes) > 0) {
        echo "<h2>📋 Clientes cadastrados no banco:</h2>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Nome</th><th>Usuário</th><th>Email</th><th>Senha</th><th>Status</th></tr>";
        foreach($clientes as $c) {
            echo "<tr>";
            echo "<td>{$c['id_cliente']}</td>";
            echo "<td>{$c['nome']}</td>";
            echo "<td>{$c['usuario']}</td>";
            echo "<td>{$c['email']}</td>";
            echo "<td>{$c['senha']}</td>";
            echo "<td>{$c['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p class='success'>✅ Existem " . count($clientes) . " clientes cadastrados.</p>";
    } else {
        echo "<p class='error'>❌ Nenhum cliente encontrado no banco de dados!</p>";
        echo "<p><a href='cadastro.php'>Clique aqui para cadastrar um cliente</a></p>";
    }
    
    // Testar login com credenciais específicas
    echo "<h2>🔑 Teste de Login Manual</h2>";
    echo "<form method='POST'>";
    echo "<label>Usuário/Email: </label><input type='text' name='test_usuario' style='padding:8px; margin:5px;'>";
    echo "<label>Senha: </label><input type='text' name='test_senha' style='padding:8px; margin:5px;'>";
    echo "<button type='submit' name='testar' style='padding:8px 16px; background:#c9a03d; border:none; border-radius:5px; cursor:pointer;'>Testar Login</button>";
    echo "</form>";
    
    if(isset($_POST['testar'])) {
        $test_usuario = $_POST['test_usuario'];
        $test_senha = $_POST['test_senha'];
        
        $stmt = $conn->prepare("SELECT * FROM clientes WHERE (email = ? OR usuario = ?) AND status = 'ativo'");
        $stmt->execute([$test_usuario, $test_usuario]);
        $cliente = $stmt->fetch();
        
        if($cliente) {
            echo "<p>✅ Cliente encontrado!</p>";
            if($test_senha === $cliente['senha']) {
                echo "<p class='success'>✅ Senha CORRETA! O login funcionaria.</p>";
            } else {
                echo "<p class='error'>❌ Senha INCORRETA!<br>";
                echo "Senha digitada: <strong>'{$test_senha}'</strong><br>";
                echo "Senha no banco: <strong>'{$cliente['senha']}'</strong></p>";
            }
        } else {
            echo "<p class='error'>❌ Usuário não encontrado!</p>";
        }
    }
    
} catch(PDOException $e) {
    echo "<p class='error'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "</div></body></html>";
?>