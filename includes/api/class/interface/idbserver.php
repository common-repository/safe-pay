<?php

namespace SafePay\Blockchain;

defined('ABSPATH') || exit;

interface IDBServer
{
    /**
     * Получение списка серверов для API
     *
     * @param string $type
     * @param string $sort
     *
     * @return object|bool
     */
    public static function getServers($type, $sort);

    /**
     * Получаем все сервера
     *
     * @return object|bool
     */
    public static function getAllServers();

    /**
     * Обновление доступности серверов
     *
     * @param int $id
     * @param int $time
     *
     * @return bool
     */
    public static function updateServer($id, $time);

    /**
     * Добавление нового сервера
     *
     * @param array $server_data
     *
     * @return bool|int
     */
    public static function addServer($server_data);

    /**
     * Получение сервера по url
     *
     * @param string $url
     *
     * @return object|bool
     */
    public static function getServerByURL($url);

    /**
     * Удаление сервера из списка
     *
     * @param int $id
     *
     * @return false|int
     */
    public static function deleteServer($id);

}