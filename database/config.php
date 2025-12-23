<?php
/**
 * Arquivo de Configuração do Banco de Dados
 * 
 * Este arquivo contém as configurações de conexão com o banco de dados.
 * Certifique-se de atualizar as credenciais conforme seu ambiente.
 */

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');        // Endereço do servidor do banco de dados
define('DB_NAME', 'meu_banco');        // Nome do banco de dados
define('DB_USER', 'root');             // Usuário do banco de dados
define('DB_PASS', '');                 // Senha do banco de dados
define('DB_CHARSET', 'utf8mb4');       // Charset da conexão

// Configurações adicionais
define('DB_PORT', '3306');             // Porta do MySQL (padrão: 3306)

/**
 * Classe de Conexão com o Banco de Dados
 */
class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Construtor privado para implementar Singleton
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            die("Erro de conexão: " . $e->getMessage());
        }
    }
    
    /**
     * Obtém a instância única da classe (Singleton)
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Retorna a conexão PDO
     * 
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Previne clonagem da instância
     */
    private function __clone() {}
    
    /**
     * Previne deserialização da instância
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Função auxiliar para obter a conexão
 * 
 * @return PDO
 */
function getDB() {
    return Database::getInstance()->getConnection();
}

// Exemplo de uso:
// $db = getDB();
// $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
// $stmt->execute([1]);
// $usuario = $stmt->fetch();
?>
