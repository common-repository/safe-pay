<?php

use SafePay\Blockchain\Base;
use SafePay\Blockchain\Cron;
use SafePay\Blockchain\DBInvoice;
use SafePay\Blockchain\DBServer;
use SafePay\Blockchain\Options;
use SafePay\Blockchain\Process;

defined('ABSPATH') || exit;

class Safe_Pay_Api
{
    private $safe_pay;

    private $version;

    /**
     * Safe_Pay_Api constructor.
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
     * Генерация ключей и счёта
     *
     * @throws \SafePay\Blockchain\Sodium\SodiumException
     */
    public function generate_account()
    {
        if ( ! check_ajax_referer('safe_pay_admin', 'security')) {
            echo json_encode(new WP_Error('rest_forbidden', __('Вы не прошли защиту', 'safe-pay'),
                array('status' => 401)));
            wp_die();
        }
        $base    = new Base();
        $account = $base->generateAccount();
        unset($account['seed'], $account['accountSeed'], $account['numAccount']);

        echo json_encode($account);
        wp_die();
    }

    /**
     * Апи отправки инвойса
     *
     * @throws \SafePay\Blockchain\Sodium\SodiumException
     */
    public function send_invoice()
    {
        if ( ! empty($_POST)) {
            $error = array();
            if ( ! check_ajax_referer('safe_pay_public', 'security')) {
                echo json_encode(new WP_Error('rest_forbidden', __('Вы не прошли защиту', 'safe-pay'),
                    array('status' => 401)));
                $error['data'][] = __('Вы не прошли защиту', 'safe-pay');
            }

            $data = $this->validate_data_pay($_POST);

            if ( ! empty($data['error'])) {
                $error['data'] += $data['error'];
            }

            if (isset($error['data']) && count($error['data']) > 0) {
                $error['status'] = 'error';
                echo json_encode($error);
                wp_die();
            }

            $this->canceled_invoice_by_order($data['data']['order_num']);

            echo json_encode($this->send_data_pay($data['data']));
        }
        wp_die();
    }

    /**
     * Апи для проверки оплаты
     */
    public function check_pay()
    {
        if ( ! check_ajax_referer('safe_pay_public', 'security')) {
            echo json_encode(false);
            wp_die();
        }

        $signature = sanitize_text_field($_POST['signature']);
        if ( ! preg_match("~^[a-zA-Z0-9]*$~", $signature)) {
            echo json_encode(false);
            wp_die();
        }
        $process = new Process();
        $process->resultPay($signature);
        echo json_encode(DBInvoice::getInvoiceByID($signature, 'BANK_ID', DBInvoice::STATUS_FINISH));
        wp_die();
    }

    /**
     * Апи для показа QR-кода
     *
     * @throws \SafePay\Blockchain\Sodium\SodiumException
     */
    public function qr_pay()
    {
        if ( ! empty($_POST)) {
            $error = array();
            if ( ! check_ajax_referer('safe_pay_public', 'security')) {
                echo json_encode(new WP_Error('rest_forbidden', __('Вы не прошли защиту', 'safe-pay'),
                    array('status' => 401)));
                $error['data'][] = __('Вы не прошли защиту', 'safe-pay');
            }

            $data = $this->validate_data_pay($_POST);

            if ( ! empty($data['error'])) {
                $error['data'] += $data['error'];
            }

            if (isset($error['data']) && count($error['data']) > 0) {
                $error['status'] = 'error';
                echo json_encode($error);
                wp_die();
            }

            $this->canceled_invoice_by_order($data['data']['order_num']);

            $data['data']['userPhone'] = md5($data['data']['order_num'] . $_SERVER['SERVER_NAME']);

            $telegram = $this->send_data_pay($data['data']);

            $order = new WC_Order($data['data']['order_num']);

            if ( ! empty($order) && ! empty($telegram['STATUS']) && $telegram['STATUS'] == 'OK') {
                include dirname(__FILE__) . '/phpqrcode/qrlib.php';
                echo json_encode($this->load_qr($order, $data['data']['description']));
                wp_die();
            } else {
                echo 1;
                echo json_encode(false);
                wp_die();
            }
        } else {
            echo json_encode(false);
            wp_die();
        }
    }

