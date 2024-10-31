<?php

defined('ABSPATH') || exit;

use \SafePay\Blockchain\Options;

class Safe_Pay
{

    protected $loader;

    protected $safe_pay;

    protected $version;

    /**
     * Safe_Pay constructor.
     */
    public function __construct()
    {
        if (defined('SAFE_PAY_VERSION')) {
            $this->version = SAFE_PAY_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->safe_pay = 'safe-pay';

        $this->load_dependencies();
        $this->set_locale();
        $this->add_method();
        $this->define_cron_hooks();
        $this->define_api_hooks();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_status_hooks();
        $this->custom_pay_page();

    }

    /**
     * Загрузка файлов плагина
     */
    private function load_dependencies()
    {
        /**
         * Автозагрузчик классов, для работы библиотеки SAFE PAY.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/api/autoload.php';
        /**
         * Класс, отвечающий за управление действиями и фильтрами основного плагина.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-safe-pay-loader.php';

        /**
         * Класс, отвечающий за определение функциональности интернационализации плагина.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-safe-pay-i18n.php';

        /**
         * Класс, отвечающий за добавление метода оплаты Safe-Pay.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-safe-pay-method.php';

        /**
         * Класс, отвечающий за смену статусов транзакций, при смене статуса заказа.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-safe-pay-status.php';

        /**
         * Класс, отвечающий за добавление кастомной страницы для оплаты.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-safe-pay-custom-pay-page.php';

        /**
         * Класс, отвечающий за запуск задач в WP Cron.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-safe-pay-cron.php';

        /**
         * Класс, отвечающий за работу с REST API SAFE PAY.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-safe-pay-api.php';

        /**
         * Класс, отвечающий за определение всех действий, которые происходят в области администратора.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-safe-pay-admin.php';

        /**
         * Класс, отвечающий за определение всех действий, которые происходят на общедоступной стороне сайта.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-safe-pay-public.php';

        $this->loader = new Safe_Pay_Loader();
    }

    /**
     * Загружаем класс отвечающий за локализацию
     */
    private function set_locale()
    {

        $plugin_i18n = new Safe_Pay_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

    }

    /**
     * Добавляет метод оплаты Safe-Pay и возвращает массив с методами оплаты.
     *
     * @param array $methods
     *
     * @return array
     */
    public function add_safe_pay_method($methods)
    {

        $methods[] = 'WC_Gateway_Safe_Pay';

        return $methods;

    }

    /**
     * Добавляем метод оплаты
     */
    private function add_method()
    {

        if ($this->isValidBild()) {
            $this->loader->add_filter('woocommerce_payment_gateways', $this, 'add_safe_pay_method');
        }

    }

    /**
     * Проверяем добавлен счёт или нет
     * @return bool
     */
    private function isValidBild()
    {
        $valid    = array(
            'SP_publickey',
            'SP_privatekey',
            'SP_bild'
        );
        $settings = get_option('safepay_options');

        foreach ($valid as $item) {
            if (empty($settings[$item])) {
                return false;
            }
        }

        return true;
    }

    private function define_cron_hooks()
    {
        $plugin_cron = new Safe_Pay_Cron();

        $this->loader->add_filter('cron_schedules', $plugin_cron, 'add_pay_interval');
        $this->loader->add_filter('cron_schedules', $plugin_cron, 'add_server_interval');
        $this->loader->add_action('safe_pay_cron_pay', $plugin_cron, 'add_safe_pay_cron_pay');
        $this->loader->add_action('safe_pay_cron_server', $plugin_cron, 'add_safe_pay_cron_server');
    }

    private function define_api_hooks()
    {
        $plugin_api = new Safe_Pay_Api($this->get_safe_pay(), $this->get_version());

        if (current_user_can('manage_options')) {
            $this->loader->add_action('wp_ajax_generate_account', $plugin_api, 'generate_account');
            $this->loader->add_action('wp_ajax_server_add', $plugin_api, 'server_add');
            $this->loader->add_action('wp_ajax_server_del', $plugin_api, 'server_del');
        }
        $this->loader->add_action('rest_api_init', $plugin_api, 'rest_safe_pay');
        $this->loader->add_action('wp_ajax_send_invoice', $plugin_api, 'send_invoice');
        $this->loader->add_action('wp_ajax_nopriv_send_invoice', $plugin_api, 'send_invoice');
        $this->loader->add_action('wp_ajax_check_pay', $plugin_api, 'check_pay');
        $this->loader->add_action('wp_ajax_nopriv_check_pay', $plugin_api, 'check_pay');
        $this->loader->add_action('wp_ajax_qr_pay', $plugin_api, 'qr_pay');
        $this->loader->add_action('wp_ajax_nopriv_qr_pay', $plugin_api, 'qr_pay');
    }

    /**
     * Регистрация всех хуков, связанных с администраторской функциональностью плагина
     */
    private function define_admin_hooks()
    {

        $plugin_admin = new Safe_Pay_Admin($this->get_safe_pay(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_safepay_page');
        $this->loader->add_action('admin_init', $plugin_admin, 'safepay_option_settings');
        $this->loader->add_action('admin_init', $plugin_admin, 'safepay_option_extended_settings');
        $this->loader->add_action('admin_init', $plugin_admin, 'safepay_option_qr_settings');

    }

    /**
     * Регистрация всех хуков, связанных с общедоступной функциональностью плагина.
     */
    private function define_public_hooks()
    {

        $plugin_public = new Safe_Pay_Public($this->get_safe_pay(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

    }

    /**
     * Регистрация всех хуков, связанных с сменой статусов заказа.
     */
    private function define_status_hooks()
    {

        $plugin_status = new Safe_Pay_Status();

        $this->loader->add_action('woocommerce_order_status_cancelled', $plugin_status, 'order_status_cancelled');
        $this->loader->add_action('woocommerce_order_status_pending', $plugin_status, 'order_status_pending');

    }

    /**
     * Класс для добавления кастомной страницы оплаты, создания шорткодов
     */
    private function custom_pay_page()
    {
        $custom_page = new Safe_Pay_Custom_Pay_Page();
        $custom_page->add_custom_pay_page();
        $this->loader->add_action('woocommerce_checkout_order_processed', $custom_page, 'send_email_invoice');
        if (Options::getUrlPayPage()) {
            $this->loader->add_action('woocommerce_thankyou', $custom_page, 'redirect_thanks_you_page');
        }
    }

    /**
     * Запускает загрузчик для выполнения всех хуков с WordPress.
     */
    public function run()
    {
        $this->loader->run();

        if ( ! wp_next_scheduled('safe_pay_cron_pay')) {
            wp_schedule_event(time(), 'pay_interval', 'safe_pay_cron_pay');
        }
        if ( ! wp_next_scheduled('safe_pay_cron_server')) {
            wp_schedule_event(time(), 'server_interval', 'safe_pay_cron_server');
        }
    }

    /**
     * Получаем имя плагина
     *
     * @return string
     */
    public function get_safe_pay()
    {
        return $this->safe_pay;
    }

    /**
     * Ссылка на класс, который управляет хуками с плагином.
     *
     * @return Safe_Pay_Loader
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Получение версии плагина
     * @return string
     */
    public function get_version()
    {
        return $this->version;
    }

}
