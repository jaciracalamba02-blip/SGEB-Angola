<?php
session_start();
if(isset($_SESSION['usuario_id'])) {
    require_once 'conexao.php';
    $log = getConexao()->prepare("INSERT INTO logs (id_usuario, acao, descricao, ip) VALUES (?, 'logout', 'Usuário fez logout', ?)");
    $log->execute([$_SESSION['usuario_id'], $_SERVER['REMOTE_ADDR']]);
}
session_start();
session_destroy();

header("Location: index.php");
header("Location: login-cliente.html")

session_start();
$_SESSION = array();
session_destroy();
header("Location: index.php");
exit();
?>