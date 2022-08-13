<?php

class SmszatorManager
{
    private DatabaseManager $databaseManager;

    public function __construct()
    {
        $this->databaseManager = new DatabaseManager();
    }

    public function checkMessages()
    {
        $messages = $this->databaseManager->getMessagesForTrackingByProvider();

        $trackingIdsString = implode(',', $messages);

        $getParameters = [
            'login'     => 'login',
            'password'  => 'password',
            'operation' => 'status',
            'sms_id'    => $trackingIdsString
        ];

        // Используем curl для отправки запроса
        $ch = curl_init('https://smszator.ru/multi.php?' . http_build_query($getParameters));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $response = curl_exec($ch);

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
            $responseXml = simplexml_load_string($response);

            $smsIds = array_values((array)$responseXml)[0];

            foreach ($smsIds as $id) {
                $smsStatus = (array)$id->status[0];
                $trackingId = (array)$id->sms_id[0];

                $status = match ((string)$smsStatus) {
                    'DELIVERED'   => MessageStatuses::STATUS_DELIVERED,
                    'UNDELIVERED' => MessageStatuses::STATUS_UNDELIVERED,
                    'QUEUED'      => MessageStatuses::WAITING_FOR_STATUS_UPDATE,
                    'ERROR'       => MessageStatuses::WAITING_FOR_REPEAT_SENDING
                };

                if ($status != MessageStatuses::WAITING_FOR_REPEAT_SENDING) {
                    $this->databaseManager->updateTrackingStatus((int)$trackingId, $status);
                }
            }

        }
    }

    public function sendMessages(array $messageIds, int $wantSmsIds)
    {
        // Получаем сообщения, сгруппированные по id текста и телефону
        $messages = $this->databaseManager->getMessagesByProvider();

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
            $phonesString = implode(',', $phoneArray);

            $getParameters = [
                'login'       => 'login',
                'password'    => 'password',
                'phones'      => $phonesString,
                'message'     => $messageIds[$key],
                'want_sms_id' => $wantSmsIds
            ];

            // Используем curl для отправки запроса
            $ch = curl_init('https://smszator.ru/multi.php?' . http_build_query($getParameters));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $response = curl_exec($ch);

            // Запрос прошел успешно
            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
                if (!str_contains($response, 'ERROR')) {
                    $updateStatus = MessageStatuses::WAITING_FOR_STATUS_UPDATE;
                } else {
                    $updateStatus = MessageStatuses::WAITING_FOR_REPEAT_SENDING;
                }

                if ($wantSmsIds == 0) {
                    // Если не нужно хранить трекеры, обновляем все сразу
                    $this->databaseManager->updateMessagesStatus($updatedMessages, $updateStatus);
                } else {
                    // Иначе приходится по одному
                    $responseXml = simplexml_load_string($response);

                    // Из-за особенностей работы simplexml приходится парсить таким образом
                    $message_info = array_values((array)$responseXml->message_infos)[0];

                    foreach ($message_info as $info) {
                        $phone = (array)$info->phone;
                        $trackingId = (array)$info->sms_id;

                        $this->databaseManager->updateOneMessageStatus($phone[0], $key, MessageStatuses::WAITING_FOR_STATUS_UPDATE, $trackingId[0]);
                    }
                }
            } else {
                // Запрос прошел неуспешно, надо попробовать отправить позже
                $this->databaseManager->updateMessagesStatus($updatedMessages, MessageStatuses::WAITING_FOR_REPEAT_SENDING);
            }

            curl_close($ch);
        }
    }
}