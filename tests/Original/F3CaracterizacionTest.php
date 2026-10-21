<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../original/PrestamoController.php';

/** Caracterización de F3 (registrar): flujos del préstamo y evidencia de la inyección SQL. */
final class F3CaracterizacionTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['__pdo'] = TestDb::crear();
        TestDb::sembrarCatalogo($GLOBALS['__pdo']);
    }

    private function registrar(string $usuario, string $ejemplar): string
    {
        $_POST = ['codigo_usuario' => $usuario, 'ejemplar' => $ejemplar];
        ob_start();
        (new PrestamoController())->registrar();
        return ob_get_clean();
    }

    public function testRegistraPrestamoValido(): void
    {
        $venc = date('Y-m-d', strtotime('+7 days'));
        $this->assertSame("<div class='ok'>Préstamo registrado para Ana Pérez. Vence el $venc</div>", $this->registrar('U001', 'B001'));
        $this->assertSame('0', (string) $GLOBALS['__pdo']->query("SELECT disponible FROM ejemplares WHERE id=1")->fetchColumn());
    }

    public function testUsuarioInactivo(): void
    {
        $this->assertSame("<div class='error'>Usuario inactivo</div>", $this->registrar('U002', 'B001'));
    }

    public function testAceptaEstadoEnMinuscula(): void
    {
        $this->assertStringContainsString('Préstamo registrado para Marta Ruiz', $this->registrar('D001', 'B001'));
    }

    public function testEjemplarNoDisponible(): void
    {
        $this->assertSame("<div class='error'>El ejemplar no está disponible</div>", $this->registrar('U001', 'B002'));
    }

    public function testEstudianteConCincoPrestamosAlcanzaElLimite(): void
    {
        TestDb::prestamosActivos($GLOBALS['__pdo'], 1, 5);
        $this->assertSame("<div class='error'>Límite de préstamos alcanzado</div>", $this->registrar('U001', 'B001'));
    }

    public function testDocenteConNueveAunPuedePrestar(): void
    {
        TestDb::prestamosActivos($GLOBALS['__pdo'], 3, 9);
        $this->assertStringContainsString('Préstamo registrado', $this->registrar('D001', 'B001'));
    }

    public function testDocenteConDiezAlcanzaElLimite(): void
    {
        TestDb::prestamosActivos($GLOBALS['__pdo'], 3, 10);
        $this->assertStringContainsString('Límite de préstamos alcanzado', $this->registrar('D001', 'B001'));
    }

    /** VULNERABILIDAD: un código inventado con SQL se trata como si fuera el usuario U001. */
    public function testInyeccionSqlEvadeLaValidacionDelUsuario(): void
    {
        $salida = $this->registrar("zzz' OR codigo='U001", 'B001');
        $this->assertStringContainsString('Préstamo registrado para Ana Pérez', $salida);
    }
}
