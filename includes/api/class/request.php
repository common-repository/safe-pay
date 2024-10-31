<?php

namespace SafePay\Blockchain;

defined('ABSPATH') || exit;

class Request
{

    const STATUS_OK = 'OK';
    const STATUS_ERROR = 'ERROR';

    /**
     * Отправка запроса по API
     *
     * @param string $urlPrefix
     * @param array|bool $param
     * @param bool|null $test
     *
     * @return array|bool
     */
    public function send($urlPrefix, $param = false, $test = null)
    {
        foreach (self::getAvailable($test) as $server) {
            $url      = $server->URL_SERVER . $urlPrefix;
            $response = self::makeCURl($url, $param);
            if ($response['STATUS'] == self::STATUS_OK) {
                return $response;
            }
        }

        return false;
    }

    /**
     * @param string $url
     * @param array|bool $param
     *
     * @return array
     */
    private static function makeCURl($url, $param = false)
    {
        $result = array();

        if ($param) {
            $url .= '?' . http_build_query($param);
        }
        $response = wp_remote_get($url, array(
            'timeout' => 10
        ));
        if (is_wp_error($response)) {
            $result['ERROR']  = $response->get_error_message();
            $result['STATUS'] = self::STATUS_ERROR;
        } elseif (wp_remote_retrieve_response_code($response) === 200) {
            $result['DATA']   = wp_remote_retrieve_body($response);
            $result['STATUS'] = self::STATUS_OK;
        } else {
            $result['ERROR']  = wp_remote_retrieve_response_code($response);
            $result['STATUS'] = self::STATUS_ERROR;
        }

        return $result;
    }

    /**
     * Получение серверов для API
     *
     * @param bool|null $test
     *
     * @return array
     */
    private static function getAvailable($test = null)
    {
        $arServers = array();
        if ($test === null) {
            $test = Options::isTest();
        }
        if ($test) {
            $dbServer = DBServer::getServers('test', 'desc');
        } else {
            $dbServer = DBServer::getServers('live', 'desc');
        }

        foreach ($dbServer as $item) {
            $arServers[] = $item;
        }

        return $arServers;
    }

}