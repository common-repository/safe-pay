<?php
defined('ABSPATH') || exit;

use SafePay\Blockchain\DBInvoice;
use SafePay\Blockchain\Process;

class Safe_Pay_Status
{
    /**
     * Отмена транзакции, при отмене заказа
     *
     * @param int $order_id
     *
     * @throws \SafePay\Blockchain\Sodium\SodiumException
     */
    function order_status_cancelled($order_id)
    {
        $DBInvoice = DBInvoice::getInvoiceByID($order_id, 'PAY_NUM', DBInvoice::STATUS_ACTIVE, true);
        if ($DBInvoice) {
            $process = new Process();
            $process->canceledPay($DBInvoice->ID);
        }
    }

    /**
     * Активация транзакции, при смене статуса заказа на "В ожидании оплаты"
     *
     * @param int $order_id
     */
    function order_status_pending($order_id)
    {
        DBInvoice::updateStatus($order_id, 'PAY_NUM', DBInvoice::STATUS_ACTIVE);
    }
}