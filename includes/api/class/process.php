<?php

namespace SafePay\Blockchain;

defined('ABSPATH') || exit;

class Process
{
    const STATUS_OK = 'OK';
    const STATUS_ERROR = 'ERROR';

    private $logging;

    function __construct()
    {
        $this->logging = Logging::getInstance();
    }

    /**
     * Отправляем телеграмму и добавляем инвойс
     *
     * @param array $params
     * @param string $recipient
     *
     * @return array
     * @throws Sodium\SodiumException
     */
    public function initPay($params, $recipient)
    {
        if (DBInvoice::getInvoiceByID($params['order_num'], 'PAY_NUM', DBInvoice::STATUS_ACTIVE)) {
            $result['STATUS'] = self::STATUS_OK;

            return $result;
        }

        $result         = array();
        $telegram       = new Telegram($recipient);
        $data           = $telegram->get($params, 'new');
        $url            = "/api/broadcasttelegram/" . $data['raw'];
        $requestHelpers = new Request();
        $response       = $requestHelpers->send($url);

        if ($response['STATUS'] == Request::STATUS_OK) {
            $dataResult = json_decode($response['DATA'], true);
            if ($dataResult && $dataResult['status'] == 'ok') {
                $result['STATUS']  = self::STATUS_OK;
                $result['DATA']    = $response['DATA'];
                $result['INVOICE'] = DBInvoice::addInvoice(array(
                    'STATUS'       => DBInvoice::STATUS_ACTIVE,
                    'RECIPIENT'    => $recipient,
                    'BANK_ID'      => $data['signature'],
                    'EXPIRE'       => $params['expire'],
                    'PAY_NUM'      => $params['order_num'],
                    'IS_TEST'      => Options::isTest(),
                    'CREATOR'      => serialize($params),
                    'DATE_CREATED' => time(),
                    'SITE_URL'     => Options::getSiteUrl(),
                ));

                $this->logging->log('sign_log', __('Телеграмма отправлена, добавлен инвойс в БД', 'safe-pay'),
                    $params['order_num']);
            } else {
                $result['STATUS'] = self::STATUS_ERROR;
                $result['ERROR']  = $response['DATA'];
                $this->logging->log('sign_log', __('При отправке телеграммы возникла ошибка', 'safe-pay'),
                    $params['order_num']);
            }
        } else {
            $result['STATUS'] = self::STATUS_ERROR;
            $result['ERROR']  = $response['ERROR'];
            $this->logging->log('sign_log', __('При отправке телеграммы возникла ошибка', 'safe-pay'),
                $params['order_num']);
        }

        return $result;
    }

    /**
     * Запускаем проверку транзакций в блоках
     *
     * @param string|bool $signSearch
     *
     * @return object|bool
     */
    public function resultPay($signSearch = false)
    {
        DBInvoice::deactivateExpiring();

        $this->resultPayAll();

        if ($signSearch) {
            return $this->resultPayBySign($signSearch);
        }

        return false;
    }

    /**
     * Проверяем транзакции в блоках и обновляем статусы
     *
     * @return bool
     */
    private function resultPayAll()
    {
        $next   = Options::getLastBlock();
        $height = $next;

        $listInvoice = $this->getListInvoice($next, $height);
        if (count($listInvoice) == 0) {
            Options::setLastBlock($height);

            return false;
        }

        $shopInvoice = $this->getShopInvoice($listInvoice);
        if (count($shopInvoice) == 0) {
            Options::setLastBlock($height);

            return false;
        }

        $arRecipients = $this->getAllBildRecipient();

        foreach ($listInvoice as $key => $itemInvoice) {
            $signature = $itemInvoice['orderSignature'];
            if ( ! isset($shopInvoice[$signature])) {
                continue;
            }
            $arInvoice = $shopInvoice[$signature];
            if ( ! $arInvoice || $itemInvoice['creator'] != $arRecipients[$arInvoice->RECIPIENT]) {
                continue;
            }
            $payCompleted = Options::completedPay($arInvoice->PAY_NUM);
            if ($payCompleted) {
                DBInvoice::updateStatus($arInvoice->ID, 'ID', DBInvoice::STATUS_FINISH);

                $this->logging->log('sign_log', __('Поступила оплата по заказу', 'safe-pay'), $arInvoice->PAY_NUM);
            }
        }

        Options::setLastBlock($height);
    }

    /**
     * Получаем список транзакций полученных из блоков
     *
     * @param int $next
     * @param int $height
     *
     * @return array
     */
    private function getListInvoice($next, &$height)
    {

        $listInvoiceFast = $this->resultPayFast();
        $listInvoiceLast = $this->resultPayLast($next, $height);
        $listInvoice     = array_merge($listInvoiceFast, $listInvoiceLast);

        return $listInvoice;
    }

