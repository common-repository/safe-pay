<?php

namespace SafePay\Blockchain;

defined('ABSPATH') || exit;

interface IDBRecipient
{
    /**
     * Получение реципиента по аттрибуту
     *
     * @param string $attr
     *
     * @return object|bool
     */
    public static function getByAttribute($attr);

    /**
     * Получение всех реципиентов
     * @return object|bool
     */
    public static function getListAll();

}