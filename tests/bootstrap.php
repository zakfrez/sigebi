<?php
// Arnés de pruebas: simula las funciones mysql_* (eliminadas en PHP 7) sobre SQLite en memoria,
// para poder ejecutar el código ORIGINAL del SIGEBI sin tocarlo.
date_default_timezone_set('America/Bogota');

spl_autoload_register(function ($clase) {
    $prefijo = 'Sigebi\\';
    if (strpos($clase, $prefijo) === 0) {
        $ruta = __DIR__ . '/../src/' . str_replace('\\', '/', substr($clase, strlen($prefijo))) . '.php';
        if (is_file($ruta)) { require $ruta; }
    }
});

require __DIR__ . '/TestDb.php';
require __DIR__ . '/FakeMysql.php';
