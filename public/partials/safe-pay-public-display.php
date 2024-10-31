<?php
defined('ABSPATH') || exit;

use SafePay\Blockchain\DBInvoice;
use SafePay\Blockchain\DBRecipient;

?>
<div class="safe-pay-payment">
    <div class="safe-pay-payment__title">
        <h3><?php echo __('Безопасные платежи SAFE PAY', 'safe-pay'); ?></h3>
        <img src="<?php echo SAFE_PAY_URL; ?>public/img/safe-pay.png"
             alt="<?php echo __('Безопасные платежи SAFE PAY', 'safe-pay'); ?>">
    </div>
    <?php if ($dbInvoice = DBInvoice::getInvoiceByID($order_id, 'PAY_NUM', DBInvoice::STATUS_FINISH, true)): ?>
        <p class="safe-pay-payment__desc">
            <?php echo __('Спасибо, оплата по заказу поступила! Ваш заказ передан в обработку.', 'safe-pay'); ?>
        </p>
    <?php elseif ($dbInvoice = DBInvoice::getInvoiceByID($order_id, 'PAY_NUM', DBInvoice::STATUS_ACTIVE, true)): ?>

        <?php $message = unserialize($dbInvoice->CREATOR); ?>
        <?php if (strlen($message['userPhone']) > 10): ?>

            <div class="safe-pay-payment__qr-block qr-block">
                <div class="qr-block__left">
                    <img src="<?php echo WP_PLUGIN_URL . '/safe-pay/temp/' . md5($order->get_total() . $order->get_order_number()) . '.png'; ?>"
                         alt="QR-code">
                </div>
                <div class="qr-block__right">
                    <p><?php echo __('Для оплаты счёта, отсканируйте QR-код и оплатите. После оплаты свяжитесь с магазином, для проверки поступления средств.',
                            'safe-pay') ?></p>
                    <p><?php echo __('Если у вас возникли сложности с оплатой по QR коду, или же вы решили оплатить стандартными средствами оплаты SAFE PAY, нажмите на кнопку ниже, Вас переместит на страницу выбора банка.',
                            'safe-pay') ?></p>
                    <a class="button cancel safe-pay-payment-form__cancel"
                       href="<?php echo $order->get_cancel_order_url(); ?>"><?php echo __('Отказаться от оплаты',
                            'safe-pay'); ?></a>
                </div>
            </div>

        <?php else: ?>

            <?php $recipient = DBRecipient::getByAttribute($dbInvoice->RECIPIENT); ?>
            <div class="safe-pay-payment__desc">
                <p>
                    <?php printf(__('Спасибо, счёт отправлен в банк %s по вашему номеру телефона. Для оплаты счёта перейдите в личный кабинет банка и найдите поступивший счёт на оплату.',
                        'safe-pay'), '<b>&laquo;' . $recipient->NAME . '&raquo;</b>') ?>
                </p>
                <?php echo __('На оплату счёта у вас осталось:', 'safe-pay'); ?>
                <span class="safe-pay-payment__invoice_end"
                      data-invoice_end="<?php echo $dbInvoice->EXPIRE - time(); ?>"></span>
            </div>
            <form action="" method="POST" class="safe-pay-payment-form" name="paymentform_safe_pay">
                <?php echo implode("\n", $args_array); ?>
                <input type="submit" class="button alt safe-pay-payment-form__submit"
                       value="<?php echo __('Проверить оплату', 'safe-pay'); ?>" data-invoice_status="false"
                       data-invoice_signature="<?php echo $dbInvoice->BANK_ID; ?>"
                       data-invoice_order_id="<?php echo $order_id; ?>"/>
                <a class="button cancel safe-pay-payment-form__cancel"
                   href="<?php echo $order->get_cancel_order_url(); ?>">
                    <?php echo __('Отказаться от оплаты', 'safe-pay'); ?>
                </a>
            </form>

        <?php endif; ?>

    <?php else: ?>
        <p class="safe-pay-payment__desc">
            <?php echo __('Спасибо за Ваш заказ, пожалуйста, нажмите кнопку ниже, чтобы сделать платёж.',
                'safe-pay'); ?>
        </p>
        <form action="" method="POST" class="safe-pay-payment-form" name="paymentform_safe_pay">
            <?php echo implode("\n", $args_array); ?>
            <input type="submit" class="button alt safe-pay-payment-form__submit"
                   value="<?php echo __('Оплатить', 'safe-pay'); ?>" data-invoice_status="true"
                   data-invoice_signature="false"/>
            <a class="button cancel safe-pay-payment-form__cancel" href="<?php echo $order->get_cancel_order_url(); ?>">
                <?php echo __('Отказаться от оплаты', 'safe-pay'); ?>
            </a>
        </form>
    <?php endif; ?>
</div>