<?php
/**
 * SIGEBI - Demostración en vivo de la refactorización (Actividad 4).
 *
 * Ejecuta el código ORIGINAL (carpeta original/, sin modificar) y el código
 * REFACTORIZADO (carpeta src/) con los mismos datos y muestra el resultado lado a lado.
 *
 * Uso:   php demo.php            (las 5 escenas seguidas)
 *        php demo.php 3          (solo la escena 3)
 *        php demo.php pausa      (espera ENTER entre escenas, útil para presentar)
 *
 * Requisitos: PHP 7.4+ con pdo_sqlite. No necesita MySQL ni PHPUnit.
 */
require __DIR__ . '/tests/bootstrap.php';
require_once __DIR__ . '/original/DevolucionAction.php';
require_once __DIR__ . '/original/PrestamoController.php';

use Sigebi\Circulacion\DevolucionAction;
use Sigebi\Circulacion\RenovacionAction;
use Sigebi\Circulacion\RepositorioCirculacionPdo;
use Sigebi\Circulacion\VistaCirculacion;
use Sigebi\Config\Configuracion;
use Sigebi\Multa\CalculadoraMulta;
use Sigebi\Prestamos\PrestamoService;

// ---------------------------------------------------------------- utilidades de consola
$color = function_exists('posix_isatty') ? @posix_isatty(STDOUT) : true;
function c(string $txt, string $cod): string { global $color; return $color ? "\033[{$cod}m{$txt}\033[0m" : $txt; }
function titulo(string $n, string $t): void { echo "\n", c(str_repeat('═', 72), '36'), "\n", c(" ESCENA $n · $t", '1;36'), "\n", c(str_repeat('═', 72), '36'), "\n"; }
function antes(string $t): void { echo c('  ANTES   ', '1;41;97'), ' ', $t, "\n"; }
function despues(string $t): void { echo c('  DESPUÉS ', '1;42;30'), ' ', $t, "\n"; }
function nota(string $t): void { echo c('  › ' . $t, '2'), "\n"; }
function pesos(int $v): string { return ($v < 0 ? '-$' : '$') . number_format(abs($v), 0, ',', '.'); }
function pausa(): void { global $argv; if (in_array('pausa', $argv, true)) { echo c("\n  [ENTER para continuar]", '2'); fgets(STDIN); } }

const ESTUDIANTE = 1; const DOCENTE = 2; const EXTERNO = 3;
const GENERAL = 1; const RESERVA = 3;

/** Ejecuta el devolver() ORIGINAL y devuelve [multa guardada, html impreso]. */
function devolverOriginal(int $dias, int $usr, int $mat): array
{
    $GLOBALS['__pdo'] = TestDb::crear();
    $id = TestDb::prestamoVencido($GLOBALS['__pdo'], $dias, $usr, $mat);
    ob_start(); devolver($id); $html = ob_get_clean();
    return [(int) $GLOBALS['__pdo']->query("SELECT multa FROM prestamos WHERE id=$id")->fetchColumn(), $html];
}

/** Ejecuta el DevolucionAction REFACTORIZADO. */
function devolverNuevo(int $dias, int $usr, int $mat): int
{
    $pdo = TestDb::crear();
    $id = TestDb::prestamoVencido($pdo, $dias, $usr, $mat);
    return (new DevolucionAction(new RepositorioCirculacionPdo($pdo), new CalculadoraMulta()))->devolver($id);
}

/** Ejecuta el renovar() ORIGINAL en un proceso aparte (usa header() + exit). */
function renovarOriginal(int $dias, int $usr, int $mat, int $renov = 0): array
{
    $json = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/tests/Original/run_renovar.php') . ' ' .
        escapeshellarg(json_encode(['dias' => $dias, 'usr' => $usr, 'mat' => $mat, 'renov' => $renov])));
    return json_decode((string) $json, true) ?: [];
}

