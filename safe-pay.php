<?php
defined('ABSPATH') || exit;

/**
 * @wordpress-plugin
 * Plugin Name:       Безопасный платеж SAFE PAY
 * Plugin URI:        https://safe-pay.ru/?page_id=5013
 * Description:       Сервис SAFE PAY разработан для приема платежей по технологии e-invoicing. Платформа позволяет мгновенно выставлять электронные счета по номеру телефона в интернет-банк покупателя.
 * Version:           1.0.9
 * Author:            SAFE PAY
 * Author URI:        https://safe-pay.ru/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       safe-pay
 * Domain Path:       /languages
 */

/**
 * Текущая версия плагина.
 */
define('SAFE_PAY_VERSION', '1.0.9');
define('SAFE_PAY_DB_VERSION', '1.1');
define('SAFE_PAY_URL', plugin_dir_url(__FILE__));
define('SAFE_PAY_PATH', plugin_dir_path(__FILE__));

/**
 * Код, который запускается при активации плагина.
 * Это действие задокументировано в includes/class-safe-pay-activator.php
 */
function activate_safe_pay()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-safe-pay-activator.php';
    Safe_Pay_Activator::activate();
}

/**
 * Код, который запускается при удалении плагина.
 * Это действие задокументировано в includes/class-safe-pay-deactivator.php
 */
function deactivate_safe_pay()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-safe-pay-deactivator.php';
    Safe_Pay_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_safe_pay');
register_deactivation_hook(__FILE__, 'deactivate_safe_pay');

if ( ! get_option('safepay_db_version')) {
    add_option('safepay_db_version', '1.0');
}

/**
 * Обновление базы данных
 */
function update_db()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-safe-pay-update.php';
    Safe_Pay_Update::update();
}

/**
 * Основной класс плагина, который используется для определения интернационализации,
 * специфичных для администратора перехватчиков и общедоступных перехватчиков сайтов.
 */
require plugin_dir_path(__FILE__) . 'includes/class-safe-pay.php';

/**
 * Начинается выполнение плагина.
 *
 * Поскольку все в плагине регистрируется с помощью хуков, то запуск этого плагина
 * с этого момента в файле не влияет на жизненный цикл страницы.
 */
function run_safe_pay()
{

    if (class_exists('WC_Payment_Gateway') && PHP_INT_SIZE !== 4) {

        if (get_option('safepay_db_version') != SAFE_PAY_DB_VERSION) {
            update_db();
        }

        $plugin = new Safe_Pay();
        $plugin->run();
    } elseif ( ! class_exists('WC_Payment_Gateway') && PHP_INT_SIZE === 4) {
        add_action('admin_notices', 'notice_error_php');
        add_action('admin_notices', 'notice_error_wc');
    } elseif ( ! class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'notice_error_wc');
    } elseif (PHP_INT_SIZE === 4) {
        add_action('admin_notices', 'notice_error_php');
    }

}

/**
 * Вывод уведомления о 32bit версии PHP
 */
function notice_error_php()
{
    $html = '';
    $html .= '<div class="notice notice-error is-dismissible">';
    $html .= '<p>[Безопасный платеж SAFE PAY] К сожалению, Вы используете 32bit версию PHP, для корректной работы платежной системы SAFE PAY необходима 64bit версия.</p>';
    $html .= '</div>';

    echo $html;
}

/**
 * Вывод уведомления об отсутствии WooCommerce
 */
function notice_error_wc()
{
    $html = '';
    $html .= '<div class="notice notice-error is-dismissible">';
    $html .= '<p>[Безопасный платеж SAFE PAY] Плагин работает только в связке WordPress/WooCommerce. Установите и настройке WooCommerce для подключения платежной системы SAFE PAY</p>';
    $html .= '</div>';

    echo $html;
}

//запускаем плагин
if ( ! has_action('plugins_loaded', 'run_safe_pay')) {
    add_action('plugins_loaded', 'run_safe_pay', 11);
}