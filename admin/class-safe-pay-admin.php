<?php
/** @noinspection PhpUndefinedVariableInspection */
defined('ABSPATH') || exit;

use SafePay\Blockchain\Options;
use SafePay\Blockchain\Base;

class Safe_Pay_Admin
{

    private $safe_pay;

    private $version;

    /**
     * Safe_Pay_Admin constructor.
     *
     * @param string $safe_pay
     * @param string $version
     */
    public function __construct($safe_pay, $version)
    {

        $this->safe_pay = $safe_pay;
        $this->version  = $version;

    }

    /**
     * Добавление стилей для админ-панели
     */
    public function enqueue_styles()
    {

        wp_enqueue_style($this->safe_pay, plugin_dir_url(__FILE__) . 'css/safe-pay-admin.css', array(),
            $this->version,
            'all');

    }

    /**
     * Добавление скриптов для админ-панели
     */
    public function enqueue_scripts()
    {

        wp_enqueue_script($this->safe_pay, plugin_dir_url(__FILE__) . 'js/safe-pay-admin.js', array('jquery'),
            $this->version, false);
        wp_localize_script($this->safe_pay, 'SPL_Admin', array(
            'new_key' => __('Вы действительно хотите сменить ключи? Если вы смените ключи и не сохраните их в надежном месте, доступ к счёту невозможно будет восстановить.',
                'safe-pay'),
            'delete'  => __('Удалить', 'safe-pay')
        ));
        wp_localize_script($this->safe_pay, 'ajax_object',
            array('ajax_url' => admin_url('admin-ajax.php'), 'ajax_nonce' => wp_create_nonce('safe_pay_admin')));
        wp_enqueue_script('Base58', plugin_dir_url(__FILE__) . 'js/Base58.js',
            $this->version, false);
        wp_enqueue_script('eralib', plugin_dir_url(__FILE__) . 'js/eralib.js',
            $this->version, false);
        wp_enqueue_script('ripemd160', plugin_dir_url(__FILE__) . 'js/ripemd160.js',
            $this->version, false);
        wp_enqueue_script('sha256', plugin_dir_url(__FILE__) . 'js/sha256.js',
            $this->version, false);

    }

    /**
     * Добавление меню для плагина
     */
    public function add_safepay_page()
    {
        add_menu_page(__('Безопасные платежи SAFE PAY', 'safe-pay'), __('Оплата SAFE PAY', 'safe-pay'),
            'manage_options',
            'safe-pay/admin/partials/safe-pay-admin-display.php', '', 'dashicons-shield', 59.5);
        add_submenu_page('safe-pay/admin/partials/safe-pay-admin-display.php',
            __('Общие настройки SAFE PAY', 'safe-pay'),
            __('Общие настройки', 'safe-pay'), 'manage_options', 'safe-pay/admin/partials/safe-pay-admin-general.php',
            '');
        add_submenu_page('safe-pay/admin/partials/safe-pay-admin-display.php', __('Транзакции SAFE PAY', 'safe-pay'),
            __('Транзакции', 'safe-pay'),
            'manage_options', 'safe-pay/admin/partials/safe-pay-admin-transactions.php', '');
        add_submenu_page('safe-pay/admin/partials/safe-pay-admin-display.php',
            __('Расширенные настройки SAFE PAY', 'safe-pay'),
            __('Расширенные настройки', 'safe-pay'),
            'manage_options', 'safe-pay/admin/partials/safe-pay-admin-extended.php', '');
        add_submenu_page('safe-pay/admin/partials/safe-pay-admin-display.php',
            __('Настройки платежей по QR-коду', 'safe-pay'),
            __('Настройки QR-кода', 'safe-pay'),
            'manage_options', 'safe-pay/admin/partials/safe-pay-admin-qr.php', '');
        add_submenu_page('safe-pay/admin/partials/safe-pay-admin-display.php',
            __('Список серверов SAFE PAY', 'safe-pay'),
            __('Список серверов', 'safe-pay'),
            'manage_options', 'safe-pay/admin/partials/safe-pay-admin-server.php', '');
        remove_submenu_page('safe-pay/admin/partials/safe-pay-admin-display.php',
            'safe-pay/admin/partials/safe-pay-admin-display.php');
    }

