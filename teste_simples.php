<?php
echo "PHP está funcionando!<br>";

try {
    $conn = new PDO("mysql:host=localhost;dbname=sgeb_angola;charset=utf8mb4", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexão com o banco de dados funcionou!";
} catch(PDOException $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>