<?php

namespace SafePay\Blockchain;

defined('ABSPATH') || exit;

interface IOptions
{
    /**
     * OptionsWordpress constructor.
     */
    public static function getPublicKey();

    /**
     * Получение публичного ключа
     *
     * @return string
     */
    public static function getPrivateKey();

    /**
     * Получение счёта
     *
     * @return string
     */
    public static function getAddress();

    /**
     * Получение срока жизни счета
     *
     * @return int
     */
    public static function getExpire();

    /**
     * Получение режима работы платежной системы
     *
     * @return int
     */
    public static function isTest();

    /**
     * Получение callback ссылки для банка, об успешной транзакции
     *
     * @return string
     */
    public static function getCallback();

    /**
     * Обновляет в настройках плагина последний проверенный блок
     *
     * @param int $block
     *
     * @return bool
     */
    public static function setLastBlock($block);

    /**
     * Получает последний блок из настроек плагина
     *
     * @return int
     */
    public static function getLastBlock();

    /**
     * Получает интервал для проверки оплаты через Cron
     *
     * @return int
     */
    public static function getPayInterval();

    /**
     * Получает интервал для проверки серверов через Cron
     *
     * @return int
     */
    public static function getServerInterval();

    /**
     * Обновляем статус в магазине на "Оплачено"
     *
     * @param int $order_id
     *
     * @return bool
     */
    public static function completedPay($order_id);

    /**
     * Получает шаблон для заголовка транзакции
     *
     * @param array $param
     *
     * @return string
     */
    public static function getTemplateTitle($param);

    /**
     * Получает шаблон для описания транзакции
     *
     * @param array $param
     *
     * @return string
     */
    public static function getTemplateDescription($param);
}