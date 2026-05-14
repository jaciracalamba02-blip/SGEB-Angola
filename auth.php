<?php
/**
 * SGEB Angola - Autenticação
 * Este arquivo contém funções para verificar login de admin e cliente
 */

session_start();

// Verificar se o administrador está logado
function verificarAdmin() {
    if(!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_logado'])) {
        header("Location: login.php");
        exit();
    }
}

// Verificar se o cliente está logado
function verificarCliente() {
    if(!isset($_SESSION['cliente_id']) || !isset($_SESSION['cliente_logado'])) {
        header("Location: login.php");
        exit();
    }
}

// Obter dados do administrador logado
function getAdminLogado() {
    if(isset($_SESSION['admin_id'])) {
        return [
            'id' => $_SESSION['admin_id'],
            'nome' => $_SESSION['admin_nome'],
            'usuario' => $_SESSION['admin_usuario'],
            'nivel' => $_SESSION['admin_nivel']
        ];
    }
    return null;
}

// Obter dados do cliente logado
function getClienteLogado() {
    if(isset($_SESSION['cliente_id'])) {
        return [
            'id' => $_SESSION['cliente_id'],
            'nome' => $_SESSION['cliente_nome'],
            'email' => $_SESSION['cliente_email'],
            'usuario' => $_SESSION['cliente_usuario']
        ];
    }
    return null;
}
?>