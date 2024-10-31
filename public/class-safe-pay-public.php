<?php
defined('ABSPATH') || exit;

use SafePay\Blockchain\Options;

class Safe_Pay_Public
{

    private $safe_pay;

    private $version;

    /**
     * Safe_Pay_Public constructor.
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
     * Добавление стилей для сайта
     */
    public function enqueue_styles()
    {

        wp_enqueue_style($this->safe_pay, plugin_dir_url(__FILE__) . 'css/safe-pay-public.css', array(),
            $this->version,
            'all');

    }

    /**
     * Добавление скриптов для сайта
     */
    public function enqueue_scripts()
    {
        if ( ! empty(Options::getQRStatus()) && ! empty(Options::getQRName()) && ! empty(Options::getQRRS()) && ! empty(Options::getQRBank()) && ! empty(Options::getQRBIK()) && ! empty(Options::getQRKsch())) {
            $qr = true;
        } else {
            $qr = false;
        }

        wp_enqueue_script($this->safe_pay, plugin_dir_url(__FILE__) . 'js/safe-pay-public.js', array('jquery'),
            $this->version, false);
        wp_localize_script($this->safe_pay, 'SPL_Public', array(
            'link_bank'            => __('Перейти в банк для оплаты', 'safe-pay'),
            'instruction'          => sprintf(__('SAFE PAY - выставление электронного счёта в личный кабинет интернет-банка. Обращаем внимание, вы сможете воспользоваться этим способом оплаты, если является клиентом одного из перечисленных банков на сайте %s. Нажимая кнопку «Оформить заказ» я подтверждаю, что ознакомлен и принимаю правила использования сервиса SAFE PAY опубликованные на сайте интернет магазина.',
                'safe-pay'), '<a href="https://safe-pay.ru" target="_blank">safe-pay.ru</a>'),
            'change_bank'          => __('Выберите свой банк:', 'safe-pay'),
            'check_pay'            => __('Проверить оплату', 'safe-pay'),
            'wait_pay'             => __('Ожидание оплаты SAFE PAY', 'safe-pay'),
            'check_pay_message'    => __('Если вы уже произвели оплату на сайте банка, нажмите на кнопку &laquo;Проверка оплаты&raquo;. Если оплата ещё не произведена, перейдите в личный кабинет выбранного банка для оплаты счёта.',
                'safe-pay'),
            'check_pay_false'      => sprintf(__('К сожалению, оплата ещё не поступила, повторите чуть позже. Обычно статус обновляется в течении %s минут после оплаты.',
                'safe-pay'), Options::getPayInterval()),
            'check_pay_true'       => __('Спасибо, оплата по заказу поступила! Ваш заказ передан в обработку.',
                'safe-pay'),
            'vote'                 => sprintf(__('%sНе нашли свой банк?%s', 'safe-pay'),
                '<a href="https://safe-pay.ru/?page_id=4720" target="_blank">',
                '</a>'),
            'send_order'           => __('Выполняется отправка счёта, ожидайте...', 'safe-pay'),
            'send_error'           => __('Возникла ошибка, повторите отправку', 'safe-pay'),
            'qr_pay'               => __('Оплата по QR-коду', 'safe-pay'),
            'qr_status'            => $qr,
            'qr_button'            => __('Оплатить по QR-коду', 'safe-pay'),
            'qr_pay_message_true'  => __('Для оплаты счёта, отсканируйте QR-код и оплатите. После оплаты свяжитесь с магазином, для проверки поступления средств.',
                'safe-pay'),
            'qr_pay_message_false' => __('Если у вас возникли сложности с оплатой по QR коду, или же вы решили оплатить стандартными средствами оплаты SAFE PAY, нажмите на кнопку ниже, Вас переместит на страницу выбора банка.',
                'safe-pay'),
            'qr_button_back'       => __('Оплатить по номеру телефона', 'safe-pay'),
            'phone_desc'           => __('Вы не указали в заказе номер телефона, или он указан не корректно. Укажите телефон, привязанный к выбранному банку.',
                'safe-pay'),
            'phone_placeholder'    => __('Номер телефона', 'safe-pay')
        ));
        wp_localize_script($this->safe_pay, 'ajax_object',
            array('ajax_url' => admin_url('admin-ajax.php'), 'ajax_nonce' => wp_create_nonce('safe_pay_public')));

    }

}
