<?php

include_once 'Classes/HTTPSender.php';
include_once 'Classes/DatabaseRepository.php';
include_once 'Types/MessageStatus.php';
include_once 'Types/Provider.php';

class SmszatorProviderWithoutTracking extends SmszatorProvider
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

        $phones = $this->groupMessageData($messages, 'phone');

        $updatedMessages = $this->groupMessageData($messages, 'id');

        foreach ($phones as $key => $phoneArray) {
            $phonesString = implode(',', $phoneArray);

            $getParameters = [
                'login'       => 'login',
                'password'    => 'password',
                'phones'      => $phonesString,
                'message'     => $messageIds[$key],
                'want_sms_id' => 0 // Данный класс реализует вариант без возврата id для трекинга
            ];

            $response = $this->httpSender->sendGetRequest(self::REQUEST_URL, $getParameters);

            $responseCode = $this->httpSender->getRequestHttpCode();

            if ($responseCode == 200) {
                // Запрос прошел успешно
                if (!str_contains($response, 'ERROR')) {
                    // В результате запроса не было ошибок
                    $updateStatus = MessageStatus::STATUS_WAITING_FOR_UPDATE;
                } else {
                    // В результате запроса была ошибка
                    $updateStatus = MessageStatus::STATUS_WAITING_FOR_REPEAT_SENDING;
                }

            } else {
                // Запрос прошел неуспешно, надо попробовать отправить позже
                $updateStatus = MessageStatus::STATUS_WAITING_FOR_REPEAT_SENDING;
            }

            $this->databaseRepository->updateMessagesStatus($updatedMessages[$key], $updateStatus);

            $this->httpSender->closeSession();
        }
    }
}