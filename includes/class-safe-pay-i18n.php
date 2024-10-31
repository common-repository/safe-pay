<?php
defined('ABSPATH') || exit;

class Safe_Pay_i18n
{

    /**
     * Загрузка textdomain для локализации
     */
    public function load_plugin_textdomain()
    {

        load_plugin_textdomain(
            'safe-pay',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );

    }

}
