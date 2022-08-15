<?php

include_once 'Classes/SmszatorProvider.php';
include_once 'Classes/SmsnaviProvider.php';

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