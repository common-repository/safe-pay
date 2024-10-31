<?php

namespace SafePay\Blockchain;

use wpdb;

defined('ABSPATH') || exit;

class DBServer implements IDBServer
{
    /**
     * Получение списка серверов для API
     *
     * @param string $type
     * @param string $sort
     *
     * @return object|bool
     */
    public static function getServers($type, $sort)
    {
        if ($sort == 'desc') {
            $query = "SELECT ID, TYPE_SERVER, URL_SERVER, TIME_UPDATE FROM " . self::db_table() . " WHERE TYPE_SERVER = %s ORDER BY TIME_UPDATE DESC";
        } elseif ($sort == 'asc') {
            $query = "SELECT ID, TYPE_SERVER, URL_SERVER, TIME_UPDATE FROM " . self::db_table() . " WHERE TYPE_SERVER = %s ORDER BY TIME_UPDATE ASC";
        }
        $all_server = self::db()->get_results(
            self::db()->prepare(
                $query,
                $type
            )
        );
        if (count($all_server) > 0) {
            return $all_server;
        } else {
            return false;
        }
    }

    /**
     * Получаем все сервера
     *
     * @return object|bool
     */
    public static function getAllServers()
    {
        $all_server = self::db()->get_results(
            "SELECT ID, TYPE_SERVER, URL_SERVER, TIME_UPDATE FROM " . self::db_table()
        );
        if (count($all_server) > 0) {
            return $all_server;
        } else {
            return false;
        }
    }

    /**
     * Обновление доступности серверов
     *
     * @param int $id
     * @param int $time
     *
     * @return bool
     */
    public static function updateServer($id, $time)
    {
        $update = self::db()->update(
            self::db_table(),
            array('TIME_UPDATE' => $time),
            array('ID' => $id)
        );
        if ($update) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Добавление нового сервера
     *
     * @param array $server_data
     *
     * @return bool|int
     */
    public static function addServer($server_data)
    {
        if (self::db()->insert(
            self::db_table(),
            $server_data,
            array(
                '%s',//URL_SERVER
                '%s',//TYPE_SERVER
            )
        )) {
            return self::db()->insert_id;
        } else {
            return false;
        }
    }

    /**
     * Получение сервера по url
     *
     * @param string $url
     *
     * @return object|bool
     */
    public static function getServerByURL($url)
    {
        $server = self::db()->get_row(
            self::db()->prepare(
                "SELECT ID FROM " . self::db_table() . " WHERE URL_SERVER=%s",
                $url
            )
        );
        if ( ! empty($server)) {
            return $server;
        } else {
            return false;
        }
    }

    /**
     * Удаление сервера из списка
     *
     * @param int $id
     *
     * @return false|int
     */
    public static function deleteServer($id)
    {
        return self::db()->delete(self::db_table(), array('ID' => $id));
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
        return self::db()->prefix . 'safe_pay_server';
    }
}