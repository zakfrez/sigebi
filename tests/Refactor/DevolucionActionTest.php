<?php
use PHPUnit\Framework\TestCase;
use Sigebi\Circulacion\DevolucionAction;
use Sigebi\Circulacion\RepositorioCirculacionPdo;
use Sigebi\Circulacion\VistaCirculacion;
use Sigebi\Multa\CalculadoraMulta;

final class DevolucionActionTest extends TestCase
{
    public function testRegistraLaDevolucionConLaMultaCorrecta(): void
    {
        $pdo = TestDb::crear();
        $id = TestDb::prestamoVencido($pdo, 3, 1, 3);
        $multa = (new DevolucionAction(new RepositorioCirculacionPdo($pdo), new CalculadoraMulta()))->devolver($id);

        $this->assertSame(4000, $multa);
        $fila = $pdo->query("SELECT multa, fecha_dev FROM prestamos WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(4000, (int) $fila['multa']);
        $this->assertSame(date('Y-m-d'), $fila['fecha_dev']);
        $this->assertSame("<div class='error'>Multa generada: $4000</div>", (new VistaCirculacion())->mensajeDevolucion($multa));
    }

    public function testDevolucionATiempoNoGeneraMulta(): void
    {
        $pdo = TestDb::crear();
        $id = TestDb::prestamoVencido($pdo, 0, 1, 1);
        $multa = (new DevolucionAction(new RepositorioCirculacionPdo($pdo), new CalculadoraMulta()))->devolver($id);
        $this->assertSame(0, $multa);
        $this->assertSame('', (new VistaCirculacion())->mensajeDevolucion($multa));
    }
}
