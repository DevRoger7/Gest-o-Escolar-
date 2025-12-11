<?php

class Database {
    private static $instance = null;
    private $connection;
    private $host;
    private $dbname;
    private $username;
    private $password;

    private function __construct() {
        $this->host = 'localhost';
        $this->dbname = 'escola_merenda';
        $this->username = 'root';
        $this->password = '';
        $this->connect();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect() {
        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8",
                $this->username,
                $this->password
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Garantir que autocommit está habilitado por padrão (será desabilitado nas transações)
            $this->connection->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
        } catch (PDOException $e) {
            die("Erro na conexão: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->connection;
    }
}

// Auto-incluir system_helper se não foi incluído
if (!function_exists('getNomeSistema')) {
    require_once(__DIR__ . '/system_helper.php');
}

?>