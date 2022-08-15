<?php

class DatabaseManager
{
    const DB_HOST = 'localhost';
    const DB_USERNAME = 'root';
    const DB_PASSWORD = 'NEW_PASSWORD';
    const DB_NAME = 'sms_users';

    private $host;
    private $username;
    private $password;
    private $name;

    private mysqli $connection;

    private static DatabaseManager $instance;

    private function __construct() { }

    /**
     * @return DatabaseManager
     */
    public static function getInstance(): DatabaseManager
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Устанавливает соединения с базой данных
     *
     * @param null $host
     * @param null $username
     * @param null $password
     * @param null $name
     */
    private function connect($host = null, $username = null, $password = null, $name = null)
    {
        $this->host = $host ?? self::DB_HOST;
        $this->username = $username ?? self::DB_USERNAME;
        $this->password = $password ?? self::DB_PASSWORD;
        $this->name = $name ?? self::DB_NAME;

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $this->connection = new mysqli($this->host, $this->username, $this->password, $this->name);

        $this->connection->set_charset('utf8mb4');
    }

    /**
     * Получает массив данных из базы данных
     *
     * @param string $sqlQuery
     * @return array
     */
    public function getData(string $sqlQuery): array
    {
        $this->connect();

        $result = $this->connection->query($sqlQuery);

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Делает запрос в базу без получения данных
     *
     * @param string $sqlQuery
     */
    public function updateData(string $sqlQuery)
    {
        $this->connect();

        $this->connection->query($sqlQuery);
    }
}