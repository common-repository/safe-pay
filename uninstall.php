<?php
defined('ABSPATH') || exit;

if ( ! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "safe_pay_invoice");
$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "safe_pay_server");
$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "safe_pay_recipient");

delete_option('safepay_options');
delete_option('safepay_options_extended');
delete_option('safepay_options_qr');
delete_option('safepay_db_version');
delete_option('woocommerce_safe-pay_settings');
