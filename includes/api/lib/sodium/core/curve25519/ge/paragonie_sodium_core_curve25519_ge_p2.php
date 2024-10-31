<?php

namespace SafePay\Blockchain\Sodium\Core\Curve25519\Ge;

use \SafePay\Blockchain\Sodium\Core\Curve25519\ParagonIE_Sodium_Core_Curve25519_Fe;

class ParagonIE_Sodium_Core_Curve25519_Ge_P2
{
    /**
     * @var ParagonIE_Sodium_Core_Curve25519_Fe
     */
    public $X;
    /**
     * @var ParagonIE_Sodium_Core_Curve25519_Fe
     */
    public $Y;
    /**
     * @var ParagonIE_Sodium_Core_Curve25519_Fe
     */
    public $Z;

    /**
     * ParagonIE_Sodium_Core_Curve25519_Ge_P2 constructor.
     *
     * @param ParagonIE_Sodium_Core_Curve25519_Fe|null $x
     * @param ParagonIE_Sodium_Core_Curve25519_Fe|null $y
     * @param ParagonIE_Sodium_Core_Curve25519_Fe|null $z
     *
     * @internal You should not use this directly from another application
     *
     */
    public function __construct(
        ParagonIE_Sodium_Core_Curve25519_Fe $x = null,
        ParagonIE_Sodium_Core_Curve25519_Fe $y = null,
        ParagonIE_Sodium_Core_Curve25519_Fe $z = null
    ) {
        if ($x === null) {
            $x = new ParagonIE_Sodium_Core_Curve25519_Fe();
        }
        $this->X = $x;
        if ($y === null) {
            $y = new ParagonIE_Sodium_Core_Curve25519_Fe();
        }
        $this->Y = $y;
        if ($z === null) {
            $z = new ParagonIE_Sodium_Core_Curve25519_Fe();
        }
        $this->Z = $z;
    }
}