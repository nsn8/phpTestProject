<?php

include_once 'Classes/DatabaseManager.php';
include_once 'Classes/DatabaseRepository.php';
include_once 'Classes/DatabasePopulator.php';
include_once 'Classes/Sender.php';
include_once 'Classes/Checker.php';
include_once 'Classes/SmsnaviProvider.php';
include_once 'Classes/SmszatorProviderWithTracking.php';
include_once 'Classes/SmszatorProviderWithoutTracking.php';
include_once 'Classes/HTTPSender.php';

unset($argv[0]);

$modeArguments = explode('=', $argv[1]);

$mode = $modeArguments[1];

print_r($mode . PHP_EOL);

// По умолчанию не сохраняем трекеры
$wantSmsIds = 0;

if (isset($argv[2])) {
    $wantSmsIdsArguments = explode('=', $argv[2]);

    $wantSmsIds = $wantSmsIdsArguments[1];
}

$databaseManager = DatabaseManager::getInstance();
$databaseRepository = DatabaseRepository::getInstance($databaseManager);
$databasePopulator = DatabasePopulator::getInstance($databaseManager);
$httpSender = HTTPSender::getInstance();

$smsnavi = new SmsnaviProvider($databaseRepository, $httpSender);

$smszator = $wantSmsIds == 1 ? new SmszatorProviderWithTracking($databaseRepository, $httpSender) :
    new SmszatorProviderWithoutTracking($databaseRepository, $httpSender);

switch ($mode) {
    case 'send':
        print_r('Сообщения отправляются' . PHP_EOL);
        $sender = new Sender($databaseRepository, $smszator, $smsnavi);
        $sender->sendMessages();
        print_r('Сообщения отправлены' . PHP_EOL);
        break;
    case 'checkstatus':
        print_r('Статусы проверяются'. PHP_EOL);
        $checker = new Checker($smszator, $smsnavi);
        $checker->checkMessages();
        print_r('Данные о сообщениях обновлены');
        break;
    case 'database':
        $databasePopulator->initializeDatabase();
        print_r('База данных инициализирована' . PHP_EOL);
        break;
    case 'drop':
        $databasePopulator->dropDatabase();
        break;
    default:
        print_r('Неправильная команда' . PHP_EOL);
        break;
}
