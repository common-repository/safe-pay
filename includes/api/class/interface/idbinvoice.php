<?php

namespace SafePay\Blockchain;

defined('ABSPATH') || exit;

interface IDBInvoice
{

    const STATUS_ACTIVE = 'active';
    const STATUS_FINISH = 'finish';
    const STATUS_WAITING = 'waiting';
    const STATUS_CANCELED = 'canceled';
    const STATUS_EXPIRED = 'expired';

    /**
     * Добавление инвойса в БД
     *
     * @param array $invoice
     *
     * @return int|bool
     */
    public static function addInvoice($invoice);

    /**
     * Обновление статуса инвойса
     *
     * @param int $id
     * @param string $typeID
     * @param string $status
     *
     * @return bool
     */
    public static function updateStatus($id, $typeID, $status);

    /**
     * Проверка существования инвойса по ID заказа/инвойса и статусу
     *
     * @param int|string $ID
     * @param string $typeID
     * @param string $status
     * @param bool $getData
     *
     * @return bool|object
     */
    public static function getInvoiceByID($ID, $typeID, $status, $getData);

    /**
     * Деактивация устаревших инвойсов
     *
     * @return bool
     */
    public static function deactivateExpiring();

    /**
     * Получение инвойсов всех или отфильтрованных по статусу
     *
     * @param null|string $status
     *
     * @return object|bool
     */
    public static function getAllInvoice($status);

}