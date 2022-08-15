<?php

include 'Classes/DatabaseManager.php';
include 'Types/MessageStatus.php';
include 'Types/Provider.php';

class DatabaseRepository
{
    private DatabaseManager $databaseManager;

    private static DatabaseRepository $instance;

    /**
     * @param DatabaseManager $databaseManager
     * @return DatabaseRepository
     */
    public static function getInstance(DatabaseManager $databaseManager): DatabaseRepository
    {
        if (!isset(self::$instance)) {
            self::$instance = new static($databaseManager);
        }

        return self::$instance;
    }

    /**
     * @param DatabaseManager $databaseManager
     */
    private function __construct(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
    }

    /**
     * Получает список сообщений, которые нужно отправить
     *
     * @return array
     */
    public function getMessagesForSending(): array
    {
        $sql = '
            SELECT * FROM message_texts 
            WHERE id IN (SELECT DISTINCT text FROM messages WHERE status <= ' . MessageStatus::STATUS_WAITING_FOR_REPEAT_SENDING . ') 
        ';

        return $this->databaseManager->getData($sql);
    }

    /**
     * Получает сообщения, которые нужно отправить через определенного провайдера
     *
     * @param int $provider
     * @return array
     */
    public function getMessagesByProvider(int $provider = Provider::PROVIDER_SMSZATOR): array
    {
        $sql = '
            SELECT msg.id, msg.phone, msg.text FROM messages msg
            INNER JOIN users usr ON msg.user_id = usr.id
            WHERE msg.status <= ' . MessageStatus::STATUS_WAITING_FOR_REPEAT_SENDING . ' AND usr.provider = ' . $provider . ' 
            GROUP BY msg.text, msg.phone
        ';

        return $this->databaseManager->getData($sql);
    }

    /**
     * Обновляет статус сообщений
     *
     * @param array $updatedIds
     * @param int $status
     */
    public function updateMessagesStatus(array $updatedIds, int $status)
    {
        $ids = implode(',', $updatedIds);

        $sql = '
            UPDATE messages SET status = ' . $status . '
            WHERE id IN (' . $ids . ')
        ';

        $this->databaseManager->updateData($sql);
    }

    /**
     * Обновляет статус одного сообщения
     *
     * @param int $phone
     * @param int $textId
     * @param int $status
     * @param int|null $trackingId
     */
    public function updateOneMessageStatus(int $phone, int $textId, int $status, int $trackingId = null)
    {
        $sql = '
            UPDATE message SET status = ' . $status . ', trackingId = ' . $trackingId . '
            WHERE phone = ' . $phone . ' AND text = ' . $textId . ' AND status < ' . MessageStatus::STATUS_WAITING_FOR_UPDATE . '
        ';

        $this->databaseManager->updateData($sql);
    }

    /**
     * Получает список сообщений, у которых нужно обновить статус
     *
     * @param int $provider
     * @return array
     */
    public function getMessagesForTrackingByProvider(int $provider = Provider::PROVIDER_SMSZATOR): array
    {
        $sql = '
            SELECT msg.tracking_id FROM messages msg
            INNER JOIN users usr ON msg.user_id = usr.id
            WHERE msg.tracking_id IS NOT NULL AND msg.status = ' . MessageStatus::STATUS_WAITING_FOR_UPDATE . '
            AND usr.provider = ' . $provider . ' 
        ';

        return $this->databaseManager->getData($sql);
    }

    /**
     * Обновляет статус сообщения
     *
     * @param int $trackingId
     * @param int $status
     */
    public function updateTrackingStatus(int $trackingId, int $status)
    {
        $sql = '
        UPDATE messages SET status = ' . $status . ' WHERE trackingId = ' . $trackingId . '
        ';

        $this->databaseManager->updateData($sql);
    }
}