    /**
     * Валидация данных для отправки телеграммы
     *
     * @param array $data
     *
     * @return array
     */
    private function validate_data_pay($data)
    {
        $result = array();

        $result['data']['recipient'] = sanitize_text_field($data['recipient']);
        if ( ! preg_match("~^[a-zA-Z0-9]*$~", $result['data']['recipient'])) {
            $result['error'][] = __('Реципиент не верного формата', 'safe-pay');
        }

        $result['data']['order_date'] = sanitize_text_field($data['order_date']);
        if ( ! filter_var($result['data']['order_date'], FILTER_VALIDATE_INT)) {
            $result['error'][] = __('Время заказа не верного формата', 'safe-pay');
        }

        $result['data']['order_num'] = sanitize_text_field($data['order_num']);
        if ( ! filter_var($result['data']['order_num'], FILTER_VALIDATE_INT)) {
            $result['error'][] = __('Номер заказа не верного формата', 'safe-pay');
        }

        $result['data']['userPhone'] = sanitize_text_field($data['userPhone']);
        if ( ! filter_var($result['data']['userPhone'], FILTER_VALIDATE_INT)) {
            $result['error'][] = __('Номер телефона не верного формата', 'safe-pay');
        }

        $result['data']['curr'] = sanitize_text_field($data['curr']);
        if ( ! filter_var($result['data']['curr'], FILTER_VALIDATE_INT)) {
            $result['error'][] = __('Номер валюты не верного формата', 'safe-pay');
        }

        $result['data']['sum'] = sanitize_text_field($data['sum']);
        if ( ! preg_match("~^[0-9-\.\,]*$~", $result['data']['sum'])) {
            $result['error'][] = __('Сумма не верного формата', 'safe-pay');
        }

        $result['data']['expire'] = sanitize_text_field($data['expire']);
        if ( ! filter_var($result['data']['expire'], FILTER_VALIDATE_INT)) {
            $result['error'][] = __('Время окончания не верного формата', 'safe-pay');
        }

        $result['data']['title']       = sanitize_text_field($data['title']);
        $result['data']['description'] = sanitize_text_field($data['description']);

        return $result;
    }

    /**
     * Отправка телеграммы
     *
     * @param array $data
     *
     * @return false|mixed|string|void
     * @throws \SafePay\Blockchain\Sodium\SodiumException
     */
    private function send_data_pay($data)
    {
        $result = new Process();

        $array = array(
            'recipient'   => $data['recipient'],
            'order_date'  => $data['order_date'],
            'order_num'   => $data['order_num'],
            'userPhone'   => $data['userPhone'],
            'curr'        => $data['curr'],
            'sum'         => $data['sum'],
            'expire'      => $data['expire'],
            'title'       => $data['title'],
            'description' => $data['description']
        );

        return $result->initPay($array, $data['recipient']);
    }

    /**
     * Отмена активного инвойса по данному заказу
     *
     * @param int $order_id
     *
     * @throws \SafePay\Blockchain\Sodium\SodiumException
     */
    private function canceled_invoice_by_order($order_id)
    {
        $DBInvoice = DBInvoice::getInvoiceByID($order_id, 'PAY_NUM', 'active', true);

        if ( ! empty($DBInvoice)) {
            $process = new Process();
            $process->canceledPay($DBInvoice->ID);
        }
    }

    /**
     * Формирование данных и получение QR-кода
     *
     * @param WC_Order $order
     *
     * @param string $desc
     *
     * @return bool|string
     */
    private function load_qr($order, $desc)
    {
        if ( ! Options::getQRStatus()) {
            return false;
        }

        if ( ! $qr_name = Options::getQRName()) {
            return false;
        }

        if ( ! $qr_number = Options::getQRRS()) {
            return false;
        }

        if ( ! $qr_inn = Options::getQRINN()) {
            return false;
        }

        if ( ! $qr_bank = Options::getQRBank()) {
            return false;
        }

        if ( ! $qr_bik = Options::getQRBIK()) {
            return false;
        }

        if ( ! $qr_ksch = Options::getQRKsch()) {
            return false;
        }

        if ( ! $qr_title = $desc) {
            return false;
        }

        $amount = number_format($order->get_total(), 2, '.', '');

        $data = 'ST00012|Name=' . htmlspecialchars_decode($qr_name) . '|PersonalAcc=' . $qr_number . '|BankName=' . $qr_bank . '|BIC=' . $this->clear_bik($qr_bik) . '|CorrespAcc=' . $qr_ksch . '|Sum=' . round($amount * 100) . '|PayeeINN=' . $qr_inn . '|Purpose=' . $qr_title . '|TechCode=15';
        if ($url_qr = $this->generate_qr($data, $order)) {
            return $url_qr;
        } else {
            return false;
        }
    }

    /**
     * Очищаем бик от указания тестового режима
     *
     * @param string $bik
     *
     * @return string
     */
    private function clear_bik($bik)
    {
        return str_replace('|test', '', $bik);
    }

    /**
     * Генерация QR-кода и сохранение в временной папке
     *
     * @param string $data
     *
     * @param object $order
     *
     * @return bool|string
     */
    private function generate_qr($data, $order)
    {
        $tempDir = WP_PLUGIN_DIR . '/safe-pay/temp/';

        if ( ! file_exists($tempDir)) {
            mkdir($tempDir);
        }

        $fileName = md5($order->get_total() . $order->get_order_number()) . '.png';

        $pngAbsoluteFilePath = $tempDir . $fileName;
        $urlRelativeFilePath = WP_PLUGIN_URL . '/safe-pay/temp/' . $fileName;

        if ( ! file_exists($pngAbsoluteFilePath)) {
            QRcode::png($data, $pngAbsoluteFilePath);
        }

        return $urlRelativeFilePath;
    }

