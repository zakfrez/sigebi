<?php
// Ejecuta el renovar() ORIGINAL en un proceso aparte (porque usa exit) y devuelve cabeceras y salida en JSON.
// En CLI header() no deja rastro, así que se intercepta reemplazando la llamada en tiempo de prueba.
require __DIR__ . '/../bootstrap.php';
function registrarCabecera($h) { $GLOBALS['__headers'][] = $h; }
$origen = file_get_contents(__DIR__ . '/../../original/RenovacionAction.php');
eval('?>' . str_replace('header(', 'registrarCabecera(', $origen));
// Recibe 4 números (días, usuario, material, renovaciones). Se evita JSON porque en Windows
// escapeshellarg() elimina las comillas dobles y el JSON llega roto.
list($dias, $usr, $mat, $renov) = array_map('intval', array_slice($argv, 1, 4));
$GLOBALS['__pdo'] = TestDb::crear();
$GLOBALS['__headers'] = [];
$id = TestDb::prestamoVencido($GLOBALS['__pdo'], $dias, $usr, $mat, $renov);
ob_start();
register_shutdown_function(function () use ($id) {
    $salida = ob_get_clean();
    $fila = $GLOBALS['__pdo']->query("SELECT renovaciones FROM prestamos WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['headers' => $GLOBALS['__headers'], 'salida' => $salida, 'renovaciones' => (int) $fila['renovaciones']]);
});
renovar($id);
