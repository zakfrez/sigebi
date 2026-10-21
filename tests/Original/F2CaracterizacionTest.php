<?php
use PHPUnit\Framework\TestCase;

/** Caracterización de F2 (renovar): se ejecuta en un proceso aparte porque el original llama a exit. */
final class F2CaracterizacionTest extends TestCase
{
    private function ejecutar(int $dias, int $usr, int $mat, int $renov): array
    {
        $json = shell_exec(PHP_BINARY . ' ' . escapeshellarg(__DIR__ . '/run_renovar.php') . ' ' .
            escapeshellarg(json_encode(['dias' => $dias, 'usr' => $usr, 'mat' => $mat, 'renov' => $renov])));
        return json_decode($json, true);
    }

    public function testEstudianteReservaTresDiasCobraTarifaVieja(): void
    {
        $r = $this->ejecutar(3, 1, 3, 0);
        // F1 cobra 4.000 para el mismo caso: la copia de F2 conserva la tarifa anterior a 2025 (1.500)
        $this->assertContains('Location: pagar_multa.php?valor=3000', $r['headers']);
    }

    public function testSinMoraRenueva(): void
    {
        $r = $this->ejecutar(0, 1, 1, 0);
        $this->assertStringStartsWith("<div class='ok'>Renovado hasta ", $r['salida']);
        $this->assertSame(1, $r['renovaciones']);
    }

    public function testConDosRenovacionesNoPermiteMas(): void
    {
        $r = $this->ejecutar(0, 1, 1, 2);
        $this->assertSame("<div class='error'>No se permiten más renovaciones</div>", $r['salida']);
        $this->assertSame(2, $r['renovaciones']);
    }
}
