<?php
/** @noinspection SqlNoDataSourceInspection */
defined('ABSPATH') || exit;

class Safe_Pay_Update
{
    /**
     * Обновление базы данных
     */
    public static function update()
    {
        $table_name = self::db_table('safe_pay_server');
        self::db()->query("DROP TABLE IF EXISTS " . $table_name);

        $sql = self::add_db_server($table_name);

        update_option('safepay_db_version', SAFE_PAY_DB_VERSION);

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Получаем sql запрос на создание таблицы серверов
     *
     * @param string $table_name
     *
     * @return string
     */
    private static function add_db_server($table_name)
    {

        if (self::db()->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

            $sql = "CREATE TABLE " . $table_name . " (
                  ID int(10) NOT NULL AUTO_INCREMENT,
                  TYPE_SERVER varchar(10) NOT NULL,
                  URL_SERVER varchar(255) NOT NULL,
                  TIME_UPDATE varchar(13) NOT NULL,
                  PRIMARY KEY (ID)
             );";
            $sql .= self::add_db_server_data($table_name);

            return $sql;
        } else {

            return '';
        }

    }

    /**
     * Получаем sql запрос на создание данных в таблице серверов
     *
     * @param string $table_name
     *
     * @return string
     */
    private static function add_db_server_data($table_name)
    {
        $sql   = '';
        $array = array(
            array('type' => 'test', 'url' => 'http://89.235.184.229:9067'),
            array('type' => 'live', 'url' => 'http://165.22.172.56:9047'),
            array('type' => 'live', 'url' => 'http://165.22.225.38:9047'),
            array('type' => 'live', 'url' => 'http://134.209.215.110:9047'),
            array('type' => 'live', 'url' => 'http://157.245.42.15:9047'),
            array('type' => 'live', 'url' => 'http://157.245.32.12:9047'),
            array('type' => 'live', 'url' => 'http://157.245.32.149:9047'),
            array('type' => 'live', 'url' => 'http://157.245.46.6:9047'),
            array('type' => 'live', 'url' => 'http://157.245.38.233:9047'),
            array('type' => 'live', 'url' => 'http://157.245.38.36:9047'),
            array('type' => 'live', 'url' => 'http://167.71.122.91:9047'),
            array('type' => 'live', 'url' => 'http://167.71.120.41:9047'),
            array('type' => 'live', 'url' => 'http://157.245.46.39:9047'),
            array('type' => 'live', 'url' => 'http://157.245.33.79:9047'),
            array('type' => 'live', 'url' => 'http://165.22.236.136:9047'),
            array('type' => 'live', 'url' => 'http://157.245.40.1:9047'),
            array('type' => 'live', 'url' => 'http://157.245.38.143:9047'),
            array('type' => 'live', 'url' => 'http://134.209.244.94:9047'),
            array('type' => 'live', 'url' => 'http://157.245.39.195:9047'),
            array('type' => 'live', 'url' => 'http://165.22.5.227:9047'),
            array('type' => 'live', 'url' => 'http://157.230.102.157:9047'),
        );

        foreach ($array as $item) {
            $sql .= "INSERT INTO " . $table_name . " (TYPE_SERVER, URL_SERVER, TIME_UPDATE) VALUES ('" . $item['type'] . "','" . $item['url'] . "','0');";
        }

        return $sql;

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
     * @param string $table
     *
     * @return string
     */
    private static function db_table($table)
    {
        return self::db()->prefix . $table;
    }
}
