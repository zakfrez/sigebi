<?php
use PHPUnit\Framework\TestCase;
use Sigebi\Multa\DiasVencidos;

/** Pruebas del cálculo de días vencidos, ahora aislado en una sola clase. */
final class DiasVencidosTest extends TestCase
{
    public function testPrestamoAtrasadoDaDiasPositivos(): void
    {
        $this->assertSame(3, DiasVencidos::entre('2026-11-13', '2026-11-16'));
    }

    public function testSiVenceHoyNoHayDiasVencidos(): void
    {
        $this->assertSame(0, DiasVencidos::entre('2026-11-16', '2026-11-16'));
    }

    public function testSiAunNoVenceDaDiasNegativos(): void
    {
        $this->assertSame(-5, DiasVencidos::entre('2026-11-21', '2026-11-16'));
    }

    public function testCuentaBienAlCambiarDeMes(): void
    {
        $this->assertSame(2, DiasVencidos::entre('2026-10-31', '2026-11-02'));
    }

    public function testCuentaBienAlCambiarDeAnio(): void
    {
        $this->assertSame(1, DiasVencidos::entre('2026-12-31', '2027-01-01'));
    }

    public function testCuentaElDia29DeFebreroEnAnioBisiesto(): void
    {
        $this->assertSame(2, DiasVencidos::entre('2028-02-28', '2028-03-01'));
    }

    public function testDaLoMismoQueLaFormulaQueEstabaCopiada(): void
    {
        $hoy = '2026-11-16';
        for ($d = -30; $d <= 60; $d++) {
            $venc = date('Y-m-d', strtotime("-$d days", strtotime($hoy)));
            $formulaAnterior = (int) round((strtotime($hoy) - strtotime($venc)) / 86400);
            $this->assertSame($formulaAnterior, DiasVencidos::entre($venc, $hoy));
        }
    }
}
