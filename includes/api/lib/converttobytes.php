<?php

namespace SafePay\Blockchain\Helpers;

class ConvertToBytes
{
    public static function fromInt64($int64)
    {
        return array_reverse(unpack("C*", pack("Q", $int64)));
    }

    public static function fromInt32($int32)
    {
        return array_reverse(unpack("C*", pack("L", $int32)));
    }

    public static function fromString($str)
    {
        return array_slice(unpack("C*", "\0" . $str), 1);
    }

    public static function toString($s)
    {
        return call_user_func_array('pack', array_merge(array("C*"), $s));
    }
}
