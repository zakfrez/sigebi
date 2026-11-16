<?php
namespace Sigebi\Multa;

/**
 * Extract Class: el cálculo de días vencidos vive en UN solo lugar.
 * Antes estaba copiado en DevolucionAction, RenovacionAction y public/index.php.
 */
final class DiasVencidos
{
    const SEGUNDOS_POR_DIA = 86400;

    /**
     * Días transcurridos desde la fecha de vencimiento hasta hoy.
     * Positivo = atrasado, 0 = vence hoy, negativo = aún no vence.
     */
    public static function entre(string $fechaVencimiento, string $hoy): int
    {
        return (int) round((strtotime($hoy) - strtotime($fechaVencimiento)) / self::SEGUNDOS_POR_DIA);
    }
}
