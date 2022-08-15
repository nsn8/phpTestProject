<?php

include 'Classes/DatabaseRepository.php';
include 'Classes/SmszatorProvider.php';
include 'Classes/SmsnaviProvider.php';

class Sender
{
    private DatabaseRepository $databaseRepository;

    private SmszatorProvider $smszator;

    private SmsnaviProvider $smsnavi;

    /**
     * @param DatabaseRepository $databaseRepository
     * @param SmszatorProvider $smszator
     * @param SmsnaviProvider $smsnavi
     */
    public function __construct(DatabaseRepository $databaseRepository, SmszatorProvider $smszator, SmsnaviProvider $smsnavi)
    {
        $this->databaseRepository = $databaseRepository;
        $this->smszator = $smszator;
        $this->smsnavi = $smsnavi;
    }

    /**
     * Отправляет сообщения
     */
    public function sendMessages()
    {
        $rawMessageTexts = $this->databaseRepository->getMessagesForSending();

        $messageIds = [];

        foreach ($rawMessageTexts as $key => $value) {
            $messageIds[$value['id']] = $value['text'];
        }

        $this->smszator->sendMessages($messageIds);
        $this->smsnavi->sendMessages($messageIds);
    }
}