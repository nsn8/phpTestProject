<?php

include 'Classes/SmszatorProvider.php';
include 'Classes/SmsnaviProvider.php';

class Checker
{
    private SmszatorProvider $smszator;

    private SmsnaviProvider $smsnavi;

    /**
     * @param SmszatorProvider $smszator
     * @param SmsnaviProvider $smsnavi
     */
    public function __construct(SmszatorProvider $smszator, SmsnaviProvider $smsnavi)
    {
        $this->smszator = $smszator;
        $this->smsnavi = $smsnavi;
    }

    /**
     * Проверяет статус сообщений
     */
    public function checkMessages()
    {
        $this->smszator->checkMessages();
        $this->smsnavi->checkMessages();
    }
}