    /**
     * Формируем массив инвойсов, сигнатуры которых совпали с пришедшими транзакциями
     *
     * @param array $listInvoice
     *
     * @return array
     */
    private function getShopInvoice($listInvoice)
    {

        $shopInvoice = array();
        foreach ($listInvoice as $itemInvoice) {
            if ($correct_invoice = DBInvoice::getInvoiceByID($itemInvoice['orderSignature'], 'BANK_ID',
                DBInvoice::STATUS_ACTIVE, true)) {
                $shopInvoice[$itemInvoice['orderSignature']] = $correct_invoice;
            }
        }

        return $shopInvoice;
    }

    private function getAllBildRecipient()
    {
        $array      = array();
        $recipients = DBRecipient::getListAll();
        foreach ($recipients as $recipients_item) {
            $array[$recipients_item->ATTRIBUTE] = $recipients_item->BILD;
        }

        return $array;
    }

    /**
     * Проверка сигнатуры, оплачена или нет
     *
     * @param string $signSearch
     *
     * @return object|bool
     */
    private function resultPayBySign($signSearch)
    {
        $dbInvoice = DBInvoice::getInvoiceByID($signSearch, 'BANK_ID', DBInvoice::STATUS_FINISH, true);
        if ($dbInvoice) {
            return $dbInvoice;
        }

        return false;
    }

    /**
     * Проверяем наличие транзакции в неподтвержденных блоках
     *
     * @return array
     */
    private function resultPayFast()
    {
        $result         = array();
        $bild           = Options::getAddress();
        $url            = "/apirecords/unconfirmedincomes/" . $bild;
        $requestHelpers = new Request();
        $response       = $requestHelpers->send($url, array('type' => 31, 'descending' => 'true'));

        if ($response['STATUS'] == Request::STATUS_OK) {
            if (strlen($response['DATA']) > 0) {
                $data = json_decode($response['DATA'], true);
                if ($data) {
                    foreach ($data as $item) {
                        $res = $this->validate_pay_message($item);
                        if ($res) {
                            $result[$item['signature']] = $res;
                        } else {
                            continue;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Проверяем наличие транзакции в подтвержденных блоках
     *
     * @param int $next
     * @param int $height
     *
     * @return array
     */
    private function resultPayLast($next, &$height)
    {
        $result = array();

        $bild           = Options::getAddress();
        $requestHelpers = new Request();

        do {
            $url      = "/apirecords/incomingfromblock/" . $bild . "/" . $next;
            $response = $requestHelpers->send($url);
            if ($response['STATUS'] == Request::STATUS_OK) {
                $data = json_decode($response['DATA'], true);
                if ( ! $data) {
                    $next = false;
                } elseif (array_key_exists('next', $data)) {
                    $next   = $data['next'];
                    $height = $next;
                } elseif (array_key_exists('height', $data)) {
                    $height = $data['height'];
                    $next   = false;
                } else {
                    $next = false;
                }
                if ($data && array_key_exists('txs', $data) && count($data['txs']) > 0) {
                    foreach ($data['txs'] as $item) {
                        $res = $this->validate_pay_message($item);
                        if ($res) {
                            $result[$item['signature']] = $res;
                        } else {
                            continue;
                        }
                    }
                }
            } else {
                $next = false;
            }
        } while ($next);

        return $result;
    }

    /**
     * Проверка корректности message
     *
     * @param array $item
     * @return array|bool
     */
    private function validate_pay_message($item)
    {
        $tempData = json_decode($item['message'], true);
        if (!$tempData || empty($tempData['orderSignature']) || empty($tempData['sum'])) {
            return false;
        }

        return array(
            'orderSignature' => $tempData['orderSignature'],
            'sum'            => $tempData['sum'],
            'creator'        => $item['creator'],
        );
    }

    /**
     * Отмена транзакции
     *
     * @param int $id
     *
     * @return bool
     * @throws Sodium\SodiumException
     */
    public function canceledPay($id)
    {
        self::resultPay();

        $dbInvoice = DBInvoice::getInvoiceByID($id, 'ID', DBInvoice::STATUS_ACTIVE, true);

        if ($dbInvoice) {
            $requestHelpers = new Request();
            $urlCheck       = '/apitelegrams/check/' . $dbInvoice->BANK_ID;
            $resCheck       = $requestHelpers->send($urlCheck);
            $check          = json_decode($resCheck['DATA'], true);
            if ($check) {
                $arProp           = unserialize($dbInvoice->CREATOR);
                $telegram         = new Telegram($dbInvoice->RECIPIENT);
                $params           = array(
                    "userPhone" => $arProp['userPhone'],
                    "BANK_ID"   => $dbInvoice->BANK_ID,
                    "order_num" => $dbInvoice->PAY_NUM,
                );
                $data             = $telegram->get($params, 'delete');
                $urlCancel        = "/api/broadcasttelegram/" . $data['raw'];
                $responseCanceled = $requestHelpers->send($urlCancel, array('broadcast' => 'true'));
                $dataResult       = ($responseCanceled['STATUS'] == Request::STATUS_OK)
                    ? json_decode($responseCanceled['DATA'], true)
                    : "";

                if ($dataResult['status'] === "ok") {
                    DBInvoice::updateStatus($id, 'ID', DBInvoice::STATUS_CANCELED);

                    return true;
                }
            }
        }

        return false;
    }

}