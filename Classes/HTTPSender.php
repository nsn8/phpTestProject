<?php

class HTTPSender
{
    private $handle = null;

    public static HTTPSender $instance;

    /**
     * @return HTTPSender|static
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    private function __construct() { }

    /**
     * Инициализирует curl
     */
    private function initializeCurl()
    {
        $this->handle = curl_init();

        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->handle, CURLOPT_HEADER, false);
    }

    /**
     * Отправляет GET запрос
     *
     * @param string $url
     * @param array $parameters
     * @return bool|string
     */
    public function sendGetRequest(string $url, array $parameters)
    {
        $this->initializeCurl();

        curl_setopt($this->handle, CURLOPT_URL, $url . http_build_query($parameters));

        return curl_exec($this->handle);
    }

    /**
     * Отправляет POST запрос
     *
     * @param string $url
     * @param array $parameters
     * @return bool|string
     */
    public function sendPostRequest(string $url, array $parameters)
    {
        $this->initializeCurl();

        curl_setopt($this->handle, CURLOPT_URL, $url);
        curl_setopt($this->handle, CURLOPT_POST, 1);
        curl_setopt($this->handle, CURLOPT_POSTFIELDS, $parameters);

        return curl_exec($this->handle);
    }

    /**
     * Получает HTTP код ответа
     *
     * @return false|mixed
     */
    public function getRequestHttpCode()
    {
        return isset($this->handle) ? curl_getinfo($this->handle, CURLINFO_HTTP_CODE) : false;
    }

    /**
     * Закрывает сессию
     */
    public function closeSession()
    {
        if (isset($this->handle)) {
            curl_close($this->handle);

            unset($this->handle);
        }
    }
}