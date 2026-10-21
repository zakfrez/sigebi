<?php
namespace Sigebi\Multa;

/**
 * Extract Class + Move Method: UNICA fuente de verdad de la regla de multas.
 * Reemplaza las copias de F1 (devolver) y F2 (renovar).
 */
final class CalculadoraMulta
{
    const USUARIO_ESTUDIANTE = 1;
    const USUARIO_DOCENTE = 2;
    const MATERIAL_RESERVA = 3;

    const TARIFA_EXTERNO = 1500;

    public function calcular(CondicionesMora $c): int
    {
        if ($c->diasVencidos <= 0) {
            return 0;
        }
        list($diasDeGracia, $tarifaPorDia) = $this->reglaPara($c->tipoUsuario, $c->tipoMaterial);
        return ($c->diasVencidos - $diasDeGracia) * $tarifaPorDia;
    }

    /** @return int[] [días de gracia, tarifa por día] */
    private function reglaPara(int $tipoUsuario, int $tipoMaterial): array
    {
        $esReserva = $tipoMaterial === self::MATERIAL_RESERVA;
        if ($tipoUsuario === self::USUARIO_ESTUDIANTE) {
            return $esReserva ? [1, 2000] : [2, 500];
        }
        if ($tipoUsuario === self::USUARIO_DOCENTE) {
            return $esReserva ? [1, 1000] : [5, 300];
        }
        return [0, self::TARIFA_EXTERNO];
    }
}
