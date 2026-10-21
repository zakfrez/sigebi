<?php
use PHPUnit\Framework\TestCase;

/** Caracterización de F4 (Parametros.php): qué variables arma hoy a partir de las columnas datoN. */
final class F4CaracterizacionTest extends TestCase
{
    public function testLeeLasColumnasSinNombre(): void
    {
        $GLOBALS['__pdo'] = TestDb::crear();
        $GLOBALS['__pdo']->exec("INSERT INTO config_general VALUES (1, 7, 14, 500, 2, 'biblioteca@ucundinamarca.edu.co', 0)");
        $v = (function () {
            include __DIR__ . '/../../original/Parametros.php';
            return compact('dias_prestamo_est', 'dias_prestamo_doc', 'tarifa_base', 'max_renov', 'correo_remitente');
        })();
        $this->assertEquals(7, $v['dias_prestamo_est']);
        $this->assertEquals(14, $v['dias_prestamo_doc']);
        $this->assertEquals(500, $v['tarifa_base']);
        $this->assertEquals(2, $v['max_renov']);
        $this->assertSame('biblioteca@ucundinamarca.edu.co', $v['correo_remitente']);
    }
}
