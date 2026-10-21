<?php
use PHPUnit\Framework\TestCase;
use Sigebi\Circulacion\DevolucionAction;
use Sigebi\Circulacion\RepositorioCirculacionPdo;
use Sigebi\Circulacion\VistaCirculacion;
use Sigebi\Multa\CalculadoraMulta;

require_once __DIR__ . '/../../original/DevolucionAction.php';

/**
 * Compara el devolver() ORIGINAL con el refactorizado en 240 combinaciones
 * (0 a 39 días x 3 tipos de usuario x 2 tipos de material). Única diferencia permitida:
 * donde el original guardaba una multa NEGATIVA (defecto), el refactorizado guarda 0.
 */
final class EquivalenciaF1Test extends TestCase
{
    public function testMismoComportamientoQueElOriginal(): void
    {
        $vista = new VistaCirculacion();
        $total = 0;
        $iguales = 0;
        $negativasOriginal = 0;
        foreach ([1, 2, 3] as $usr) {
            foreach ([1, 3] as $mat) {
                for ($dias = 0; $dias < 40; $dias++) {
                    // Original
                    $GLOBALS['__pdo'] = TestDb::crear();
                    $id = TestDb::prestamoVencido($GLOBALS['__pdo'], $dias, $usr, $mat);
                    ob_start();
                    devolver($id);
                    $salidaOriginal = ob_get_clean();
                    $multaOriginal = (int) $GLOBALS['__pdo']->query("SELECT multa FROM prestamos WHERE id=$id")->fetchColumn();

                    // Refactorizado
                    $pdo = TestDb::crear();
                    $idNuevo = TestDb::prestamoVencido($pdo, $dias, $usr, $mat);
                    $accion = new DevolucionAction(new RepositorioCirculacionPdo($pdo), new CalculadoraMulta());
                    $multaNueva = $accion->devolver($idNuevo);

                    $total++;
                    $this->assertSame(max(0, $multaOriginal), $multaNueva, "u=$usr m=$mat d=$dias");
                    $this->assertSame($salidaOriginal, $vista->mensajeDevolucion($multaNueva), "mensaje u=$usr m=$mat d=$dias");
                    if ($multaOriginal < 0) { $negativasOriginal++; } else { $iguales++; }
                }
            }
        }
        file_put_contents(__DIR__ . '/../../evidencias/equivalencia_f1.json',
            json_encode(['combinaciones' => $total, 'identicas' => $iguales, 'multas_negativas_corregidas_a_cero' => $negativasOriginal]));
        $this->assertSame(240, $total);
    }
}
