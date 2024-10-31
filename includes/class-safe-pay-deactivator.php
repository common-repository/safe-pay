<?php
defined('ABSPATH') || exit;

class Safe_Pay_Deactivator
{
    /**
     * Завершение задач в кроне, при деактивации плагина
     */
    public static function deactivate()
    {
        wp_clear_scheduled_hook('safe_pay_cron_pay');
        wp_clear_scheduled_hook('safe_pay_cron_server');
    }

}
