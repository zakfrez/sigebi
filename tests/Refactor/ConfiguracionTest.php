<?php
use PHPUnit\Framework\TestCase;
use Sigebi\Config\Configuracion;

/** La nueva clase entrega exactamente los mismos valores que las variables del original (F4). */
final class ConfiguracionTest extends TestCase
{
    public function testEntregaLosMismosValoresQueElOriginal(): void
    {
        $GLOBALS['__pdo'] = $pdo = TestDb::crear();
        $pdo->exec("INSERT INTO config_general VALUES (1, 7, 14, 500, 2, 'biblioteca@ucundinamarca.edu.co', 1)");

        $original = (function () {
            include __DIR__ . '/../../original/Parametros.php';
            return compact('dias_prestamo_est', 'dias_prestamo_doc', 'tarifa_base', 'max_renov', 'correo_remitente');
        })();
        $config = Configuracion::cargar($pdo);

        $this->assertEquals($original['dias_prestamo_est'], $config->diasPrestamoEstudiante());
        $this->assertEquals($original['dias_prestamo_doc'], $config->diasPrestamoDocente());
        $this->assertEquals($original['tarifa_base'], $config->tarifaBase());
        $this->assertEquals($original['max_renov'], $config->maximoRenovaciones());
        $this->assertSame($original['correo_remitente'], $config->correoRemitente());
        $this->assertSame(1, $config->datoDesconocido52());
    }
}
