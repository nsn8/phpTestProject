<?php

include 'Classes/DatabaseManager.php';
include 'Classes/SmszatorManager.php';
include 'Classes/SmsnaviManager.php';

class Sender
{
    private DatabaseManager $databaseManager;

    private SmszatorManager $smszator;

    private SmsnaviManager $smsnavi;

    public function __construct()
    {
        $this->databaseManager = new DatabaseManager();
        $this->smszator = new SmszatorManager();
        $this->smsnavi = new SmsnaviManager();
    }

    public function sendMessages(int $wantSmsIds = 0)
    {
        $rawMessageTexts = $this->databaseManager->getMessagesForSending();

        $messageIds = [];

        foreach ($rawMessageTexts as $key => $value) {
            $messageIds[$value['id']] = $value['text'];
        }

        $this->smszator->sendMessages($messageIds, $wantSmsIds);
        $this->smsnavi->sendMessages($messageIds);
    }
}