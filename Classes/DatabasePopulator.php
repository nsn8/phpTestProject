<?php

include 'Classes/DatabaseManager.php';

class DatabasePopulator
{
    private DatabaseManager $databaseManager;

    private static DatabasePopulator $instance;

    private function __construct(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
    }

    public static function getInstance(DatabaseManager $databaseManager): DatabasePopulator
    {
        if (!isset(self::$instance)) {
            self::$instance = new static($databaseManager);
        }

        return self::$instance;
    }

    /**
     * Создает таблицы и наполняет их данными
     */
    public function initializeDatabase()
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS message_texts(
                id int PRIMARY KEY AUTO_INCREMENT,
                text varchar(255)                                        
            )
        ';

        $this->databaseManager->updateData($sql);

        $sql = '
            CREATE TABLE IF NOT EXISTS users(
                id int PRIMARY KEY AUTO_INCREMENT,
                provider int DEFAULT 0
            )
        ';

        $this->databaseManager->updateData($sql);

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

        $this->databaseManager->updateData($sql);

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

        $this->databaseManager->updateData($sql);

        $sql = '
            INSERT INTO message_texts(text) 
            VALUES ("Hello, this is first variation of text"),
                   ("Hi there! This is the second variation of text"),
                   ("This is third variation, less friendly")
        ';

        $this->databaseManager->updateData($sql);

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

        $this->databaseManager->updateData($sql);
    }

    /**
     * Удаляет таблицы
     */
    public function dropDatabase()
    {
        $sql = 'DROP TABLE IF EXISTS messages';

        $this->databaseManager->updateData($sql);

        $sql = 'DROP TABLE IF EXISTS users';

        $this->databaseManager->updateData($sql);

        $sql = 'DROP TABLE IF EXISTS message_texts';

        $this->databaseManager->updateData($sql);
    }

}