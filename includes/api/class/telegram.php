<?php

namespace SafePay\Blockchain;

defined('ABSPATH') || exit;

use SafePay\Blockchain\Helpers\Base58;
use SafePay\Blockchain\Helpers\ConvertToBytes;

class Telegram
{
    private $recipient;
    private $logging;

    /**
     * Telegram constructor.
     *
     * @param string $recipient
     */
    public function __construct($recipient)
    {
        $this->logging   = Logging::getInstance();
        $this->recipient = DBRecipient::getByAttribute($recipient);
    }

    /**
     * Получаем сигнатуру и байткод
     *
     * @param array $params
     * @param string $type
     *
     * @return array
     * @throws Sodium\SodiumException
     */
    public function get($params, $type)
    {
        $result              = array();
        $data_first          = $this->getDataFirst();
        $data_last           = $this->getDataLast($params, $type);
        $result['signature'] = $this->getSign($data_first, $data_last);
        $result['raw']       = $this->getRaw($data_first, $data_last, $result['signature']);

        return $result;
    }

    /**
     * Получаем сигнатуру
     *
     * @param array $data_first
     * @param array $data_last
     *
     * @return string
     * @throws Sodium\SodiumException
     */
    private function getSign($data_first, $data_last)
    {
        $port        = (Options::isTest()) ? 9066 : 9046;
        $dataForSign = array_merge($data_first, $data_last, ConvertToBytes::fromInt32($port));
        $dataForSign = Base58::encode($dataForSign);
        $base        = new Base();
        $sign        = $base->sign($dataForSign, Options::getPrivateKey());

        return $sign['signature'];
    }

    /**
     * Получаем байткод
     *
     * @param array $data_first
     * @param array $data_last
     * @param string $sign
     *
     * @return string
     */
    private function getRaw($data_first, $data_last, $sign)
    {
        return Base58::encode(array_merge($data_first, Base58::decode($sign), $data_last));
    }

    /**
     * Формируем первую часть телеграммы
     *
     * @return array
     */
    private function getDataFirst()
    {
        $tiemstamp = round(microtime(true) * 1000);
        //TRANSACTION_TYPE
        $data_first = array(31, 0, 0, 0);
        //TIMESTAMP
        $data_first = array_merge($data_first, ConvertToBytes::fromInt64($tiemstamp));
        //REFERENCE
        $data_first = array_merge($data_first, array(0, 0, 0, 0, 0, 0, 0, 0));
        //CREATOR PUBLIC KEY
        $data_first = array_merge($data_first, Base58::decode(Options::getPublicKey()));
        //FEE POW
        $data_first = array_merge($data_first, array(0));

        return $data_first;
    }

    /**
     * Формируем вторую часть телеграммы
     *
     * @param array $params
     * @param string $type
     *
     * @return array
     * @throws Sodium\SodiumException
     */
    private function getDataLast($params, $type)
    {
        //RECIPIENT
        $data_last = Base58::decode($this->recipient->BILD);
        //ASSET KEY
        $data_last = array_merge($data_last, array(0, 0, 0, 0, 0, 0, 0, 0));
        //AMOUNT
        $data_last = array_merge($data_last, array(0, 0, 0, 0, 0, 0, 0, 0));

        $titleByte = ConvertToBytes::fromString($params["userPhone"]);
        //TITLE LENGTH
        $data_last = array_merge($data_last, array(count($titleByte)));
        //TITLE
        $data_last = array_merge($data_last, $titleByte);

        $message = array();
        if ($type == 'new') {
            $message = $this->getMessage($params);
            $this->logging->log('sign_log', __('Создана телеграмма на добавление сигнатуры', 'safe-pay'),
                $params['order_num']);
        } elseif ($type == 'delete') {
            $this->logging->log('sign_log', __('Создана телеграмма на удаление сигнатуры', 'safe-pay'),
                $params['order_num']);
            $message = $this->getMessageDelete($params);
        }
        //MESSAGE LENGTH
        $data_last = array_merge($data_last, ConvertToBytes::fromInt32(count($message)));
        //MESSAGE
        $data_last = array_merge($data_last, $message);
        //ENCRYPTED
        if ($type == 'delete') {
            $data_last = array_merge($data_last, array(0));
        } else {
            $data_last = array_merge($data_last, array(1));
        }
        //TEXT
        $data_last = array_merge($data_last, array(1));

        return $data_last;
    }

    /**
     * Получаем зашифрованное сообщение
     *
     * @param array $params
     *
     * @return string
     * @throws Sodium\SodiumException
     */
    private function getMessage($params)
    {
        $arMessage = array(
            'date'        => $params['order_date'],
            'order'       => $params['order_num'],
            'user'        => $params['userPhone'],
            'curr'        => $params['curr'],
            'sum'         => $params['sum'],
            'expire'      => $params['expire'],
            'title'       => $params['title'],
            'description' => $params['description'],
            'details'     => '-',
            'callback'    => Options::getCallback()
        );

        return $this->cryptMessage($arMessage);
    }

    /**
     * Получаем сообщение для удаления сигнатуры
     *
     * @param array $params
     *
     * @return string
     */
    private function getMessageDelete($params)
    {
        $arMessage = array(
            "__DELETE" => array(
                "list" => array($params['BANK_ID'])
            )
        );

        return ConvertToBytes::fromString(json_encode($arMessage, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param array $arMessage
     *
     * @return string
     * @throws Sodium\SodiumException
     */
    private function cryptMessage($arMessage)
    {
        $jsonMessage    = json_encode($arMessage, JSON_UNESCAPED_UNICODE);
        $base           = new Base();
        $encryptMessage = $base->dataEncrypt($jsonMessage, $this->recipient->PUBLIC_KEY, Options::getPrivateKey());

        return Base58::decode($encryptMessage['encrypted']);
    }
}