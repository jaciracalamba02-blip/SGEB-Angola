<?php
/**
 * SGEB Angola - Funções Auxiliares
 */

// Limpar dados
function limparDados($dado) {
    $dado = trim($dado);
    $dado = stripslashes($dado);
    $dado = htmlspecialchars($dado);
    return $dado;
}

// Validar NIF (9 dígitos)
function validarNIF($nif) {
    $nif = preg_replace('/[^0-9]/', '', $nif);
    return strlen($nif) === 9;
}

// Validar telefone Angola
function validarTelefoneAngola($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    return strlen($telefone) === 9 && substr($telefone, 0, 1) === '9';
}

// Validar email
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Simular empréstimo
function simularEmprestimo($valor, $taxa, $prazo) {
    $taxaMensal = $taxa / 100;
    if($taxaMensal == 0) {
        $parcela = $valor / $prazo;
    } else {
        $parcela = $valor * ($taxaMensal * pow(1 + $taxaMensal, $prazo)) / (pow(1 + $taxaMensal, $prazo) - 1);
    }
    $total = $parcela * $prazo;
    $juros = $total - $valor;
    return ['parcela' => $parcela, 'total' => $total, 'juros' => $juros];
}

// Formatar moeda
function formatarMoeda($valor) {
    return 'KZ ' . number_format($valor, 2, ',', '.');
}

// Redirecionar com mensagem
function redirecionarComMensagem($url, $mensagem, $tipo = 'sucesso') {
    $_SESSION['mensagem'] = $mensagem;
    $_SESSION['tipo_mensagem'] = $tipo;
    header("Location: " . $url);
    exit();
}
?>