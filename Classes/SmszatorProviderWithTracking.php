<?php

include_once 'Classes/HTTPSender.php';
include_once 'Classes/DatabaseRepository.php';
include_once 'Types/MessageStatus.php';
include_once 'Types/Provider.php';

class SmszatorProviderWithTracking extends SmszatorProvider
{
    /**
     * Отправляет сообщения
     *
     * @param array $messageIds
     */
    public function sendMessages(array $messageIds)
    {
        parent::sendMessages($messageIds);

        $messages = $this->databaseRepository->getMessagesByProvider();

        // Массив с номерами телефонов по ключу Id текста
        $phones = $this->groupMessageData($messages, 'phone');

        foreach ($phones as $key => $phoneArray) {
            $phonesString = implode(',', $phoneArray);

            $getParameters = [
                'login'       => 'login',
                'password'    => 'password',
                'phones'      => $phonesString,
                'message'     => $messageIds[$key],
                'want_sms_id' => 1 // Данный класс реализует вариант с возвратом id для трекинга
            ];

            $response = $this->httpSender->sendGetRequest(self::REQUEST_URL, $getParameters);

            $responseCode = $this->httpSender->getRequestHttpCode();

            if ($responseCode == 200) {
                if (!str_contains($response, 'ERROR')) {
                    $updateStatus = MessageStatus::STATUS_WAITING_FOR_UPDATE;
                } else {
                    $updateStatus = MessageStatus::STATUS_WAITING_FOR_REPEAT_SENDING;
                }

                // Поскольку все трекеры уникальные, приходится обновлять по одному
                $responseXml = simplexml_load_string($response);

                // Из-за особенностей работы simplexml приходится парсить таким образом
                $message_info = array_values((array)$responseXml->message_infos)[0];

                foreach ($message_info as $info) {
                    $phone = (array)$info->phone;
                    $trackingId = (array)$info->sms_id;

                    $this->databaseRepository->updateOneMessageStatus($phone[0], $key, MessageStatus::STATUS_WAITING_FOR_UPDATE, $trackingId[0]);
                }
            } else {
                // Запрос прошел неуспешно, надо попробовать отправить позже
                $updatedMessages = $this->groupMessageData($messages, 'id');

                $this->databaseRepository->updateMessagesStatus($updatedMessages[$key], MessageStatus::STATUS_WAITING_FOR_REPEAT_SENDING);
            }

            $this->httpSender->closeSession();
        }
    }
}