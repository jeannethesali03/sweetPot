<?php
class Database
{
    private $host;
    private $db;
    private $user;
    private $password;
    private $charset;
    private $pdo;

    public function __construct()
    {
        $this->host = HOST;
        $this->db = DB;
        $this->user = USER;
        $this->password = PASSWORD;
        $this->charset = CHARSET;
    }

    public function connect()
    {
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->pdo = new PDO($dsn, $this->user, $this->password, $options);
            return $this->pdo;
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public function getConnection()
    {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
    }
}
?>