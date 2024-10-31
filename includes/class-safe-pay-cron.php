<?php

defined('ABSPATH') || exit;

use \SafePay\Blockchain\Options;
use \SafePay\Blockchain\Cron;

class Safe_Pay_Cron
{
    /**
     * Добавляем новый интервал для задачи проверки поступления оплаты
     *
     * @param array $schedules
     *
     * @return array
     */
    public function add_pay_interval($schedules)
    {
        $interval = Options::getPayInterval();

        $schedules['pay_interval'] = array(
            'interval' => 60 * (int)$interval,
            'display'  => (int)$interval . ' min'
        );

        return $schedules;
    }

    /**
     * Добавляем новый интервал для задачи проверки серверов
     *
     * @param array $schedules
     *
     * @return array
     */
    public function add_server_interval($schedules)
    {
        $interval = Options::getServerInterval();

        $schedules['server_interval'] = array(
            'interval' => 60 * (int)$interval,
            'display'  => (int)$interval . ' min'
        );

        return $schedules;
    }

    /**
     * Функция запуска проверки поступления оплат
     */
    public function add_safe_pay_cron_pay()
    {
        $cron = new Cron();
        $cron->resultPay();
    }

    /**
     * Функция запуска проверки серверов
     */
    public function add_safe_pay_cron_server()
    {
        $cron = new Cron();
        $cron->availableServers();
    }
}