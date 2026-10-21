<?php
use PHPUnit\Framework\TestCase;
use Sigebi\Circulacion\RenovacionAction;
use Sigebi\Circulacion\RepositorioCirculacionPdo;
use Sigebi\Circulacion\ResultadoRenovacion;
use Sigebi\Circulacion\VistaCirculacion;
use Sigebi\Multa\CalculadoraMulta;

final class RenovacionActionTest extends TestCase
{
    private function accion(PDO $pdo): RenovacionAction
    {
        return new RenovacionAction(new RepositorioCirculacionPdo($pdo), new CalculadoraMulta());
    }

    /** F1 y F2 ahora coinciden: antes devolver() cobraba 4.000 y renovar() cobraba 3.000. */
    public function testRenovarYDevolverCobranLoMismo(): void
    {
        $pdo = TestDb::crear();
        $id = TestDb::prestamoVencido($pdo, 3, 1, 3);
        $r = $this->accion($pdo)->renovar($id);
        $this->assertSame(ResultadoRenovacion::MULTA_PENDIENTE, $r->tipo);
        $this->assertSame(4000, $r->valorMulta);
        $this->assertSame('pagar_multa.php?valor=4000', (new VistaCirculacion())->urlPagoMulta($r->valorMulta));
    }

    public function testSinMoraRenuevaSieteDias(): void
    {
        $pdo = TestDb::crear();
        $id = TestDb::prestamoVencido($pdo, 0, 1, 1);
        $r = $this->accion($pdo)->renovar($id);
        $esperada = date('Y-m-d', strtotime('+7 days'));
        $this->assertSame(ResultadoRenovacion::RENOVADA, $r->tipo);
        $this->assertSame("<div class='ok'>Renovado hasta $esperada</div>", (new VistaCirculacion())->mensajeRenovacion($r));
        $fila = $pdo->query("SELECT renovaciones, fecha_venc FROM prestamos WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(1, (int) $fila['renovaciones']);
        $this->assertSame($esperada, $fila['fecha_venc']);
    }

    public function testConDosRenovacionesNoPermiteMas(): void
    {
        $pdo = TestDb::crear();
        $id = TestDb::prestamoVencido($pdo, 0, 1, 1, 2);
        $r = $this->accion($pdo)->renovar($id);
        $this->assertSame("<div class='error'>No se permiten más renovaciones</div>", (new VistaCirculacion())->mensajeRenovacion($r));
        $this->assertSame(2, (int) $pdo->query("SELECT renovaciones FROM prestamos WHERE id=$id")->fetchColumn());
    }
}
