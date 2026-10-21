<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../original/DevolucionAction.php';

/**
 * Pruebas de CARACTERIZACIÓN de F1 (código original): fijan lo que el código hace HOY,
 * incluidos sus dos defectos (multa negativa). Son las 7 filas de la tabla del caso.
 */
final class F1CaracterizacionTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['__pdo'] = TestDb::crear();
    }

    public static function casos(): array
    {
        return [
            '0 días, estudiante, general'    => [0, 1, 1, 0],
            '1 día, estudiante, general (DEFECTO: negativa)' => [1, 1, 1, -500],
            '5 días, estudiante, general'    => [5, 1, 1, 1500],
            '3 días, estudiante, reserva'    => [3, 1, 3, 4000],
            '3 días, docente, general (DEFECTO: negativa)'   => [3, 2, 1, -600],
            '10 días, docente, general'      => [10, 2, 1, 1500],
            '2 días, externo, cualquiera'    => [2, 3, 1, 3000],
        ];
    }

    /** @dataProvider casos */
    public function testMultaQueCalculaHoy(int $dias, int $usr, int $mat, int $esperada): void
    {
        $pdo = $GLOBALS['__pdo'];
        $id = TestDb::prestamoVencido($pdo, $dias, $usr, $mat);

        ob_start();
        devolver($id);
        $salida = ob_get_clean();

        $guardada = (int) $pdo->query("SELECT multa FROM prestamos WHERE id=$id")->fetchColumn();
        $this->assertSame($esperada, $guardada);
        $mensaje = $esperada > 0 ? "<div class='error'>Multa generada: $$esperada</div>" : '';
        $this->assertSame($mensaje, $salida);
    }
}
