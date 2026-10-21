<?php
use PHPUnit\Framework\TestCase;
use Sigebi\Multa\CalculadoraMulta;
use Sigebi\Multa\CondicionesMora;

/** Pruebas de la regla única de multas (con los 2 defectos del caso ya corregidos). */
final class CalculadoraMultaTest extends TestCase
{
    public static function casos(): array
    {
        return [
            '0 días, estudiante, general'            => [0, 1, 1, 0],
            '1 día, estudiante, general (era -500)'  => [1, 1, 1, 0],
            '5 días, estudiante, general'            => [5, 1, 1, 1500],
            '3 días, estudiante, reserva'            => [3, 1, 3, 4000],
            '3 días, docente, general (era -600)'    => [3, 2, 1, 0],
            '10 días, docente, general'              => [10, 2, 1, 1500],
            '2 días, externo, cualquiera'            => [2, 3, 1, 3000],
            'límite de gracia: estudiante general 2 días' => [2, 1, 1, 0],
            'primer día cobrado: estudiante general 3 días' => [3, 1, 1, 500],
            'docente reserva 4 días'                 => [4, 2, 3, 3000],
            'externo reserva 1 día'                  => [1, 3, 3, 1500],
        ];
    }

    /** @dataProvider casos */
    public function testCalculaLaMulta(int $dias, int $usuario, int $material, int $esperada): void
    {
        $this->assertSame($esperada, (new CalculadoraMulta())->calcular(new CondicionesMora($dias, $usuario, $material)));
    }

    public function testNuncaDevuelveMultaNegativa(): void
    {
        $calculadora = new CalculadoraMulta();
        for ($dias = -3; $dias < 60; $dias++) {
            foreach ([1, 2, 3] as $usuario) {
                foreach ([1, 3] as $material) {
                    $this->assertGreaterThanOrEqual(0, $calculadora->calcular(new CondicionesMora($dias, $usuario, $material)));
                }
            }
        }
    }
}
