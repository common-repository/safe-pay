<?php

namespace SafePay\Blockchain;

use WC_Order;

defined('ABSPATH') || exit;

class Options implements IOptions
{
    /**
     * Получение публичного ключа
     *
     * @return string
     */
    public static function getPublicKey()
    {
        return self::getAttribute('SP_publickey');
    }

    /**
     * Получение приватного ключа
     *
     * @return string
     */
    public static function getPrivateKey()
    {
        return self::getAttribute('SP_privatekey');
    }

    /**
     * Получение счёта
     *
     * @return string
     */
    public static function getAddress()
    {
        return self::getAttribute('SP_bild');
    }

    /**
     * Получение срока жизни счета
     *
     * @return int
     */
    public static function getExpire()
    {
        if ( ! empty(self::getAttribute('SP_expire'))) {
            return self::getAttribute('SP_expire');
        } else {
            return 12;
        }
    }

    /**
     * Получение режима работы платежной системы
     *
     * @return int
     */
    public static function isTest()
    {
        if (self::getAttribute('SP_testing') == 'on') {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Получение callback ссылки для банка, об успешной транзакции
     *
     * @return string
     */
    public static function getCallback()
    {
        return self::getSiteUrl() . '/wp-json/safe-pay/v1/api/callback/';
    }

    /**
     * Обновляет в настройках плагина последний проверенный блок
     *
     * @param int $block
     *
     * @return bool
     */
    public static function setLastBlock($block)
    {
        $settings = self::getSettings();

        $settings[self::getTypeLastBlock()] = (int)$block;

        return update_option('safepay_options', $settings);

    }

    /**
     * Получает последний блок из настроек плагина
     *
     * @return int
     */
    public static function getLastBlock()
    {
        if ( ! empty(self::getAttribute(self::getTypeLastBlock()))) {
            return self::getAttribute(self::getTypeLastBlock());
        } else {
            return 0;
        }
    }

    /**
     * Получает интервал для проверки оплаты через Cron
     *
     * @return int
     */
    public static function getPayInterval()
    {
        if ( ! empty(self::getAttribute('SP_pay_cron'))) {
            return self::getAttribute('SP_pay_cron');
        } else {
            return 5;
        }
    }

    /**
     * Получает интервал для проверки серверов через Cron
     *
     * @return int
     */
    public static function getServerInterval()
    {
        if ( ! empty(self::getAttribute('SP_server_cron'))) {
            return self::getAttribute('SP_server_cron');
        } else {
            return 5;
        }
    }

    /**
     * Обновляем статус в магазине на "Оплачено"
     *
     * @param int $order_id
     *
     * @return bool
     */
    public static function completedPay($order_id)
    {
        $order = new WC_Order($order_id);
        $order->add_order_note(__('Платеж успешно оплачен', 'safe-pay'));
        $order->update_status('processing');
        if ($order->payment_complete()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Получает шаблон для заголовка транзакции
     *
     * @param array $param
     *
     * @return string
     */
    public static function getTemplateTitle($param)
    {
        if ( ! empty(self::getAttribute('SP_template_title'))) {
            $text = self::getAttribute('SP_template_title');
        } else {
            $text = __('Оплата заказа из интернет-магазина %site_url%', 'safe-pay');
        }

        return self::getCorrectTemplate($text, $param);
    }

    /**
     * Получает шаблон для описания транзакции
     *
     * @param array $param
     *
     * @return string
     */
    public static function getTemplateDescription($param)
    {
        if ( ! empty(self::getAttribute('SP_template_desc'))) {
            $text = self::getAttribute('SP_template_desc');
        } else {
            $text = __('Оплата по счету №%order_id% от %order_date% на сумму %order_sum%.', 'safe-pay');
        }

        return self::getCorrectTemplate($text, $param);
    }

    /**
     * Получает ссылку на кастомную страницу оплаты
     *
     * @return string|bool
     */
    public static function getUrlPayPage()
    {
        if ( ! empty(self::getAttribute('SP_link_pay'))) {
            $link = explode('?', self::getAttribute('SP_link_pay'));
            if (count($link) > 1) {
                $link = $link[0] . '?' . $link[1] . '&custom_pay=true';
            } else {
                $link = $link[0] . '?custom_pay=true';
            }

            return $link;
        } else {
            return false;
        }
    }

    /**
     * Получение статуса работы QR-кода
     *
     * @return bool
     */
    public static function getQRStatus()
    {
        if ( ! empty(self::getAttribute('SP_qr_status'))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Получение названия получателя платежа
     *
     * @return bool|string
     */
    public static function getQRName()
    {
        if ( ! empty(self::getAttribute('SP_qr_name'))) {
            return self::getAttribute('SP_qr_name');
        } else {
            return false;
        }
    }

    /**
     * Получение расчетного счёта получателя платежа
     *
     * @return bool|int
     */
    public static function getQRRS()
    {
        if ( ! empty(self::getAttribute('SP_qr_rs'))) {
            return self::getAttribute('SP_qr_rs');
        } else {
            return false;
        }
    }

    /**
     * Получение ИНН получателя платежа
     *
     * @return bool|int
     */
    public static function getQRINN()
    {
        if ( ! empty(self::getAttribute('SP_qr_inn'))) {
            return self::getAttribute('SP_qr_inn');
        } else {
            return false;
        }
    }

    /**
     * Получение наименования банка
     *
     * @return bool|string
     */
    public static function getQRBank()
    {
        if ( ! empty(self::getAttribute('SP_qr_bank'))) {
            return self::getAttribute('SP_qr_bank');
        } else {
            return false;
        }
    }

    /**
     * Получение БИК банка
     *
     * @return bool|string
     */
    public static function getQRBIK()
    {
        if ( ! empty(self::getAttribute('SP_qr_bik'))) {
            return self::getAttribute('SP_qr_bik');
        } else {
            return false;
        }
    }

    /**
     * Получение к/с банка
     *
     * @return bool|string
     */
    public static function getQRKsch()
    {
        if ( ! empty(self::getAttribute('SP_qr_ksch'))) {
            return self::getAttribute('SP_qr_ksch');
        } else {
            return false;
        }
    }

    /**
     * Подстановка параметров в текст
     *
     * @param string $text
     * @param array $param
     *
     * @return string
     */
    private static function getCorrectTemplate($text, $param)
    {
        $text = str_replace('%order_id%', $param['order_id'], $text);
        $text = str_replace('%order_date%', $param['order_date'], $text);
        $text = str_replace('%order_sum%', $param['order_sum'], $text);
        $text = str_replace('%site_url%', $param['site_url'], $text);

        return $text;
    }

    /**
     * Получает название поля последнего блока для тестового или боевого сервера
     *
     * @return string
     */
    private static function getTypeLastBlock()
    {
        if (self::isTest()) {
            return 'SP_last_block_test';
        } else {
            return 'SP_last_block';
        }
    }

    /**
     * Получение параметра настроек плагина
     *
     * @param string $type
     *
     * @return mixed
     */
    private static function getAttribute($type)
    {
        $settings = self::getSettings();
        if ( ! empty($settings[$type])) {
            return $settings[$type];
        } else {
            return false;
        }
    }

    /**
     * Получение настроек плагина
     *
     * @return array
     */
    private static function getSettings()
    {
        $general  = array();
        $extended = array();
        $qr       = array();

        if ( ! empty(get_option('safepay_options'))) {
            $general = get_option('safepay_options');
        }

        if ( ! empty(get_option('safepay_options_extended'))) {
            $extended = get_option('safepay_options_extended');
        }

        if ( ! empty(get_option('safepay_options_qr'))) {
            $qr = get_option('safepay_options_qr');
        }

        return array_merge($general, $extended, $qr);
    }

    /**
     * Получение ссылки на сайт
     *
     * @return string
     */
    public static function getSiteUrl()
    {
        return get_site_url();
    }

}