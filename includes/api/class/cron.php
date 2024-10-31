<?php

namespace SafePay\Blockchain;

defined('ABSPATH') || exit;

class Cron
{
    private $logging;

    function __construct()
    {
        $this->logging = Logging::getInstance();
    }

    /**
     * Задача на проверку поступление оплаты
     */
    public function resultPay()
    {
        $process = new Process();
        $process->resultPay();

        $this->logging->log('cron_log', __('Запустилась задача на проверку поступления оплаты', 'safe-pay'));
    }

    /**
     * Задача на проверку серверов по дате последнего блока
     */
    public function availableServers()
    {
        $dbServer = DBServer::getAllServers();
        foreach ($dbServer as $item) {
            $url      = $item->URL_SERVER . "/api/lastblock";
            $response = wp_remote_get($url, array(
                'timeout' => 3
            ));
            if ( ! is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $result = json_decode(wp_remote_retrieve_body($response), true);

                if ($result["timestamp"] > $item->TIME_UPDATE) {
                    DBServer::updateServer($item->ID, $result["timestamp"]);
                }
            }
        }

        $this->logging->log('cron_log', __('Запустилась задача на проверку серверов', 'safe-pay'));
    }

}