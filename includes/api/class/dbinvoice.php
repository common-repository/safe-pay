<?php

namespace SafePay\Blockchain;

use WC_Order;
use wpdb;

defined('ABSPATH') || exit;

class DBInvoice implements IDBInvoice
{
    /**
     * Добавление инвойса в БД
     *
     * @param array $invoice
     *
     * @return int|bool
     */
    public static function addInvoice($invoice)
    {
        if (self::db()->insert(
            self::db_table(),
            $invoice,
            array(
                '%s',//STATUS
                '%s',//RECIPIENT
                '%s',//BANK_ID
                '%s',//EXPIRE
                '%s',//PAY_NUM
                '%d',//IS_TEST
                '%s',//DATE_CREATED
                '%s',//CREATOR
                '%s'//SITE_URL
            )
        )) {
            $order = new WC_Order($invoice['PAY_NUM']);
            $order->add_order_note(__('В банк отправлен счёт на оплату', 'safe-pay'));

            return self::db()->insert_id;
        } else {
            return false;
        }
    }

    /**
     * Обновление статуса инвойса
     *
     * @param int $id
     * @param string $typeID
     * @param string $status
     *
     * @return bool
     */
    public static function updateStatus($id, $typeID, $status)
    {
        if (self::db()->update(self::db_table(),
            array('STATUS' => $status, 'DATE_UPDATED' => time()),
            array($typeID => $id)
        )) {
            return true;
        } else {
            return false;
        }
    }

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
    public static function getInvoiceByID($ID, $typeID, $status, $getData = false)
    {
        $typeID  = esc_sql($typeID);
        $invoice = self::db()->get_row(
            self::db()->prepare(
                "SELECT * FROM " . self::db_table() . " WHERE STATUS = %s AND " . $typeID . " = %s",
                $status,
                $ID
            )
        );
        if ( ! empty($invoice)) {
            if ($getData) {
                return $invoice;
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Деактивация устаревших инвойсов
     *
     * @return bool
     */
    public static function deactivateExpiring()
    {
        $activeInvoices = self::getAllInvoice(self::STATUS_ACTIVE);
        if ($activeInvoices) {
            foreach ($activeInvoices as $item) {
                if (time() > $item->EXPIRE) {
                    self::updateStatus($item->ID, 'ID', self::STATUS_EXPIRED);
                    $order = new WC_Order($item->PAY_NUM);
                    $order->add_order_note(__('Время оплаты счёта истекло', 'safe-pay'));
                }
            }
        }

        return true;
    }

    /**
     * Получение инвойсов всех или отфильтрованных по статусу
     *
     * @param null|string $status
     *
     * @return array|object|bool
     */
    public static function getAllInvoice($status = null)
    {
        if ($status === null) {
            $invoices = self::db()->get_results(
                "SELECT ID, STATUS, RECIPIENT, BANK_ID, EXPIRE, PAY_NUM, IS_TEST, DATE_CREATED, DATE_UPDATED, CREATOR FROM " . self::db_table()
            );
        } else {
            $invoices = self::db()->get_results(
                self::db()->prepare(
                    "SELECT ID, EXPIRE, DATE_CREATED, PAY_NUM FROM " . self::db_table() . " WHERE STATUS = %s",
                    self::STATUS_ACTIVE
                )
            );
        }
        if (count($invoices) > 0) {
            return $invoices;
        } else {
            return false;
        }
    }
    /**
     * Возвращает название статуса
     *
     * @param string $status
     *
     * @return string|bool
     */
    public static function getStatusName($status)
    {
        $array = array(
            'active'   => __('Активна', 'safe-pay'),
            'finish'   => __('Оплачена', 'safe-pay'),
            'waiting'  => __('В ожидании', 'safe-pay'),
            'canceled' => __('Отменена', 'safe-pay'),
            'expired'  => __('Просрочена', 'safe-pay')
        );
        if ( ! empty($array[$status])) {
            return $array[$status];
        }

        return false;
    }

    /**
     * Подключение класса для работы с БД
     *
     * @return wpdb
     */
    private static function db()
    {
        global $wpdb;

        return $wpdb;
    }

    /**
     * Название таблицы
     *
     * @return string
     */
    private static function db_table()
    {
        return self::db()->prefix . 'safe_pay_invoice';
    }
}