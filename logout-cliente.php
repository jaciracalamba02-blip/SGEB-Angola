<?php
/**
 * SGEB - Logout do Cliente
 */

session_start();

// Registrar log de saída (opcional)
if(isset($_SESSION['cliente_id'])) {
    // Você pode registrar no banco se quiser
    // $logStmt->execute([':id' => $_SESSION['cliente_id'], 'acao' => 'logout']);
}

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Destruir a sessão
session_destroy();

// Redirecionar para o login
header("Location: login-cliente.html");
exit();
?>