<?php

include 'Enums/Providers.php';
include 'Enums/MessageStatuses.php';

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

    public function getMessagesForSending(): array
    {
        $this->connect();

        $sql = '
            SELECT * FROM message_texts 
            WHERE id IN (SELECT DISTINCT text FROM messages WHERE status <= ' . MessageStatuses::WAITING_FOR_REPEAT_SENDING . ') 
        ';

        $result = $this->connection->query($sql);

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getMessagesByProvider(int $provider = Providers::SMSZATOR_PROVIDER)
    {
        $this->connect();

        $sql = '
            SELECT msg.id, msg.phone, msg.text FROM messages msg
            INNER JOIN users usr ON msg.user_id = usr.id
            WHERE msg.status <= ' . MessageStatuses::WAITING_FOR_REPEAT_SENDING . ' AND usr.provider = ' . $provider . ' 
            GROUP BY msg.text, msg.phone
        ';

        $result = $this->connection->query($sql);

        print_r($result->fetch_all(MYSQLI_ASSOC));

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function updateMessagesStatus(array $updatedIds, int $status)
    {
        $ids = implode(',', $updatedIds);

        $this->connect();

        $sql = '
            UPDATE messages SET status = ' . $status . '
            WHERE id IN (' . $ids . ')
        ';

        $this->connection->query($sql);
    }

    public function updateOneMessageStatus(int $phone, int $textId, int $status, int $trackingId = null)
    {
        $this->connect();

        $sql = '
            UPDATE message SET status = ' . $status . ', trackingId = ' . $trackingId . '
            WHERE phone = ' . $phone . ' AND text = ' . $textId . ' AND status < ' . MessageStatuses::WAITING_FOR_STATUS_UPDATE . '
        ';

        $this->connection->query($sql);
    }

    public function getMessagesForTrackingByProvider(int $provider = Providers::SMSZATOR_PROVIDER)
    {
        $this->connect();

        $sql = '
            SELECT msg.tracking_id FROM messages msg
            INNER JOIN users usr ON msg.user_id = usr.id
            WHERE msg.tracking_id IS NOT NULL AND msg.status = ' . MessageStatuses::WAITING_FOR_STATUS_UPDATE . '
            AND usr.provider = ' . $provider . ' 
        ';

        $result = $this->connection->query($sql);

        print_r($result->fetch_all(MYSQLI_ASSOC));

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function updateTrackingStatus(int $trackingId, int $status)
    {
        $this->connect();

        $sql = '
        UPDATE messages SET status = ' . $status . ' WHERE trackingId = ' . $trackingId . '
        ';

        $this->connection->query($sql);
    }

    public function initializeDatabase()
    {
        $this->connect();

        $sql = '
            CREATE TABLE IF NOT EXISTS message_texts(
                id int PRIMARY KEY AUTO_INCREMENT,
                text varchar(255)                                        
            )
        ';

        $this->connection->query($sql);

        $sql = '
            CREATE TABLE IF NOT EXISTS users(
                id int PRIMARY KEY AUTO_INCREMENT,
                provider int DEFAULT 0
            )
        ';

        $this->connection->query($sql);

        $sql = '
            CREATE TABLE IF NOT EXISTS messages(
                id int PRIMARY KEY AUTO_INCREMENT,
                user_id int,
                phone bigint,
                text int,
                status int,
                tracking_id bigint DEFAULT NULL 
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (text) REFERENCES message_texts(id) ON DELETE CASCADE ON UPDATE CASCADE
            )
        ';

        $this->connection->query($sql);

        $sql = '
            INSERT INTO users(provider) 
            VALUES (0),
                   (0),
                   (0),
                   (0),
                   (1),
                   (1),
                   (1)
        ';

        $this->connection->query($sql);

        $sql = '
            INSERT INTO message_texts(text) 
            VALUES ("Hello, this is first variation of text"),
                   ("Hi there! This is the second variation of text"),
                   ("This is third variation, less friendly")
        ';

        $this->connection->query($sql);

        $sql = '
            INSERT INTO messages(user_id, phone, text, status)
            VALUES (1, 79134321211, 1, 0),
                   (2, 74563332123, 1, 0),
                   (1, 79891234567, 1, 0),
                   (3, 79891234567, 2, 0),
                   (3, 79987655443, 2, 0),
                   (4, 75554345632, 3, 0),
                   (5, 76564445644, 3, 0),
                   (6, 78987654544, 1, 0),
                   (7, 76454434643, 1, 0),
                   (6, 79895954304, 2, 0),
                   (7, 79699696969, 3, 0),
                   (7, 72645473848, 3, 0)
        ';

        $this->connection->query($sql);
    }

    public function dropDatabase()
    {
        $this->connect();

        $sql = 'DROP TABLE IF EXISTS messages';

        $this->connection->query($sql);

        $sql = 'DROP TABLE IF EXISTS users';

        $this->connection->query($sql);

        $sql = 'DROP TABLE IF EXISTS message_texts';

        $this->connection->query($sql);
    }
}