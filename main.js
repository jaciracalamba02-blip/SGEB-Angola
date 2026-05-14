/**
 * SGEB - JavaScript Principal
 */

// Modal Functions
function abrirModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function fecharModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

document.addEventListener('click', function(event) {
    if (event.target.classList && event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
});

// Toggle Sidebar
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) sidebar.classList.toggle('open');
}

// Simulação de Empréstimo
function simularEmprestimo() {
    const valor = parseFloat(document.getElementById('valor_simulacao')?.value);
    const juros = parseFloat(document.getElementById('taxa_simulacao')?.value);
    const prazo = parseInt(document.getElementById('prazo_simulacao')?.value);
    
    if (isNaN(valor) || valor <= 0) {
        alert('Informe um valor válido');
        return;
    }
    if (isNaN(juros) || juros < 0) {
        alert('Informe uma taxa válida');
        return;
    }
    if (isNaN(prazo) || prazo <= 0) {
        alert('Informe um prazo válido');
        return;
    }
    
    const taxaMensal = juros / 100;
    const parcela = valor * (taxaMensal * Math.pow(1 + taxaMensal, prazo)) / (Math.pow(1 + taxaMensal, prazo) - 1);
    const totalPagar = parcela * prazo;
    const totalJuros = totalPagar - valor;
    
    const resultadoDiv = document.getElementById('resultado_simulacao');
    const textoDiv = document.getElementById('simulacao_texto');
    
    if (resultadoDiv && textoDiv) {
        textoDiv.innerHTML = `
            <strong>Resultado:</strong><br>
            Valor: KZ ${valor.toLocaleString('pt-BR', {minimumFractionDigits: 2})}<br>
            Taxa: ${juros}% ao mês<br>
            Prazo: ${prazo} meses<br>
            <strong>Parcela: KZ ${parcela.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</strong><br>
            Total a pagar: KZ ${totalPagar.toLocaleString('pt-BR', {minimumFractionDigits: 2})}<br>
            Total juros: KZ ${totalJuros.toLocaleString('pt-BR', {minimumFractionDigits: 2})}
        `;
        resultadoDiv.style.display = 'flex';
    }
}

// Máscaras
function mascaraCPF(input) {
    let valor = input.value.replace(/\D/g, '');
    if (valor.length <= 11) {
        valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
        valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
        valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        input.value = valor;
    }
}

function mascaraTelefone(input) {
    let valor = input.value.replace(/\D/g, '');
    if (valor.length <= 11) {
        valor = valor.replace(/(\d{2})(\d)/, '($1) $2');
        valor = valor.replace(/(\d{5})(\d)/, '$1-$2');
        input.value = valor;
    }
}

function mascaraValor(input) {
    let valor = input.value.replace(/\D/g, '');
    if (valor) {
        valor = (parseInt(valor) / 100).toFixed(2);
        input.value = valor;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[name="cpf"]').forEach(input => {
        input.addEventListener('input', function() { mascaraCPF(this); });
    });
    document.querySelectorAll('input[name="telefone"], input[name="celular"]').forEach(input => {
        input.addEventListener('input', function() { mascaraTelefone(this); });
    });
    document.querySelectorAll('input[data-mascara="valor"]').forEach(input => {
        input.addEventListener('input', function() { mascaraValor(this); });
    });
});

// Funções Gerais
function confirmarExclusao(mensagem) {
    return confirm(mensagem || 'Tem certeza que deseja excluir este registro?');
}

function exibirMensagem(tipo, mensagem) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo}`;
    alertDiv.innerHTML = `<i class="fas fa-${tipo === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i> ${mensagem}`;
    document.querySelector('.main-content .container-fluid').insertBefore(alertDiv, document.querySelector('.main-content .container-fluid').firstChild);
    setTimeout(() => alertDiv.remove(), 5000);
}

// Telefone deve ter 9 dígitos
if(telefoneNumeros.length !== 9) {
    mostrarMensagem('Telefone inválido! Digite um número válido com 9 dígitos (ex: 923123456).', 'erro');
    return false;
}

// Telefone deve começar com 9
if(!telefoneNumeros.startsWith('9')) {
    mostrarMensagem('Número de telefone deve começar com 9 (ex: 923 123 456)!', 'erro');
    return false;
}

// Se não estiver logado, redireciona para o login
if(!cliente_logado) {
    window.location.href = 'login-cliente.html';
}