<?php

use SafePay\Blockchain\Options;

defined('ABSPATH') || exit;

class Safe_Pay_Custom_Pay_Page
{

    /**
     * Добавление шорткодов для страницы оплаты и ссылки на страницу оплаты
     */
    public function add_custom_pay_page()
    {
        if (Options::getUrlPayPage()) {
            $wc_gw_safe_pay = WC_Gateway_Safe_Pay::getInstance();
            add_shortcode('safe_pay_page', array($wc_gw_safe_pay, 'custom_pay_page'));
            add_shortcode('safe_pay_page_url', array($wc_gw_safe_pay, 'safe_pay_payment_url'));
        }
    }

    /**
     * Отправка письма с ссылкой для оплаты
     *
     * @param int $order_id
     */
    public function send_email_invoice($order_id)
    {
        $order = wc_get_order($order_id);

        if (!$order->has_status('pending')) {
            return;
        }

        $wc_email = WC()->mailer()->get_emails()['WC_Email_Customer_Invoice'];

        $wc_email->trigger($order_id);
    }

    /**
     * Переадресация на кастомную страницу оплаты
     *
     * @param int $order_id
     */
    public function redirect_thanks_you_page($order_id)
    {
        $order = new WC_Order($order_id);
        $url = Options::getUrlPayPage() . "&key=" . $order->get_order_key() . "&order_id=" . $order_id;

        if ($order->get_status() != 'failed') {
            echo "<script type=\"text/javascript\">window.location = '" . $url . "'</script>";
        }
    }

}