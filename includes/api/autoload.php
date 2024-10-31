<?php

defined('ABSPATH') || exit;

//создаём переменную с списком каталогов с классами
global $classesDir;
$classesDir = array(
    dirname(__FILE__) . '/class/',
    dirname(__FILE__) . '/class/interface/',
    dirname(__FILE__) . '/lib/',
    dirname(__FILE__) . '/lib/sodium/',
    dirname(__FILE__) . '/lib/sodium/core/',
    dirname(__FILE__) . '/lib/sodium/core/curve25519/',
    dirname(__FILE__) . '/lib/sodium/core/curve25519/ge/',
);
//ищет класс по по всем папкам
spl_autoload_register(function ($class_name) {
    $class_name = explode('\\', $class_name);
    global $classesDir;
    foreach ($classesDir as $directory) {
        if (file_exists($directory . strtolower(end($class_name)) . '.php')) {
            require_once($directory . strtolower(end($class_name)) . '.php');

            return;
        }
    }
});

require_once dirname(__FILE__) . '/lib/random_compat/random.php';