function renovarNuevo(int $dias, int $usr, int $mat, int $renov = 0)
{
    $pdo = TestDb::crear();
    $id = TestDb::prestamoVencido($pdo, $dias, $usr, $mat, $renov);
    $config = Configuracion::desdeFila(['dato3' => 7, 'dato4' => 14, 'dato17' => 500, 'dato21' => 2, 'dato35' => '', 'dato52' => 0]);
    return (new RenovacionAction(new RepositorioCirculacionPdo($pdo), new CalculadoraMulta(), $config))->renovar($id);
}

// ---------------------------------------------------------------- escenas
$escenas = [];

$escenas[1] = function () {
    titulo('1', 'La regla de multas duplicada cobraba distinto (F1 vs F2)');
    nota('Caso: estudiante, material de RESERVA, 3 días de retraso.');
    [$mDev] = devolverOriginal(3, ESTUDIANTE, RESERVA);
    $ren = renovarOriginal(3, ESTUDIANTE, RESERVA);
    preg_match('/valor=(-?\d+)/', implode(' ', $ren['headers'] ?? []), $m);
    $mRen = isset($m[1]) ? (int) $m[1] : 0;
    antes('devolver()  cobra ' . c(pesos($mDev), '1') . '    renovar()  cobra ' . c(pesos($mRen), '1;31') . '   ← misma situación, dos precios');
    $nDev = devolverNuevo(3, ESTUDIANTE, RESERVA);
    $nRen = renovarNuevo(3, ESTUDIANTE, RESERVA)->valorMulta;
    despues('devolver()  cobra ' . c(pesos($nDev), '1') . '    renovar()  cobra ' . c(pesos($nRen), '1;32') . '   ← una sola CalculadoraMulta');
    nota('Técnicas: Extract Class + Move Method + Introduce Parameter Object (CondicionesMora).');
};

$escenas[2] = function () {
    titulo('2', 'Los 7 casos del enunciado: comportamiento conservado y defecto corregido');
    $casos = [
        [0, ESTUDIANTE, GENERAL], [1, ESTUDIANTE, GENERAL], [5, ESTUDIANTE, GENERAL], [3, ESTUDIANTE, RESERVA],
        [3, DOCENTE, GENERAL], [10, DOCENTE, GENERAL], [2, EXTERNO, GENERAL],
    ];
    $usr = [1 => 'Estudiante', 2 => 'Docente', 3 => 'Externo'];
    $mat = [1 => 'General', 3 => 'Reserva'];
    printf("  %-5s %-11s %-8s %12s %14s   %s\n", 'Días', 'Usuario', 'Material', 'Original', 'Refactorizado', '');
    foreach ($casos as [$d, $u, $t]) {
        [$o] = devolverOriginal($d, $u, $t);
        $n = devolverNuevo($d, $u, $t);
        $obs = $o === $n ? c('igual', '32') : c('defecto corregido (multa negativa → 0)', '1;33');
        printf("  %-5d %-11s %-8s %12s %14s   %s\n", $d, $usr[$u], $mat[$t], $o < 0 ? c(str_pad(pesos($o), 12, ' ', STR_PAD_LEFT), '1;31') : pesos($o), pesos($n), $obs);
    }
    nota('La corrección se hizo en un commit SEPARADO de la refactorización (fa71d5e).');
};

$escenas[3] = function () {
    titulo('3', 'Prueba de equivalencia: 240 combinaciones original vs refactorizado');
    $iguales = 0; $corregidas = 0; $otras = 0; $total = 0;
    foreach ([ESTUDIANTE, DOCENTE, EXTERNO] as $u) {
        foreach ([GENERAL, RESERVA] as $t) {
            for ($d = 0; $d < 40; $d++) {
                [$o, $html] = devolverOriginal($d, $u, $t);
                $n = devolverNuevo($d, $u, $t);
                $mismoHtml = $html === (new VistaCirculacion())->mensajeDevolucion($n);
                $total++;
                if ($o === $n && $mismoHtml) { $iguales++; }
                elseif ($o < 0 && $n === 0 && $mismoHtml) { $corregidas++; }
                else { $otras++; }
            }
        }
    }
    echo "  Combinaciones ejecutadas (0–39 días × 3 usuarios × 2 materiales): ", c((string) $total, '1'), "\n";
    echo "  Resultado y mensaje idénticos:                                     ", c((string) $iguales, '1;32'), "\n";
    echo "  Multa negativa del original corregida a 0:                         ", c((string) $corregidas, '1;33'), "\n";
    echo "  Cualquier otra diferencia:                                         ", c((string) $otras, $otras ? '1;31' : '1;32'), "\n";
};

