<?php

include 'Classes/DatabaseManager.php';
include 'Classes/Sender.php';
include 'Classes/Checker.php';

unset($argv[0]);

$argv = explode('=', $argv[1]);

$mode = $argv[1];

print_r($mode . PHP_EOL);

$databaseManager = new DatabaseManager();

switch ($mode) {
    case 'send':
        print_r('Сообщения отправляются' . PHP_EOL);
        $sender = new Sender();
        $sender->sendMessages();
        print_r('Сообщения отправлены' . PHP_EOL);
        break;
    case 'checkstatus':
        print_r('Статусы проверяются'. PHP_EOL);
        $checker = new Checker();
        $checker->checkMessages();
        print_r('Данные о сообщениях обновлены');
        break;
    case 'database':
        $databaseManager->initializeDatabase();
        print_r('База данных инициализирована' . PHP_EOL);
        break;
    case 'drop':
        $databaseManager->dropDatabase();
        break;
    default:
        print_r('Неправильная команда' . PHP_EOL);
        break;
}
