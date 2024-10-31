<?php

namespace SafePay\Blockchain;

defined('ABSPATH') || exit;

use Exception;
use SafePay\Blockchain\Helpers\Base58;
use SafePay\Blockchain\Helpers\ConvertToBytes;
use SafePay\Blockchain\Sodium\Core\ParagonIE_Sodium_Core_Ed25519;
use SafePay\Blockchain\Sodium\ParagonIE_Sodium_Crypto;
use SafePay\Blockchain\Sodium\ParagonIE_Sodium_Compat;

class Base
{
    const SEED_BYTES = 32;
    const METHOD_CRYPT = 'aes-256-cbc';
    private $iv;

    public function __construct()
    {
        $this->iv = chr(0x06) . chr(0x04) . chr(0x03) . chr(0x08) . chr(0x01) . chr(0x02) . chr(0x01) . chr(0x02)
            . chr(0x07) . chr(0x02) . chr(0x03) . chr(0x08) . chr(0x05) . chr(0x07) . chr(0x01) . chr(0x01);
    }

    /**
     * Генерируем сид
     *
     * @return array
     * @throws Exception
     */
    public function generateSeed()
    {
        try {
            $seed = random_bytes(self::SEED_BYTES);
        } catch (TypeError $e) {
            die("An unexpected error has occurred");
        } catch (Error $e) {
            die("An unexpected error has occurred");
        } catch (Exception $e) {
            die("Could not generate a random string. Is our OS secure?");
        }
        $arSeed = array(
            'seed'        => $seed,
            'seed_base58' => Base58::encode(ConvertToBytes::fromString($seed)),
        );

        return $arSeed;
    }

    /**
     * Создаём приватный и публичный ключ
     *
     * @param array $seed_base58
     *
     * @return array
     * @throws Sodium\SodiumException
     */
    public function createKeyPair($seed_base58 = null)
    {
        if (!$seed_base58) {
            $seed = $this->generateSeed();
        } else {
            $seed = array(
                'seed'        => ConvertToBytes::toString(Base58::decode($seed_base58)),
                'seed_base58' => $seed_base58,
            );
        }
        $pk = '';
        $sk = '';
        ParagonIE_Sodium_Core_Ed25519::seed_keypair($pk, $sk, $seed['seed']);
        $result = array(
            'seed'       => $seed['seed_base58'],
            'privateKey' => Base58::encode(ConvertToBytes::fromString($sk)),
            'publicKey'  => Base58::encode(ConvertToBytes::fromString($pk))
        );

        return $result;
    }

    /**
     * Получаем массив с данными счёта, сидов, ключей
     *
     * @param string|bool $seed
     * @param int|bool $nonce
     *
     * @return array
     * @throws Sodium\SodiumException
     */
    public function generateAccount($seed = false, $nonce = false)
    {
        if (!$seed) {
            $arSeed = $this->generateSeed();
            $seed = $arSeed['seed_base58'];
        }
        if (!$nonce) {
            $nonce = time();
        }
        $accountSeed = $this->getAccountSeed($seed, $nonce);
        $keyPair = $this->createKeyPair($accountSeed);

        $result = array(
            'seed'        => $seed,
            'accountSeed' => $accountSeed,
            'privateKey'  => $keyPair['privateKey'],
            'numAccount'  => $nonce,
            'publicKey'   => $keyPair['publicKey']
        );

        $result += $this->getLastBlockData();

        return $result;
    }

    /**
     * Получение последних блоков
     *
     * @return array
     */
    public function getLastBlockData()
    {
        return array(
            'lastBlock'     => $this->getLastBlock(0),
            'lastBlockTest' => $this->getLastBlock(1)
        );
    }

    /**
     * Шифрование данных
     *
     * @param string $message
     * @param string $publicKey
     * @param string $privateKey
     *
     * @return array
     * @throws Sodium\SodiumException
     */
    public function dataEncrypt($message, $publicKey, $privateKey)
    {
        $password = $this->getPassword($publicKey, $privateKey);
        $encrypted = openssl_encrypt($message, self::METHOD_CRYPT, $password, OPENSSL_RAW_DATA, $this->iv);
        $era_encrypted = chr(0x01) . $encrypted;
        $result = array(
            'encrypted' => Base58::encode(ConvertToBytes::fromString($era_encrypted)),
        );

        return $result;
    }

    /**
     * Расшифровка данных
     *
     * @param string $message
     * @param string $publicKey
     * @param string $privateKey
     *
     * @return array
     * @throws Sodium\SodiumException
     */
    public function dataDecrypt($message, $publicKey, $privateKey)
    {
        $password = $this->getPassword($publicKey, $privateKey);
        $era_encrypted = ConvertToBytes::toString(Base58::decode($message));
        $encrypted = substr($era_encrypted, 1, strlen($era_encrypted) - 1);
        $decrypted = openssl_decrypt($encrypted, self::METHOD_CRYPT, $password, OPENSSL_RAW_DATA, $this->iv);
        $result = array(
            'decrypted' => $decrypted,
        );

        return $result;
    }

    /**
     * Получение сигнатуры
     *
     * @param string $message
     * @param string $privateKey
     *
     * @return array
     * @throws Sodium\SodiumException
     */
    public function sign($message, $privateKey)
    {
        $message = ConvertToBytes::toString(Base58::decode($message));
        $privateKey = ConvertToBytes::toString(Base58::decode($privateKey));
        $sign = ParagonIE_Sodium_Core_Ed25519::sign_detached($message, $privateKey);
        $result = array(
            'signature' => Base58::encode(ConvertToBytes::fromString($sign)),
        );

        return $result;
    }

    /**
     * Получение пароля из приватного и публичного ключей
     *
     * @param string $publicKey
     * @param string $privateKey
     *
     * @return string
     * @throws Sodium\SodiumException
     */
    public function getPassword($publicKey, $privateKey)
    {
        $publicKey = ConvertToBytes::toString(Base58::decode($publicKey));
        $privateKey = ConvertToBytes::toString(Base58::decode($privateKey));
        $publicKey_curve25519 = ParagonIE_Sodium_Core_Ed25519::pk_to_curve25519($publicKey);
        $privateKey_curve25519 = ParagonIE_Sodium_Compat::crypto_sign_ed25519_sk_to_curve25519($privateKey);
        $ss = ParagonIE_Sodium_Crypto::scalarmult($privateKey_curve25519, $publicKey_curve25519);
        $password = substr(hash('sha256', $ss, true), 0, 32);

        return $password;
    }

    /**
     * Получение последнего блока
     *
     * @param bool $test
     *
     * @return int|bool
     */
    private function getLastBlock($test)
    {
        $url = "/api/height";
        $requestHelpers = new Request();
        $response = $requestHelpers->send($url, false, $test);
        if ($response['STATUS'] == Request::STATUS_OK) {
            return $response['DATA'];
        }

        return false;
    }

    /**
     * Получаем сид аккаунта
     *
     * @param string $seed_base58
     * @param int $nonce
     *
     * @return string
     */
    private function getAccountSeed($seed_base58, $nonce)
    {
        $result = array();
        $nonce = $nonce - 1;
        $nonce_byte = ConvertToBytes::fromInt32($nonce);
        $result = array_merge($result, $nonce_byte);
        $seed_byte = Base58::decode($seed_base58);
        $result = array_merge($result, $seed_byte);
        $result = array_merge($result, $nonce_byte);
        $hash_res = hash('sha256', ConvertToBytes::toString($result), true);
        $hash_hash_res = hash('sha256', $hash_res, true);

        return Base58::encode(ConvertToBytes::fromString($hash_hash_res));
    }
}