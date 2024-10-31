<?php
defined('ABSPATH') || exit;

use SafePay\Blockchain\DBInvoice;

$invoice = DBInvoice::getAllInvoice();
?>
<div class="wrap">
    <h2><?php echo get_admin_page_title() ?></h2>

    <?php include 'safe-pay-admin-nav.php'; ?>

    <?php if ($invoice !== false):
        $invoice = array_reverse($invoice);
    ?>
        <table class="safepay-settings__table safepay-table">
            <tr class="safepay-table__row safepay-table__head">
                <th class="safepay-table__col"><?php printf(__('ID', 'safe-pay')); ?></th>
                <th class="safepay-table__col"><?php printf(__('Дата', 'safe-pay')); ?></th>
                <th class="safepay-table__col"><?php printf(__('Статус', 'safe-pay')); ?></th>
                <th class="safepay-table__col"><?php printf(__('Сигнатура', 'safe-pay')); ?></th>
                <th class="safepay-table__col"><?php printf(__('Сумма', 'safe-pay')); ?></th>
                <th class="safepay-table__col"><?php printf(__('Подробнее', 'safe-pay')); ?></th>
            </tr>
            <?php foreach ($invoice as $invoice_item): ?>
                <?php $data_transaction = unserialize($invoice_item->CREATOR); ?>
                <tr class="safepay-table__row">
                    <td class="safepay-table__col"><?php echo esc_html($invoice_item->ID); ?></td>
                    <td class="safepay-table__col"><?php echo esc_html(get_date_from_gmt(date("Y-m-d H:i:s",
                            $invoice_item->DATE_CREATED))); ?></td>
                    <td class="safepay-table__col">
                        <span class="safepay-table__status--<?php echo esc_html($invoice_item->STATUS); ?>">
                            <?php echo esc_html(DBInvoice::getStatusName($invoice_item->STATUS)); ?>
                        </span>
                    </td>
                    <td class="safepay-table__col"><?php echo esc_html($invoice_item->BANK_ID); ?></td>
                    <td class="safepay-table__col"><?php echo esc_html($data_transaction['sum']); ?>
                        &nbsp;<?php echo esc_html(get_woocommerce_currency()); ?></td>
                    <td class="safepay-table__col">
                        <a href="/wp-admin/post.php?post=<?php echo esc_html($invoice_item->PAY_NUM); ?>&action=edit"><?php printf(__('Заказ',
                                'safe-pay')); ?>#<?php echo esc_html($invoice_item->PAY_NUM); ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <form method="get" action="/wp-json/safe-pay/v1/api/update_status_pay/">
            <input type="submit" class="button-primary"
                   value="<?php printf(__('Обновить статусы транзакций', 'safe-pay')); ?>"/>
        </form>
    <?php else: ?>
        <p><?php echo __('Пока ещё не поступило ни одной транзакции', 'safe-pay'); ?></p>
    <?php endif; ?>
</div>