$escenas[4] = function () {
    titulo('4', 'Inyección SQL en el registro de préstamos (F3)');
    $ataque = "zzz' OR codigo='U001";
    nota("Código de usuario escrito en el formulario:  $ataque");
    $GLOBALS['__pdo'] = TestDb::crear();
    TestDb::sembrarCatalogo($GLOBALS['__pdo']);
    $_POST = ['codigo_usuario' => $ataque, 'ejemplar' => 'B001'];
    ob_start(); (new PrestamoController())->registrar(); $html = strip_tags(ob_get_clean());
    $n = (int) $GLOBALS['__pdo']->query('SELECT COUNT(*) FROM prestamos')->fetchColumn();
    antes(c($html, '1;31') . "  (préstamos creados: $n)");
    $pdo = TestDb::crear();
    TestDb::sembrarCatalogo($pdo);
    $r = (new PrestamoService(new RepositorioCirculacionPdo($pdo)))->registrar($ataque, 'B001');
    $n2 = (int) $pdo->query('SELECT COUNT(*) FROM prestamos')->fetchColumn();
    despues(c($r->mensaje, '1;32') . "  (préstamos creados: $n2)");
    nota('El SQL salió de la lógica a RepositorioCirculacionPdo, que solo usa consultas preparadas.');
    echo "\n";
    nota('Flujo normal (Ana Pérez, U001, ejemplar B001) con el servicio nuevo:');
    $pdo = TestDb::crear(); TestDb::sembrarCatalogo($pdo);
    $ok = (new PrestamoService(new RepositorioCirculacionPdo($pdo)))->registrar('U001', 'B001');
    despues(strip_tags((new VistaCirculacion())->mensajePrestamo($ok)));
};

$escenas[5] = function () {
    titulo('5', 'Configuración con nombres en lugar de dato1…dato70 (F4)');
    $fila = ['dato3' => 7, 'dato4' => 14, 'dato17' => 500, 'dato21' => 2, 'dato35' => 'biblioteca@ucundinamarca.edu.co', 'dato52' => 0];
    antes("\$cfg['dato21']  → " . $fila['dato21'] . c('   ¿qué es dato21? hay que leer código para saberlo', '2'));
    $cfg = Configuracion::desdeFila($fila);
    despues('$config->maximoRenovaciones()     → ' . $cfg->maximoRenovaciones());
    despues('$config->diasPrestamoEstudiante() → ' . $cfg->diasPrestamoEstudiante());
    despues('$config->datoDesconocido52()      → ' . $cfg->datoDesconocido52() . c('   (nombre honesto hasta validarlo)', '2'));
    echo "\n";
    nota('El máximo de renovaciones ya no está escrito en renovar(): viene de la configuración.');
    $r = renovarNuevo(0, ESTUDIANTE, GENERAL, 2);
    despues('Préstamo con 2 renovaciones → ' . strip_tags((new VistaCirculacion())->mensajeRenovacion($r)));
};

// ---------------------------------------------------------------- ejecución
echo c("\n  SIGEBI · Biblioteca Universitaria Villa Verde — demostración de la refactorización\n", '1');
echo c('  Código original: original/   ·   Código refactorizado: src/   ·   PHP ' . PHP_VERSION . "\n", '2');
$solo = null;
foreach (array_slice($argv, 1) as $a) { if (ctype_digit($a)) { $solo = (int) $a; } }
foreach ($escenas as $n => $escena) {
    if ($solo !== null && $solo !== $n) { continue; }
    $escena();
    if ($solo === null) { pausa(); }
}
echo "\n";
