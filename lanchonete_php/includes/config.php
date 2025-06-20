<?php
/**
 * Arquivo de configuração e conexão com o banco de dados
 * CardapioGO - Sistema de Lanchonete
 */

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'cardapiogo');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configurações gerais do sistema
define('SITE_NAME', 'CardapioGO');
define('SITE_SLOGAN', 'Seu cardápio digital em tempo real.');
define('SITE_URL', 'http://localhost');

// Configurações de sessão
session_start();

/**
 * Classe para conexão com o banco de dados
 */
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn = null;

    /**
     * Conecta ao banco de dados
     */
    public function connect() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Erro de conexão: " . $e->getMessage();
            die();
        }
        return $this->conn;
    }
}

/**
 * Função para obter conexão com o banco
 */
function getConnection() {
    $database = new Database();
    return $database->connect();
}

/**
 * Função para verificar se o usuário está logado
 */
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Função para redirecionar
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Função para formatar preço
 */
function formatPrice($price) {
    return 'R$ ' . number_format($price, 2, ',', '.');
}

/**
 * Função para sanitizar entrada
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Função para verificar senha (para fins acadêmicos, sem hash)
 */
function verifyPassword($password, $stored_password) {
    return $password === $stored_password;
}
?>

