<?php

namespace SafePay\Blockchain;

use wpdb;

defined('ABSPATH') || exit;

class DBRecipient implements IDBRecipient
{
    /**
     * Получение реципиента по аттрибуту
     *
     * @param string $attr
     *
     * @return object|bool
     */
    public static function getByAttribute($attr)
    {
        if ($recipient = self::db()->get_row(
            self::db()->prepare(
                "SELECT NAME, ATTRIBUTE, BILD, PUBLIC_KEY, PAY_URL, APP_ANDROID, APP_IOS, PICTURE_URL FROM " . self::db_table() . " WHERE ATTRIBUTE = %s",
                $attr
            )
        )) {
            return $recipient;
        } else {
            return false;
        }
    }

    /**
     * Получение всех реципиентов
     * @return object|bool
     */
    public static function getListAll()
    {
        $recipient = self::db()->get_results("SELECT NAME, ATTRIBUTE, BILD, PUBLIC_KEY, PAY_URL, APP_ANDROID, APP_IOS, PICTURE_URL FROM " . self::db_table());
        if (count($recipient) > 0) {
            return $recipient;
        } else {
            return false;
        }
    }

    /**
     * Подключение класса для работы с БД
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
        return self::db()->prefix . 'safe_pay_recipient';
    }
}