    /**
     * Добавление полей для страницы общих нстроек
     */
    public function safepay_option_settings()
    {
        register_setting('safepay_options', 'safepay_options', array($this, 'sanitize_callback'));

        add_settings_section('safepay_section_1', __('Ключи для платежного сервиса', 'safe-pay'), '',
            'safe-pay/admin/partials/safe-pay-admin-general.php');

        $true_field_params = array(
            'type'      => 'text',
            'id'        => 'SP_publickey',
            'desc'      => '',
            'label_for' => 'SP_publickey'
        );
        add_settings_field('SP_publickey', __('Публичный ключ *', 'safe-pay'),
            array($this, 'safepay_option_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-general.php', 'safepay_section_1', $true_field_params);

        $true_field_params = array(
            'type'      => 'text',
            'id'        => 'SP_privatekey',
            'desc'      => '',
            'label_for' => 'SP_privatekey'
        );
        add_settings_field('SP_privatekey', __('Приватный ключ *', 'safe-pay'),
            array($this, 'safepay_option_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-general.php', 'safepay_section_1', $true_field_params);

        $true_field_params = array(
            'type'      => 'text',
            'id'        => 'SP_bild',
            'desc'      => '',
            'label_for' => 'SP_bild'
        );
        add_settings_field('SP_bild', __('Ключ счёта *', 'safe-pay'),
            array($this, 'safepay_option_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-general.php', 'safepay_section_1', $true_field_params);

        $true_field_params = array(
            'type'      => 'text',
            'id'        => 'SP_last_block',
            'desc'      => '',
            'label_for' => 'SP_last_block',
            'class'     => 'SP_field_hidden'
        );
        add_settings_field('SP_last_block', __('Последний блок на боевом сервере', 'safe-pay'),
            array($this, 'safepay_option_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-general.php', 'safepay_section_1', $true_field_params);

        $true_field_params = array(
            'type'      => 'text',
            'id'        => 'SP_last_block_test',
            'desc'      => '',
            'label_for' => 'SP_last_block_test',
            'class'     => 'SP_field_hidden'
        );
        add_settings_field('SP_last_block_test', __('Последний блок на тестовом сервере', 'safe-pay'),
            array($this, 'safepay_option_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-general.php', 'safepay_section_1', $true_field_params);

        $true_field_params = array(
            'type'  => 'generate',
            'id'    => 'SP_generate',
            'desc'  => '',
            'value' => __('Сгенерировать ключи', 'safe-pay')
        );
        add_settings_field('SP_generate', '&nbsp;', array($this, 'safepay_option_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-general.php', 'safepay_section_1', $true_field_params);

        // Добавляем вторую секцию настроек

        add_settings_section('safepay_section_2', __('Дополнительные параметры', 'safe-pay'), '',
            'safe-pay/admin/partials/safe-pay-admin-general.php');

        // Создадим чекбокс
        $true_field_params = array(
            'type' => 'checkbox',
            'id'   => 'SP_testing',
            'desc' => __('Включить тестовый режим. После тестирования, необходимо убрать галочку.', 'safe-pay')
        );
        add_settings_field('SP_testing', __('Тестовый режим', 'safe-pay'),
            array($this, 'safepay_option_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-general.php', 'safepay_section_2', $true_field_params);

        // статус метода оплаты
        $true_field_params = array(
            'type' => 'status',
            'id'   => 'SP_status',
            'desc' => sprintf(__('Для того чтобы активировать метод оплаты SAFE PAY, необходимо создать ключи, а после этого включить метод оплаты в <a href="%s">WooCommerce->Настройки->Платежи</a>',
                'safe-pay'), '?page=wc-settings&tab=checkout')
        );
        add_settings_field('SP_status', __('Статус метода оплаты', 'safe-pay'),
            array($this, 'safepay_option_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-general.php', 'safepay_section_2', $true_field_params);

        // статус крона
        $true_field_params = array(
            'type' => 'status_cron',
            'id'   => 'SP_status_cron'
        );
        add_settings_field('SP_status_cron', __('Статус WP Cron', 'safe-pay'),
            array($this, 'safepay_option_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-general.php', 'safepay_section_2', $true_field_params);

        $true_field_params = array(
            'type'      => 'text',
            'id'        => 'SP_server_cron',
            'desc'      => __('Введите интервал, проверки серверов для задачи WP Cron (по умолчанию 5 минут).',
                'safe-pay'),
            'label_for' => 'SP_server_cron'
        );
        add_settings_field('SP_server_cron', __('Интервал проверки серверов (минуты)', 'safe-pay'),
            array($this, 'safepay_option_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-general.php', 'safepay_section_2', $true_field_params);

        $true_field_params = array(
            'type'      => 'text',
            'id'        => 'SP_pay_cron',
            'desc'      => __('Введите интервал, проверки поступления оплаты для задачи WP Cron (по умолчанию 5 минут).',
                'safe-pay'),
            'label_for' => 'SP_pay_cron'
        );
        add_settings_field('SP_pay_cron', __('Интервал проверки оплаты (минуты)', 'safe-pay'),
            array($this, 'safepay_option_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-general.php', 'safepay_section_2', $true_field_params);

        $true_field_params = array(
            'type'      => 'text',
            'id'        => 'SP_expire',
            'desc'      => __('Введите время, отведённое на оплату счёта. По истечению данного времени, транзакция переходит в статус "Истекла" (по умолчанию 12 часов, максимум 24 часа).',
                'safe-pay'),
            'label_for' => 'SP_expire'
        );
        add_settings_field('SP_expire', __('Время на оплату (часы)', 'safe-pay'),
            array($this, 'safepay_option_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-general.php', 'safepay_section_2', $true_field_params);
    }

    /**
     * Добавление полей для страницы общих нстроек
     */
    public function safepay_option_extended_settings()
    {
        register_setting('safepay_options_extended', 'safepay_options_extended', array($this, 'sanitize_callback'));

        add_settings_section('safepay_section_3', __('Создание кастомной страницы оплаты', 'safe-pay'), '',
            'safe-pay/admin/partials/safe-pay-admin-extended.php');

        $true_field_params = array(
            'type'      => 'text',
            'id'        => 'SP_link_pay',
            'desc'      => sprintf(__('Если ваша страница оплаты переделана, и по какой то причине не работает с модулем оплаты, создайте новую страницу и вставьте в неё шорткод %s, а в данное поле введите полную ссылку на страницу оплаты, например: %s',
                    'safe-pay'), '<mark>[safe_pay_page]</mark>',
                    '<mark>https://site.ru/pay/</mark>') . '<br><br>' . sprintf(__('Далее необходимо в файле %s заменить код (желательно перед этим перенести файл в свою тему - %s).',
                    'safe-pay'), '<mark>plugins/woocommerce/templates/email/customer-invoice.php</mark>',
                    '<mark>yourtheme/woocommerce/emails/customer-invoice.php</mark>') .
                           '<code class="safepay-settings__code">\'&lt;a href="\' . esc_url( <mark class="safepay-settings__code--false">$order->get_checkout_payment_url()</mark> ) . \'"&gt;\' . esc_html__( \'Pay for this order\', \'woocommerce\' ) . \'&lt;/a&gt;\'</code>' .
                           '<code class="safepay-settings__code">\'&lt;a href="\' . esc_url( <mark class="safepay-settings__code--true">shortcode_exists( \'safe_pay_page_url\' ) ? do_shortcode(\'[safe_pay_page_url order_id="\'.$order->get_id().\'"]\') : $order->get_checkout_order_received_url()</mark> ) . \'"&gt;\' . esc_html__( \'Pay for this order\', \'woocommerce\' ) . \'&lt;/a&gt;\'</code>',
            'label_for' => 'SP_link_pay',
            'value'     => ''
        );
        add_settings_field('SP_link_pay', __('Ссылка на страницу оплаты', 'safe-pay'),
            array($this, 'safepay_option_extended_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-extended.php', 'safepay_section_3', $true_field_params);

        add_settings_section('safepay_section_4', __('Шаблоны для транзакций', 'safe-pay'), '',
            'safe-pay/admin/partials/safe-pay-admin-extended.php');

        $true_field_params = array(
            'type'      => 'text',
            'id'        => 'SP_template_title',
            'desc'      => __('Если необходимо, отредактируйте шаблон заголовка.', 'safe-pay'),
            'label_for' => 'SP_template_title',
            'value'     => __('Оплата заказа из интернет-магазина %site_url%', 'safe-pay')
        );
        add_settings_field('SP_template_title', __('Шаблон заголовка транзакции', 'safe-pay'),
            array($this, 'safepay_option_extended_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-extended.php', 'safepay_section_4', $true_field_params);

        $true_field_params = array(
            'type'      => 'text',
            'id'        => 'SP_template_desc',
            'desc'      => __('Если необходимо, отредактируйте шаблон описания.', 'safe-pay'),
            'label_for' => 'SP_template_desc',
            'value'     => __('Оплата по счету №%order_id% от %order_date% на сумму %order_sum%.', 'safe-pay')
        );
        add_settings_field('SP_template_desc', __('Шаблон описания транзакции', 'safe-pay'),
            array($this, 'safepay_option_extended_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-extended.php', 'safepay_section_4', $true_field_params);

        $true_field_params = array(
            'type' => 'description',
            'desc' => '<p>' . sprintf(__('%s - ID заказа', 'safe-pay'), '<mark>%order_id%</mark>') . '</p>' .
                      '<p>' . sprintf(__('%s - Дата заказа', 'safe-pay'), '<mark>%order_date%</mark>') . '</p>' .
                      '<p>' . sprintf(__('%s - Сумма заказа', 'safe-pay'), '<mark>%order_sum%</mark>') . '</p>' .
                      '<p>' . sprintf(__('%s - Ссылка на магазин', 'safe-pay'), '<mark>%site_url%</mark>') . '</p>'
        );
        add_settings_field('SP_template_param', __('Доступные параметры', 'safe-pay'),
            array($this, 'safepay_option_extended_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-extended.php', 'safepay_section_4', $true_field_params);
    }

    /**
     * Добавление полей для страницы настроек qr-кода
     */
    public function safepay_option_qr_settings()
    {
        register_setting('safepay_options_qr', 'safepay_options_qr', array($this, 'sanitize_callback'));

        add_settings_section('safepay_section_5', __('Общие настройки QR', 'safe-pay'), '',
            'safe-pay/admin/partials/safe-pay-admin-qr.php');

        $true_field_params = array(
            'type'      => 'checkbox',
            'id'        => 'SP_qr_status',
            'label_for' => 'SP_qr_status',
            'desc'      => __('Включить / Выключить', 'safe-pay')
        );
        add_settings_field('SP_qr_status', __('Статус', 'safe-pay'),
            array($this, 'safepay_option_qr_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-qr.php', 'safepay_section_5', $true_field_params);

        add_settings_section('safepay_section_6',
            __('Реквизиты для QR-кода (только для счетов в АКБ «Трансстройбанк» (АО))', 'safe-pay'), '',
            'safe-pay/admin/partials/safe-pay-admin-qr.php');

        $true_field_params = array(
            'type'      => 'text',
            'id'        => 'SP_qr_name',
            'desc'      => __('Введите наименование ООО / ИП получателя.', 'safe-pay'),
            'label_for' => 'SP_qr_name'
        );
        add_settings_field('SP_qr_name', __('Наименование юр. лица *', 'safe-pay'),
            array($this, 'safepay_option_qr_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-qr.php', 'safepay_section_6', $true_field_params);

        $true_field_params = array(
            'type'      => 'text',
            'id'        => 'SP_qr_rs',
            'desc'      => __('Введите рассчетный счёт получателя в АКБ «Трансстройбанк» (АО).', 'safe-pay'),
            'label_for' => 'SP_qr_rs'
        );
        add_settings_field('SP_qr_rs', __('Рассчетный счёт *', 'safe-pay'),
            array($this, 'safepay_option_qr_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-qr.php', 'safepay_section_6', $true_field_params);

        $true_field_params = array(
            'type'      => 'text',
            'id'        => 'SP_qr_inn',
            'desc'      => __('Введите ИНН получателя.', 'safe-pay'),
            'label_for' => 'SP_qr_inn'
        );
        add_settings_field('SP_qr_inn', __('ИНН *', 'safe-pay'),
            array($this, 'safepay_option_qr_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-qr.php', 'safepay_section_6', $true_field_params);

        $true_field_params = array(
            'type'      => 'text',
            'id'        => 'SP_qr_bank',
            'desc'      => __('Введите наименование банка.', 'safe-pay'),
            'label_for' => 'SP_qr_bank'
        );
        add_settings_field('SP_qr_bank', __('Наименование банка *', 'safe-pay'),
            array($this, 'safepay_option_qr_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-qr.php', 'safepay_section_6', $true_field_params);

        $true_field_params = array(
            'type'      => 'text',
            'id'        => 'SP_qr_bik',
            'desc'      => __('Введите БИК банка.', 'safe-pay'),
            'label_for' => 'SP_qr_bik'
        );
        add_settings_field('SP_qr_bik', __('БИК банка *', 'safe-pay'),
            array($this, 'safepay_option_qr_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-qr.php', 'safepay_section_6', $true_field_params);

        $true_field_params = array(
            'type'      => 'text',
            'id'        => 'SP_qr_ksch',
            'desc'      => __('Введите корреспондентский счет банка.', 'safe-pay'),
            'label_for' => 'SP_qr_ksch'
        );
        add_settings_field('SP_qr_ksch', __('Корреспондентский счет банка *', 'safe-pay'),
            array($this, 'safepay_option_qr_display_settings'),
            'safe-pay/admin/partials/safe-pay-admin-qr.php', 'safepay_section_6', $true_field_params);
    }

    /**
     * Внешний вид типов полей для страницы общих настроек
     *
     * @param $args
     */
    public function safepay_option_display_settings($args)
    {
        extract($args);
        $option_name = 'safepay_options';
        $o           = get_option($option_name);

        switch ($type) {
            case 'text':
                @$o[$id] = esc_attr(stripslashes($o[$id]));
                echo "<input class='regular-text' type='text' id='$id' name='" . $option_name . "[$id]' value='$o[$id]' />";
                echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";
                break;
            case 'checkbox':
                $checked = (@$o[$id] == 'on') ? " checked='checked'" : '';
                echo "<label><input type='checkbox' id='$id' name='" . $option_name . "[$id]' $checked /> ";
                echo ($desc != '') ? $desc : "";
                echo "</label>";
                break;
            case 'generate':
                echo "<input class='button-primary' type='submit' id='$id' value='" . $value . "' />";
                echo "<input class='button-primary safepay-settings__download' type='submit' value='" . __('Скачать ключи',
                        'safe-pay') . "' />";
                echo "<span class='safepay-settings__loading'><img src='" . plugins_url('safe-pay/admin/img/') . "loading_mini.gif' alt='Loading'></span>";
                echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";
                echo "<div class='safepay-settings__message'>" . __('Вы сгенерировали/вставили ключи. Если вы вставили существующие ключи, убедитесь в их корректности. Сохраните ключи, нажав кнопку &laquo;Сохранить изменения&raquo;, после этого выгрузите файл с ключами в надежное место.',
                        'safe-pay') . "</div>";
                break;
            case 'status':
                if ($this->get_sp_payment_status() === 'yes') {
                    echo '<p class="safepay-settings__status safepay-settings__status--enabled">' . __('Активен',
                            'safe-pay') . '</p>';
                } else {
                    echo '<p class="safepay-settings__status safepay-settings__status--disabled">' . __('Неактивен',
                            'safe-pay') . '</p>';
                }
                echo ($desc != '') ? "<span class='description'>$desc</span>" : "";
                break;
            case 'status_cron':
                if ( ! defined('DISABLE_WP_CRON')) {
                    echo '<p class="safepay-settings__status safepay-settings__status--enabled">' . __('Активен',
                            'safe-pay') . '</p>';
                    printf(__('Для корректной работы платёжной системы требуется WP Cron. Он используется для обновления доступности серверов и проверки поступления оплат.',
                        'safe-pay'));
                } else {
                    echo '<p class="safepay-settings__status safepay-settings__status--disabled">' . __('Неактивен',
                            'safe-pay') . '</p>';
                    echo '<p>' . __('Для корректной работы платёжной системы требуется WP Cron. Он используется для обновления доступности серверов и проверки поступления оплат.',
                            'safe-pay') . '</p>';
                    echo '<p>' . __('Вам необходимо включить WP Cron, задав константе DISABLE_WP_CRON значение - false. Или запустить задачи через Cron сервера/хостинга по http/https запросу:',
                            'safe-pay') . '</p>';
                    echo '<p><mark>' . Options::getSiteUrl() . '/wp-json/safe-pay/v1/api/cron_server/</mark> ' . sprintf(__('интервал %s минут',
                            'safe-pay'), Options::getServerInterval()) . '</p>';
                    echo '<p><mark>' . Options::getSiteUrl() . '/wp-json/safe-pay/v1/api/cron_pay/</mark> ' . sprintf(__('интервал %s минут',
                            'safe-pay'), Options::getPayInterval()) . '</p>';
                }
                break;
        }
    }

    /**
     * Внешний вид типов полей для страницы расширенных настроек
     *
     * @param $args
     */
    public function safepay_option_extended_display_settings($args)
    {
        extract($args);
        $option_name = 'safepay_options_extended';
        $o           = get_option($option_name);

        switch ($type) {
            case 'text':
                if ( ! empty($o[$id])) {
                    $o[$id] = esc_attr(stripslashes($o[$id]));
                    $value  = $o[$id];
                }
                echo "<input class='regular-text' type='text' id='$id' name='" . $option_name . "[$id]' value='$value' />";
                echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";
                break;
            case 'description':
                echo ($desc != '') ? "<div class='description'>$desc</div>" : "";
                break;
        }
    }

    /**
     * Внешний вид типов полей для страницы расширенных настроек
     *
     * @param $args
     */
    public function safepay_option_qr_display_settings($args)
    {
        extract($args);
        $option_name = 'safepay_options_qr';
        $o           = get_option($option_name);

        switch ($type) {
            case 'text':
                if ( ! empty($o[$id])) {
                    $o[$id] = esc_attr(stripslashes($o[$id]));
                    $value  = $o[$id];
                }
                echo "<input class='regular-text' type='text' id='$id' name='" . $option_name . "[$id]' value='$value' />";
                echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";
                break;
            case 'checkbox':
                $checked = (@$o[$id] == 'on') ? " checked='checked'" : '';
                echo "<label><input type='checkbox' id='$id' name='" . $option_name . "[$id]' $checked /> ";
                echo ($desc != '') ? $desc : "";
                echo "</label>";
                break;
        }
    }

    /**
     * Валидация полей настроек
     *
     * @param array $input
     *
     * @return array
     */
    public function sanitize_callback($input)
    {
        $array_valid = array(
            'SP_server_cron',
            'SP_pay_cron',
            'SP_expire'
        );
        foreach ($input as $k => $v) {
            $valid_input[$k] = trim($v);
            if (in_array($k, $array_valid)) {
                if ( ! filter_var($valid_input[$k], FILTER_VALIDATE_INT)) {
                    if ($k == 'SP_expire') {
                        $valid_input[$k] = 12;
                    } else {
                        $valid_input[$k] = 5;
                    }
                } else {
                    if ($k == 'SP_expire') {
                        if ((int)$valid_input[$k] <= 24) {
                            $valid_input[$k] = (int)$valid_input[$k];
                        } else {
                            $valid_input[$k] = 24;
                        }
                    } else {
                        $valid_input[$k] = (int)$valid_input[$k];
                    }
                }
            } elseif ($k == 'SP_last_block' || $k == 'SP_last_block_test') {
                if (empty($v)) {
                    $base                              = new Base();
                    $key                               = $base->getLastBlockData();
                    $valid_input['SP_last_block']      = (int)$key['lastBlock'];
                    $valid_input['SP_last_block_test'] = (int)$key['lastBlockTest'];
                }
            } elseif ($k == 'SP_qr_bik') {
                if ($v != '044525326' && stripos($v, '|test') === false) {
                    $valid_input['SP_qr_bik'] = '';
                }
            }
        }

        return $valid_input;
    }

    /**
     * Получаем статус метода оплаты SAFE PAY
     *
     * @return string|bool
     */
    private function get_sp_payment_status()
    {
        $option = get_option('woocommerce_' . $this->safe_pay . '_settings');

        if (empty($option)) {
            return false;
        }

        return $option['enabled'];

    }

}
