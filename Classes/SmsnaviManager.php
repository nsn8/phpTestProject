<?php

class SmsnaviManager
{
    private DatabaseManager $databaseManager;

    public function __construct()
    {
        $this->databaseManager = new DatabaseManager();
    }

    public function sendMessages(array $messageIds)
    {
        // Получаем сообщения, сгруппированные по id текста и телефону
        $messages = $this->databaseManager->getMessagesByProvider(Providers::SMSNAVI_PROVIDER);

        // Начинаем отчет с самого маленького Id текста
        $count = $messages[array_key_first($messages)]['text'];

        // Массив с номерами телефонов по ключу Id текста
        $phones = [];
        // Массив с Id сообщений по ключу Id текста
        $updatedMessages = [];

        for ($i = 0; $i <= array_key_last($messages); $i++) {
            $phones[$count] = $phones[$count] ?? [];
            $updatedMessages[$count] = $updatedMessages[$count] ?? [];

            array_push($phones[$count], $messages[$i]['phone']);
            array_push($updatedMessages[$count], $messages[$i]['id']);

            // Если не последний, и у следующего другое сообщение, присваиваем счетчику Id следующего сообщения
            if ($i != array_key_last($messages) && $messages[$i + 1]['text'] != $messages[$i]['text']) {
                $count = $messages[$i + 1]['text'];
            }
        }

        foreach ($phones as $key => $phoneArray) {
            // Параметры для отправки
            $data = [
                'message'   => $messageIds[$key],
                'clientIds' => $phoneArray
            ];

            $postParameters = [
                'serviceId' => 'login',
                'pass'      => 'password',
                'data'      => json_encode($data)
            ];

            // Используем curl для отправки запроса
            $ch = curl_init('http://smsnavi.ru/send/');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postParameters);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $response = curl_exec($ch);

            // Получаем код ответа
            $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            switch ($responseCode) {
                case 200:
                    // Все прошло удачно
                    $clientIds = json_decode($response);
                    foreach ($clientIds as $phone => $clientId) {
                        if (str_contains($clientId['status'], 'Error')) {
                            // Что-то пошло не так, попытаемся отправить в будущем
                            $this->databaseManager->updateOneMessageStatus($phone, $key, MessageStatuses::WAITING_FOR_REPEAT_SENDING);
                        } else {
                            // Все прошло хорошо, ставим trackingId сообщениям
                            $this->databaseManager->updateOneMessageStatus($phone, $key, MessageStatuses::WAITING_FOR_STATUS_UPDATE, $clientId['track_id']);
                        }
                    }
                    break;
                case 400:
                    print_r('Отсутствуют обязательные параметры или они заданы некорректно');
                    break;
                case 401:
                    print_r('Передано неверное сочетание ServiceId/pass');
                    break;
                case 402:
                    print_r('Исчерпан остаток оплаченных сообщений');
                    break;
            }

            curl_close($ch);
        }
    }

    public function checkMessages()
    {
        // Получаем сообщения
        $messages = $this->databaseManager->getMessagesForTrackingByProvider(Providers::SMSNAVI_PROVIDER);

        // Оформляем в понятный рассыльщику вид
        $data = [
            'trackingIds' => $messages
        ];

        $postParameters = [
            'serviceId' => 'login',
            'pass'      => 'password',
            'data'      => json_encode($data)
        ];

        // Используем curl для отправки запроса
        $ch = curl_init('http://smsnavi.ru/status/');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postParameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $response = curl_exec($ch);

        $trackingIds = json_decode($response);

        foreach ($trackingIds as $id => $status) {
            $status = match ($status) {
                0 =>MessageStatuses::WAITING_FOR_STATUS_UPDATE,
                2 => MessageStatuses::STATUS_DELIVERED,
                5 => MessageStatuses::STATUS_UNDELIVERED,
            };

            if ($status != MessageStatuses::WAITING_FOR_STATUS_UPDATE) {
                $this->databaseManager->updateTrackingStatus($id, $status);
            }
        }

        curl_close($ch);
    }
}