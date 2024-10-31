<?php

namespace SafePay\Blockchain\Helpers;

class Base58
{
    const ALPHABET = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";

    public static function encode($buffer)
    {
        if (count($buffer) == 0) {
            return "";
        }

        $ALPHABET = self::ALPHABET;
        $i        = 0;
        $j        = 0;
        $digits   = array(0);
        while ($i < count($buffer)) {
            $j = 0;
            while ($j < count($digits)) {
                $digits[$j] <<= 8;
                $j++;
            }
            $digits[0] += $buffer[$i];
            $carry     = 0;
            $j         = 0;
            while ($j < count($digits)) {
                $digits[$j] += $carry;
                $carry      = ($digits[$j] / 58) | 0;
                $digits[$j] %= 58;
                ++$j;
            }
            while ($carry) {
                $digits[] = $carry % 58;
                $carry    = ($carry / 58) | 0;
            }
            $i++;
        }
        $i = 0;
        while ($buffer[$i] === 0 && $i < count($buffer) - 1) {
            $digits[] = 0;
            $i++;
        }
        $digits = array_reverse($digits);
        $result = '';
        foreach ($digits as $val) {
            $result .= $ALPHABET[$val];
        }

        return $result;
    }

    public static function decode($string)
    {
        if (strlen($string) === 0) {
            return '';
        }

        $ALPHABET     = self::ALPHABET;
        $ALPHABET_MAP = [];
        $i            = 0;
        while ($i < strlen($ALPHABET)) {
            $ALPHABET_MAP[$ALPHABET[$i]] = $i;
            $i++;
        }

        $i     = 0;
        $j     = 0;
        $bytes = [];
        while ($i < strlen($string)) {
            $c = $string[$i];
            if ( ! array_key_exists($c, $ALPHABET_MAP)) {
                return '';
            }
            $j = 0;
            while ($j < count($bytes)) {
                $bytes[$j] *= 58;
                $j++;
            }
            @$bytes[0] += $ALPHABET_MAP[$c];
            $carry    = 0;
            $j        = 0;
            while ($j < count($bytes)) {
                $bytes[$j] += $carry;
                $carry     = $bytes[$j] >> 8;
                $bytes[$j] &= 0xff;
                ++$j;
            }
            while ($carry) {
                $bytes[] = ($carry & 0xff);
                $carry   >>= 8;
            }
            $i++;
        }
        $i = 0;
        while ($string[$i] === "1" && $i < strlen($string) - 1) {
            $bytes[] = 0;
            $i++;
        }

        return array_reverse($bytes);
    }
}
