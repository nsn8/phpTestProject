<?php

include_once 'BaseProvider.php';
include_once 'Classes/HTTPSender.php';
include_once 'Classes/DatabaseRepository.php';
include_once 'Types/MessageStatus.php';
include_once 'Types/Provider.php';

class SmszatorProvider extends BaseProvider
{
    const REQUEST_URL = 'https://smszator.ru/multi.php?';

    /**
     * Отправляет смс сообщения
     *
     * @param array $messageIds
     */
    public function sendMessages(array $messageIds)
    {

    }

    /**
     * Проверяет статус сообщений
     */
    public function checkMessages()
    {
        $messages = $this->databaseRepository->getMessagesForTrackingByProvider();

        $trackingIdsString = implode(',', $messages);

        $getParameters = [
            'login'     => 'login',
            'password'  => 'password',
            'operation' => 'status',
            'sms_id'    => $trackingIdsString
        ];

        // Отправляем GET запрос
        $response = $this->httpSender->sendGetRequest(self::REQUEST_URL, $getParameters);

        // Получаем код ответа
        $responseCode = $this->httpSender->getRequestHttpCode();

        if ($responseCode == 200) {
            //Если все хорошо, обрабатываем ответ
            $responseXml = simplexml_load_string($response);

            // Проводим манипуляции из-за того, как simplexml парсит xml
            $smsIds = array_values((array)$responseXml)[0];

            foreach ($smsIds as $id) {
                $smsStatus = (array)$id->status[0];
                $trackingId = (array)$id->sms_id[0];

                $status = match ((string)$smsStatus) {
                    'DELIVERED'   => MessageStatus::STATUS_DELIVERED,
                    'UNDELIVERED' => MessageStatus::STATUS_UNDELIVERED,
                    'QUEUED'      => MessageStatus::STATUS_WAITING_FOR_UPDATE,
                    'ERROR'       => MessageStatus::STATUS_WAITING_FOR_REPEAT_SENDING
                };

                if ($status != MessageStatus::STATUS_WAITING_FOR_REPEAT_SENDING) {
                    $this->databaseRepository->updateTrackingStatus((int)$trackingId, $status);
                }
            }
        }
    }
}