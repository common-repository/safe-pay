<?php
defined('ABSPATH') || exit;

use Alcohol\ISO4217;
use SafePay\Blockchain\DBRecipient;
use SafePay\Blockchain\Options;

class WC_Gateway_Safe_Pay extends WC_Payment_Gateway
{

    private static $instance = null;

    public function __construct()
    {
        $this->id                 = 'safe-pay';
        $this->icon               = apply_filters('woocommerce_safe-pay_icon',
            SAFE_PAY_URL . 'public/img/safe-pay.png');
        $this->has_fields         = false;
        $this->method_title       = __('Безопасные платежи SAFE PAY', 'safe-pay');
        $this->method_description = __('SAFE PAY позволяет мгновенно выставлять электронные счета по номеру телефона в интернет-банк покупателя.',
            'safe-pay');

        // Загрузка настроек
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title       = $this->get_option('title');
        $this->description = $this->get_option('description');

        if ( ! has_action('woocommerce_update_options_payment_gateways_' . $this->id)) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id,
                array($this, 'process_admin_options'));
        }
        if ( ! has_action('woocommerce_thankyou_' . $this->id)) {
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        }
    }

    /**
     * Проверяет существование объекта, если существует то направляет на него, если нет, то создаёт новый
     *
     * @return WC_Gateway_Safe_Pay
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Настройка полей настроек для способа оплаты SAFE PAY
     */
    public function init_form_fields()
    {
        $this->form_fields = apply_filters('wc_offline_form_fields', array(

            'enabled' => array(
                'title'   => __('Включить/Выключить', 'safe-pay'),
                'type'    => 'checkbox',
                'label'   => __('Включить безопасные платежи SAFE PAY', 'safe-pay'),
                'default' => 'no'
            ),

            'title' => array(
                'title'       => __('Наименование', 'safe-pay'),
                'type'        => 'text',
                'description' => __('Название для способа оплаты, который клиент видит во время оформления заказа.',
                    'safe-pay'),
                'default'     => 'SAFE PAY',
                'desc_tip'    => true
            ),

            'description' => array(
                'title'       => __('Описание', 'safe-pay'),
                'type'        => 'textarea',
                'description' => __('Описание способа оплаты, которое клиент увидит при оформлении заказа.',
                    'safe-pay'),
                'default'     => sprintf(__('SAFE PAY - выставление электронного счёта в личный кабинет интернет-банка. Обращаем внимание, вы сможете воспользоваться этим способом оплаты, если является клиентом одного из перечисленных банков на сайте %s. Нажимая кнопку «Оформить заказ» я подтверждаю, что ознакомлен и принимаю правила использования сервиса SAFE PAY опубликованные на сайте интернет магазина.',
                    'safe-pay'), '<a href="https://safe-pay.ru" target="_blank">safe-pay.ru</a>'),
                'desc_tip'    => true
            )
        ));
    }

    /**
     * Вывод оплаты на странице "Спасибо"
     *
     * @param int $order_id
     * @param object|null $order
     */
    public function thankyou_page($order_id, $order = null)
    {
        if ( ! empty($_GET['key'])) {
            if ($order === null) {
                $order = new WC_Order($order_id);
            }

            $param = array(
                'order_id'   => $order_id,
                'order_date' => $order->get_date_created()->date('d.m.Y H:i'),
                'order_sum'  => $this->getAmount($order->get_total()) . ' ' . get_woocommerce_currency(),
                'site_url'   => $_SERVER['SERVER_NAME'],
            );

            $args = array(
                'order_date'    => $order->get_date_created()->getTimestamp(),
                'order_num'     => $order_id,
                'userPhone'     => $this->getPhone($order->get_data()),
                'curr'          => $this->getCurrency(get_woocommerce_currency()),
                'sum'           => $this->getAmount($order->get_total()),
                'expire'        => time() + Options::getExpire() * 3600,
                'title'         => Options::getTemplateTitle($param),
                'description'   => Options::getTemplateDescription($param) . $this->getTax($order),
                'ALL_RECIPIENT' => json_encode(DBRecipient::getListAll()),
                'plugin_dir'    => plugins_url('safe-pay/'),
            );

            $args_array = array();

            foreach ($args as $key => $value) {
                $status_input = '';
                if (in_array($key, array('ALL_RECIPIENT', 'img_dir', 'plugin_dir'))) {
                    $status_input = ' disabled';
                }
                $args_array[] = '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '"' . $status_input . ' />';
            }
            include SAFE_PAY_PATH . 'public/partials/safe-pay-public-display.php';
        }
    }

    /**
     * Функция для отображения оплаты через шорткод
     */
    public function custom_pay_page()
    {
        if ( ! empty($_GET['order_id']) && ! empty($_GET['key'])) {

            $order_id = sanitize_text_field($_GET['order_id']);
            if ( ! filter_var($order_id, FILTER_VALIDATE_INT)) {
                $error['data'][] = __('Номер заказа не верного формата', 'safe-pay');
            }

            try {
                $order      = new WC_Order($order_id);
                $order_data = $order->get_data();

                if ($order->get_order_key() === $_GET['key']) {
                    echo '<p>' . sprintf(__('Оплата по заказу #%s на сумму %s %s', 'safe-pay'), $order_id,
                            $order_data['total'],
                            $order_data['currency']) . '</p>';
                    $this->thankyou_page($order_id, $order);
                } else {
                    echo __('Доступ запрещен.', 'safe-pay');
                }
            } catch (Exception $e) {
                echo __('Доступ запрещен.', 'safe-pay');
            }
        }
    }

    /**
     * Процесс оплаты и редирект на страницу "спасибо"
     *
     * @param int $order_id
     *
     * @return array
     */
    public function process_payment($order_id)
    {
        WC()->cart->empty_cart();

        return array(
            'result'   => 'success',
            'redirect' => $this->safe_pay_payment_url(array('order_id' => $order_id))
        );
    }

    /**
     * Формирование ссылки для редиректа на страницу оплаты
     *
     * @param array $order_arr
     *
     * @return string
     */
    public function safe_pay_payment_url($order_arr)
    {
        $order = wc_get_order($order_arr['order_id']);
        if (Options::getUrlPayPage()) {
            return Options::getUrlPayPage() . "&key=" . $order->get_order_key() . "&order_id=" . $order_arr['order_id'];
        } else {
            return $this->get_return_url($order) . "&order_id=" . $order_arr['order_id'];
        }
    }

    /**
     * Конвертация валюты в ISO 4217 number-3
     *
     * @param string $currency валюта в ISO 4217 alfa-3
     *
     * @return int
     */
    private function getCurrency($currency)
    {
        $iso4217    = new ISO4217();
        $number_cur = $iso4217->getByAlpha3($currency);

        return (int)$number_cur['numeric'];
    }

    /**
     * Получение суммы в корректном виде
     *
     * @param string $amount
     *
     * @return string
     */
    private function getAmount($amount)
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Получение телефона в корректном виде
     *
     * @param array $order массив с данными о заказе
     *
     * @return string
     */
    private function getPhone($order)
    {
        $phone = $order['billing']['phone'];

        $phone = preg_replace('~\D+~', '', $phone);

        return substr($phone, -10);
    }

    /**
     * Проверка ставки НДС
     *
     * @param object $order
     *
     * @return string
     */
    private function getTax($order)
    {
        $tax = 0;
        foreach ($order->get_items('tax') as $item_id => $item_tax) {
            $tax_data = $item_tax->get_data();
            if (stripos($tax_data['label'], 'НДС') !== false) {
                $tax = $tax_data['rate_percent'];
            }
        }
        if ($tax === 0) {
            return ' (Без НДС)';
        } else {
            return ' (Включая НДС ' . $tax . '%)';
        }
    }

}