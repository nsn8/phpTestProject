<?php

include 'Classes/DatabaseManager.php';
include 'Classes/SmszatorManager.php';
include 'Classes/SmsnaviManager.php';

class Checker
{
    private SmszatorManager $smszator;

    private SmsnaviManager $smsnavi;

    public function __construct()
    {
        $this->databaseManager = new DatabaseManager();
        $this->smszator = new SmszatorManager();
        $this->smsnavi = new SmsnaviManager();
    }

    public function checkMessages()
    {
        $this->smszator->checkMessages();
        $this->smsnavi->checkMessages();
    }
}