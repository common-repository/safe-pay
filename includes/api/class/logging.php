<?php

namespace SafePay\Blockchain;

defined('ABSPATH') || exit;

class Logging
{
    private $log;
    private static $instance = null;

    /**
     * Проверяет существование объекта, если существует то направляет на него, если нет, то создаёт новый
     *
     * @return Logging
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __clone()
    {
    }

    private function __construct()
    {
    }

    /**
     * При уничтожении объекта, записываем все собранные логи в файл
     */
    function __destruct()
    {
        if ($this->log > 0) {
            foreach ($this->log as $key => $value) {
                $this->$key($value);
            }
        }
    }

    /**
     * Добавление логов в массив
     *
     * @param string $type
     * @param string $text
     * @param null|int $order_id
     */
    public function log($type, $text, $order_id = null)
    {
        if ($order_id !== null) {
            $text = '[' . __('Заказ №', 'safe-pay') . $order_id . '] ' . $text;
        }
        $this->log[$type][] = $text;
    }

    /**
     * Добавление логирования выполнения задач в Cron
     *
     * @param array $text
     */
    private function cron_log($text)
    {
        $path = SAFE_PAY_PATH . 'includes/api/log/cron/';
        $file = date('WmY') . '_cron.log';

        $this->add_log($path, $file, $text);
    }

    /**
     * Добавление логирования базовых функций SAFE PAY
     *
     * @param array $text
     */
    private function sign_log($text)
    {
        $path = SAFE_PAY_PATH . 'includes/api/log/';
        $file = 'sign.log';

        $this->add_log($path, $file, $text);
    }

    /**
     * Запись логов в файл
     *
     * @param string $path
     * @param string $file
     * @param array $text
     */
    private function add_log($path, $file, $text)
    {
        $end_text = '';
        foreach ($text as $text_item) {
            $end_text .= '[' . date('d.m.Y H:i:s') . ']' . $text_item . PHP_EOL;
        }

        file_put_contents($path . $file, $end_text, FILE_APPEND);
    }
}