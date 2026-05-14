<?php
/**
 * SGEB Angola - Configuração da Base de Dados
 * 
 * INSTRUÇÕES:
 * 1. Altere os dados abaixo conforme sua configuração do MySQL
 * 2. Execute o script database.sql no phpMyAdmin
 * 3. Verifique a conexão
 */

// =====================================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// =====================================================

// Servidor (geralmente localhost)
define('DB_HOST', 'localhost');

// Nome do banco de dados (deve ser o mesmo do SQL)
define('DB_NAME', 'sgeb_angola');

// Usuário do MySQL (padrão do XAMPP/WAMP é 'root')
define('DB_USER', 'root');

// Senha do MySQL (padrão do XAMPP é vazio, WAMP é vazio)
define('DB_PASS', '');

// Configuração de timezone para Angola
date_default_timezone_set('Africa/Luanda');

// =====================================================
// CLASSE DE CONEXÃO PDO (RECOMENDADO)
// =====================================================

class Database {
    private static $instance = null;
    private $connection;

    // Construtor privado (Singleton)
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // Lança exceções em erros
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Retorna array associativo
                    PDO::ATTR_EMULATE_PREPARES => false  // Previne SQL Injection
                ]
            );
        } catch(PDOException $e) {
            // Em caso de erro, mostra mensagem amigável
            die("❌ Erro ao conectar ao banco de dados: " . $e->getMessage());
        }
    }

    // Método para obter a instância única (Singleton)
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Método para obter a conexão PDO
    public function getConnection() {
        return $this->connection;
    }
}

// =====================================================
// FUNÇÕES AUXILIARES PARA CONSULTAS
// =====================================================

/**
 * Obtém a conexão com o banco de dados
 */
function getConexao() {
    return Database::getInstance()->getConnection();
}

/**
 * Executa uma query SQL com parâmetros
 * @param string $sql Query SQL com placeholders
 * @param array $params Parâmetros para bind
 * @return PDOStatement
 */
function executarQuery($sql, $params = []) {
    $stmt = getConexao()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Busca todos os registros de uma tabela
 * @param string $tabela Nome da tabela
 * @param string $condicao Condição WHERE (opcional)
 * @param array $params Parâmetros da condição
 */
function getAll($tabela, $condicao = "", $params = []) {
    $sql = "SELECT * FROM $tabela";
    if ($condicao) {
        $sql .= " WHERE $condicao";
    }
    return executarQuery($sql, $params)->fetchAll();
}

/**
 * Busca um registro por ID
 * @param string $tabela Nome da tabela
 * @param int $id Valor do ID
 * @param string $campo Nome do campo ID (padrão: id_{tabela})
 */
function getById($tabela, $id, $campo = null) {
    if ($campo === null) {
        $campo = "id_" . rtrim($tabela, 's');
    }
    $sql = "SELECT * FROM $tabela WHERE $campo = ?";
    return executarQuery($sql, [$id])->fetch();
}

/**
 * Insere um registro na tabela
 * @param string $tabela Nome da tabela
 * @param array $dados Array associativo [campo => valor]
 */
function inserir($tabela, $dados) {
    $campos = implode(", ", array_keys($dados));
    $placeholders = ":" . implode(", :", array_keys($dados));
    $sql = "INSERT INTO $tabela ($campos) VALUES ($placeholders)";
    $stmt = getConexao()->prepare($sql);
    return $stmt->execute($dados);
}

/**
 * Atualiza um registro na tabela
 * @param string $tabela Nome da tabela
 * @param array $dados Array associativo [campo => valor]
 * @param int $id Valor do ID
 * @param string $campo_id Nome do campo ID
 */
function atualizar($tabela, $dados, $id, $campo_id = null) {
    if ($campo_id === null) {
        $campo_id = "id_" . rtrim($tabela, 's');
    }
    
    $sets = [];
    foreach (array_keys($dados) as $campo) {
        $sets[] = "$campo = :$campo";
    }
    $sql = "UPDATE $tabela SET " . implode(", ", $sets) . " WHERE $campo_id = :id";
    $dados['id'] = $id;
    $stmt = getConexao()->prepare($sql);
    return $stmt->execute($dados);
}

/**
 * Exclui um registro da tabela
 * @param string $tabela Nome da tabela
 * @param int $id Valor do ID
 * @param string $campo_id Nome do campo ID
 */
function excluir($tabela, $id, $campo_id = null) {
    if ($campo_id === null) {
        $campo_id = "id_" . rtrim($tabela, 's');
    }
    $sql = "DELETE FROM $tabela WHERE $campo_id = ?";
    $stmt = getConexao()->prepare($sql);
    return $stmt->execute([$id]);
}

/**
 * Conta registros em uma tabela
 * @param string $tabela Nome da tabela
 * @param string $condicao Condição WHERE (opcional)
 * @param array $params Parâmetros da condição
 */
function contar($tabela, $condicao = "", $params = []) {
    $sql = "SELECT COUNT(*) as total FROM $tabela";
    if ($condicao) {
        $sql .= " WHERE $condicao";
    }
    $resultado = executarQuery($sql, $params)->fetch();
    return $resultado['total'];
}
?>