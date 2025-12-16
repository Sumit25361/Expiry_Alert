<?php
class Database
{
    private $host;
    private $username;
    private $password;
    private $database;
    private $connection;

    public function __construct()
    {
        // Auto-detect environment
        if ($_SERVER['SERVER_NAME'] == 'sql107.infinityfree.com' || strpos($_SERVER['HTTP_HOST'] ?? '', 'infinityfree') !== false) {
            // Production Credentials (InfinityFree)
            $this->host = "sql107.infinityfree.com";
            $this->username = "if0_40689940";
            $this->password = "Sumitkb123";
            $this->database = "if0_40689940_edr";
        } else {
            // Local Development
            $this->host = "localhost";
            $this->username = "root";
            $this->password = "";
            $this->database = "EDR";
        }

        $this->connect();
    }

    private function connect()
    {
        try {
            $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);

            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }

            $this->connection->set_charset("utf8");
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function prepare($query)
    {
        return $this->connection->prepare($query);
    }

    public function query($query)
    {
        return $this->connection->query($query);
    }

    public function escape($string)
    {
        return $this->connection->real_escape_string($string);
    }

    public function close()
    {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}
?>