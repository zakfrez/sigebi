<?php
/** Arranque de la aplicación web del SIGEBI: carga el código refactorizado (src/) y abre la base de datos. */
date_default_timezone_set('America/Bogota');
error_reporting(E_ALL);

spl_autoload_register(function ($clase) {
    $prefijo = 'Sigebi\\';
    if (strpos($clase, $prefijo) === 0) {
        $ruta = __DIR__ . '/../src/' . str_replace('\\', '/', substr($clase, strlen($prefijo))) . '.php';
        if (is_file($ruta)) { require $ruta; }
    }
});
require __DIR__ . '/BaseDatos.php';
require __DIR__ . '/ConsultasPanel.php';

if (!extension_loaded('pdo_sqlite')) {
    exit('Falta la extensión pdo_sqlite. Actívela en php.ini (extension=pdo_sqlite) y reinicie el servidor.');
}
$carpetaDatos = __DIR__ . '/../datos';
if (!is_dir($carpetaDatos)) { mkdir($carpetaDatos, 0777, true); }
$pdo = BaseDatos::conectar($carpetaDatos . '/sigebi.sqlite');
