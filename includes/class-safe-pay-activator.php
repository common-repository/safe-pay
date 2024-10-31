<?php
defined('ABSPATH') || exit;

class Safe_Pay_Activator
{
    /**
     * Устанавливаем таблицы БД и добавляем задачи в Cron
     */
    public static function activate()
    {
        if (empty(get_option('safepay_db_version'))) {
            add_option('safepay_db_version', SAFE_PAY_DB_VERSION);
        }
        self::install_db_table();
    }

    /**
     * Создание баз данных
     */
    private static function install_db_table()
    {
        $sql = '';
        $sql .= self::add_db_server();
        $sql .= self::add_db_invoice();
        $sql .= self::add_db_recipient();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Получаем sql запрос на создание таблицы серверов
     *
     * @return string
     */
    private static function add_db_server()
    {

        $table_name = self::db_table('safe_pay_server');
        if (self::db()->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

            $sql = "CREATE TABLE " . $table_name . " (
                  ID int(18) NOT NULL AUTO_INCREMENT,
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
     * Получаем sql запрос на создание таблицы инвойсов
     *
     * @return string
     */
    private static function add_db_invoice()
    {

        $table_name = self::db_table('safe_pay_invoice');
        if (self::db()->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

            $sql = "CREATE TABLE " . $table_name . " (
                ID int(18) NOT NULL AUTO_INCREMENT,
                STATUS varchar(10) NOT NULL,
                RECIPIENT varchar(10) NOT NULL,
                BANK_ID varchar(100) NOT NULL,
                EXPIRE int(10) NOT NULL,
                PAY_NUM int(11) NOT NULL,
                IS_TEST int(1) NOT NULL,
                DATE_CREATED int(10) NOT NULL,
                DATE_UPDATED int(10) NOT NULL,
                CREATOR text NOT NULL,
                SITE_URL varchar(255) NOT NULL,
                PRIMARY KEY (ID)
            );";

            return $sql;
        } else {

            return '';
        }
    }

    /**
     * Получаем sql запрос на создание таблицы реципиентов
     *
     * @return string
     */
    private static function add_db_recipient()
    {

        $table_name = self::db_table('safe_pay_recipient');
        if (self::db()->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

            $sql = "CREATE TABLE " . $table_name . " (
                ID int(10) NOT NULL AUTO_INCREMENT,
                NAME varchar(100) NOT NULL,
                ATTRIBUTE varchar(10) NOT NULL,
                BILD varchar(50) NOT NULL,
                PUBLIC_KEY varchar(50) NOT NULL,
                PAY_URL varchar(255) NOT NULL,
                APP_ANDROID varchar(255),
                APP_IOS varchar(255),
                PICTURE_URL varchar(255) NOT NULL,
                PRIMARY KEY (ID)
             );";
            $sql .= self::add_db_recipient_data($table_name);

            return $sql;
        } else {

            return '';
        }
    }

    /**
     * Получаем sql запрос на создание данных в таблице реципиентов
     *
     * @param string $table_name
     *
     * @return string
     */
    private static function add_db_recipient_data($table_name)
    {
        $sql   = '';
        $array = array(
            array(
                'NAME'        => 'ТрансСтройБанк',
                'ATTRIBUTE'   => 'tsbnk',
                'BILD'        => '7Dpv5Gi8HjCBgtDN1P1niuPJQCBQ5H8Zob',
                'PUBLIC_KEY'  => '2M9WSqXrpmRzaQkxjcWKnrABabbwqjPbJktBDbPswgL7',
                'PAY_URL'     => 'https://online.transstroybank.ru/',
                'APP_ANDROID' => 'https://play.google.com/store/apps/details?id=com.isimplelab.ibank.tsbank',
                'APP_IOS'     => 'https://apps.apple.com/ru/app/тсб-онлайн/id723491575?mt=8',
                'PICTURE_URL' => 'public/img/tsbnk.png',
            )
        );

        foreach ($array as $item) {
            $sql .= "INSERT INTO " . $table_name . " (NAME, ATTRIBUTE, BILD, PUBLIC_KEY, PAY_URL, APP_ANDROID, APP_IOS, PICTURE_URL) VALUES ('" . $item['NAME'] . "', '" . $item['ATTRIBUTE'] . "', '" . $item['BILD'] . "', '" . $item['PUBLIC_KEY'] . "', '" . $item['PAY_URL'] . "', '" . $item['APP_ANDROID'] . "', '" . $item['APP_IOS'] . "', '" . $item['PICTURE_URL'] . "');";
        }

        return $sql;

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
