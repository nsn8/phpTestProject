<?php

abstract class BaseProvider
{
    protected DatabaseRepository $databaseRepository;
    protected HTTPSender $httpSender;

    /**
     * @param DatabaseRepository $databaseRepository
     * @param HTTPSender $httpSender
     */
    public function __construct(DatabaseRepository $databaseRepository, HTTPSender $httpSender)
    {
        $this->databaseRepository = $databaseRepository;
        $this->httpSender = $httpSender;
    }

    /**
     * Группирует нужные данные по Id сообщения
     *
     * @param array $messages
     * @param string $field
     * @return array
     */
    protected function groupMessageData(array $messages, string $field): array
    {
        // Начинаем отчет с самого маленького Id текста
        $count = $messages[array_key_first($messages)]['text'];

        $result = [];

        for ($i = 0; $i <= array_key_last($messages); $i++) {
            $result[$count] = $result[$count] ?? [];

            array_push($result[$count], $messages[$i][$field]);

            // Если не последний, и у следующего другое сообщение, присваиваем счетчику Id следующего сообщения
            if ($i != array_key_last($messages) && $messages[$i + 1]['text'] != $messages[$i]['text']) {
                $count = $messages[$i + 1]['text'];
            }
        }

        return $result;
    }

    public abstract function sendMessages(array $messageIds);

    public abstract function checkMessages();
}