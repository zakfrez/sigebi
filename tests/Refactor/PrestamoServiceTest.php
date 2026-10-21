<?php
use PHPUnit\Framework\TestCase;
use Sigebi\Circulacion\RepositorioCirculacionPdo;
use Sigebi\Circulacion\VistaCirculacion;
use Sigebi\Prestamos\PrestamoService;

require_once __DIR__ . '/../../original/PrestamoController.php';

/**
 * Ejecuta los MISMOS escenarios con el registrar() original y con PrestamoService,
 * y exige el mismo mensaje HTML y el mismo estado final de la base de datos.
 */
final class PrestamoServiceTest extends TestCase
{
    public static function escenarios(): array
    {
        return [
            'préstamo válido'              => ['U001', 'B001', []],
            'usuario inactivo'             => ['U002', 'B001', []],
            'estado en minúscula'          => ['D001', 'B001', []],
            'ejemplar no disponible'       => ['U001', 'B002', []],
            'estudiante con 5 préstamos'   => ['U001', 'B001', [1 => 5]],
            'estudiante con 4 préstamos'   => ['U001', 'B001', [1 => 4]],
            'docente con 9 préstamos'      => ['D001', 'B001', [3 => 9]],
            'docente con 10 préstamos'     => ['D001', 'B001', [3 => 10]],
            'usuario inexistente'          => ['NOEXISTE', 'B001', []],
        ];
    }

    private function preparar(array $prestamosPrevios): PDO
    {
        $pdo = TestDb::crear();
        TestDb::sembrarCatalogo($pdo);
        foreach ($prestamosPrevios as $idUsr => $cuantos) {
            TestDb::prestamosActivos($pdo, $idUsr, $cuantos);
        }
        return $pdo;
    }

    private function estado(PDO $pdo): array
    {
        return [
            (int) $pdo->query('SELECT COUNT(*) FROM prestamos')->fetchColumn(),
            $pdo->query('SELECT group_concat(disponible) FROM ejemplares')->fetchColumn(),
        ];
    }

    /** @dataProvider escenarios */
    public function testMismoResultadoQueElOriginal(string $usuario, string $ejemplar, array $previos): void
    {
        $GLOBALS['__pdo'] = $this->preparar($previos);
        $_POST = ['codigo_usuario' => $usuario, 'ejemplar' => $ejemplar];
        // El original lee $u['estado'] aunque el usuario no exista: en PHP 5.3 era un aviso tolerado.
        set_error_handler(function () { return true; });
        ob_start();
        (new PrestamoController())->registrar();
        $salidaOriginal = ob_get_clean();
        restore_error_handler();
        $estadoOriginal = $this->estado($GLOBALS['__pdo']);

        $pdo = $this->preparar($previos);
        $servicio = new PrestamoService(new RepositorioCirculacionPdo($pdo));
        $salidaNueva = (new VistaCirculacion())->mensajePrestamo($servicio->registrar($usuario, $ejemplar));

        $this->assertSame($salidaOriginal, $salidaNueva);
        $this->assertSame($estadoOriginal, $this->estado($pdo));
    }

    /** La inyección SQL que evadía la validación en el original ya no funciona. */
    public function testInyeccionSqlYaNoEvadeLaValidacion(): void
    {
        $pdo = $this->preparar([]);
        $servicio = new PrestamoService(new RepositorioCirculacionPdo($pdo));
        $resultado = $servicio->registrar("zzz' OR codigo='U001", 'B001');

        $this->assertFalse($resultado->exito);
        $this->assertSame('Usuario inactivo', $resultado->mensaje);
        $this->assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM prestamos')->fetchColumn());
    }
}
