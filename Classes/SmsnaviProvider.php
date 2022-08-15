<?php

include_once 'BaseProvider.php';
include_once 'Classes/HTTPSender.php';
include_once 'Classes/DatabaseRepository.php';
include_once 'Types/MessageStatus.php';
include_once 'Types/Provider.php';

class SmsnaviProvider extends BaseProvider
{
    const REQUEST_URL_SEND = 'http://smsnavi.ru/send/';
    const REQUEST_URL_CHECK = 'http://smsnavi.ru/status/';

    /**
     * Отправляет сообщения
     *
     * @param array $messageIds
     */
    public function sendMessages(array $messageIds)
    {
        $messages = $this->databaseRepository->getMessagesByProvider(Provider::PROVIDER_SMSNAVI);

        // Массив с номерами телефонов по ключу Id текста
        $phones = $this->groupMessageData($messages, 'phone');

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

            // Отправляем POST запрос
            $response = $this->httpSender->sendPostRequest(self::REQUEST_URL_SEND, $postParameters);

            $responseCode = $this->httpSender->getRequestHttpCode();

            // Проверяем что пришло в результате
            switch ($responseCode) {
                case 200:
                    // Все прошло удачно
                    $clientIds = json_decode($response);
                    foreach ($clientIds as $phone => $clientId) {
                        if (str_contains($clientId['status'], 'Error')) {
                            // Что-то пошло не так, попытаемся отправить в будущем
                            $this->databaseRepository->updateOneMessageStatus($phone, $key, MessageStatus::STATUS_WAITING_FOR_REPEAT_SENDING);
                        } else {
                            // Все прошло хорошо, ставим trackingId сообщениям
                            $this->databaseRepository->updateOneMessageStatus($phone, $key, MessageStatus::STATUS_WAITING_FOR_UPDATE, $clientId['track_id']);
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
        }

        $this->httpSender->closeSession();
    }

    /**
     * Проверяет статус сообщений
     */
    public function checkMessages()
    {
        // Получаем сообщения
        $messages = $this->databaseRepository->getMessagesForTrackingByProvider(Provider::PROVIDER_SMSNAVI);

        // Оформляем в понятный рассыльщику вид
        $data = [
            'trackingIds' => $messages
        ];

        $postParameters = [
            'serviceId' => 'login',
            'pass'      => 'password',
            'data'      => json_encode($data)
        ];

        $response = $this->httpSender->sendPostRequest(self::REQUEST_URL_CHECK, $postParameters);

        $trackingIds = json_decode($response);

        foreach ($trackingIds as $id => $status) {
            $status = match ($status) {
                0 => MessageStatus::STATUS_WAITING_FOR_UPDATE,
                2 => MessageStatus::STATUS_DELIVERED,
                5 => MessageStatus::STATUS_UNDELIVERED,
            };

            if ($status != MessageStatus::STATUS_WAITING_FOR_UPDATE) {
                $this->databaseRepository->updateTrackingStatus($id, $status);
            }
        }

        $this->httpSender->closeSession();
    }
}