    /**
     * Апи для проверки статусов оплаты в админ-панели
     */
    public function update_status_pay()
    {
        $process = new Process();
        $process->resultPay();
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    /**
     * Апи для callback банка
     */
    public function callback()
    {
        $request = array();

        if (empty($_POST['sign']) || empty($_POST['redirect'])) {
            echo json_encode(false);
            exit();
        }

        $request['sign'] = sanitize_text_field($_POST['sign']);
        if ( ! preg_match("~^[a-zA-Z0-9]*$~", $request['sign'])) {
            echo json_encode(false);
            exit();
        }

        $request['redirect'] = sanitize_text_field($_POST['redirect']);

        $process = new Process();
        $result  = $process->resultPay($request['sign']);
        if ($request['redirect'] && $result) {
            echo json_encode(true);
        } elseif ($request['redirect']) {
            echo json_encode(false);
        }
        exit();
    }

    /**
     * Апи добавления нового сервера
     */
    public function server_add()
    {
        if ( ! check_ajax_referer('safe_pay_admin', 'security')) {
            echo json_encode(new WP_Error('rest_forbidden', __('Вы не прошли защиту', 'safe-pay'),
                array('status' => 401)));
            wp_die();
        }
        $url_server  = sanitize_text_field($_POST['URL_SERVER']);
        $type_server = sanitize_text_field($_POST['TYPE_SERVER']);
        if (empty($url_server) || empty($type_server)) {
            $result['status']  = 'error';
            $result['message'] = __('Вы ввели не все данные', 'safe-pay');
            echo json_encode($result);
            wp_die();
        }
        if (DBServer::getServerByURL(strtolower($url_server)) !== false) {
            $result['status']  = 'error';
            $result['message'] = __('Данный сервер уже добавлен', 'safe-pay');
            echo json_encode($result);
            wp_die();
        }
        if ($type_server != 'live' && $type_server != 'test') {
            $result['status']  = 'error';
            $result['message'] = __('Введён не верный тип сервера', 'safe-pay');
            echo json_encode($result);
            wp_die();
        }
        if ( ! preg_match("~^[a-zA-Zа-яА-Я0-9-\.\:\/]*$~", $url_server)) {
            $result['status']  = 'error';
            $result['message'] = __('Не верный формат ссылки на сервер', 'safe-pay');
            echo json_encode($result);
            wp_die();
        }

        $server_data = array(
            'URL_SERVER'  => strtolower($url_server),
            'TYPE_SERVER' => $type_server
        );
        if ($id = DBServer::addServer($server_data)) {
            $result['status']  = 'success';
            $result['message'] = __('Сервер успешно добавлен', 'safe-pay');
            $result['server']  = array(
                'id'   => $id,
                'url'  => $url_server,
                'type' => $type_server
            );
        } else {
            $result['status']  = 'error';
            $result['message'] = __('Возникла непредвиденная ошибка, попробуйте позже', 'safe-pay');
        }
        echo json_encode($result);
        wp_die();
    }

    /**
     * Апи удаления сервера
     */
    public function server_del()
    {
        if ( ! check_ajax_referer('safe_pay_admin', 'security')) {
            echo json_encode(new WP_Error('rest_forbidden', __('Вы не прошли защиту', 'safe-pay'),
                array('status' => 401)));
            wp_die();
        }
        $server_id = sanitize_text_field($_POST['server_id']);
        if (filter_var($server_id, FILTER_VALIDATE_INT)) {
            DBServer::deleteServer($server_id);
            $result['status']  = 'success';
            $result['message'] = __('Сервер успешно удалён', 'safe-pay');
        } else {
            $result['status']  = 'error';
            $result['message'] = __('У вас нет доступа для удаления сервера', 'safe-pay');
        }
        echo json_encode($result);
        wp_die();
    }

    /**
     * Запуск задачи на проверку серверов
     *
     * @return string
     */
    public function cron_server()
    {
        $cron = new Cron();
        $cron->availableServers();

        return json_encode(array('status' => 'success'));
    }

    /**
     * Запуск задачи на проверку оплат
     *
     * @return string
     */
    public function cron_pay()
    {
        $cron = new Cron();
        $cron->resultPay();

        return json_encode(array('status' => 'success'));
    }

    /**
     * Функция маршрутизации rest api
     */
    public function rest_safe_pay()
    {
        register_rest_route($this->safe_pay . '/v1', '/api/(?P<action>.+)', array(
            'methods'  => WP_REST_Server::ALLMETHODS,
            'callback' => array($this, 'rest_safe_pay_func'),
            'args'     => array(
                'action' => array(
                    'type'              => 'string',
                    'sanitize_callback' => function ($param, $request, $key) {
                        if ( ! method_exists($this, $param)) {
                            return new WP_Error();
                        }

                        return $param;
                    }
                )
            )
        ));
    }

    /**
     * Функция подгрузки нужного метода для rest api
     *
     * @param $request
     *
     * @return WP_Error|string
     */
    public function rest_safe_pay_func($request)
    {
        $param  = $request->get_param('action');
        $secure = array(
            'update_status_pay',
            'callback',
            'cron_pay',
            'cron_server'
        );
        if ( ! in_array($param, $secure)) {
            return new WP_Error('rest_forbidden', __('Вы не прошли защиту', 'safe-pay'),
                array('status' => 401));
        }

        return $this->$param();
    }
}