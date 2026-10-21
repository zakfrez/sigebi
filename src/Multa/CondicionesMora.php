<?php
namespace Sigebi\Multa;

/**
 * Introduce Parameter Object: agrupa los tres datos que siempre viajan juntos
 * para calcular una multa (antes eran $dias, $p['tipo_usr'] y $p['tipo_mat']).
 */
final class CondicionesMora
{
    /** @var int */
    public $diasVencidos;
    /** @var int */
    public $tipoUsuario;
    /** @var int */
    public $tipoMaterial;

    public function __construct(int $diasVencidos, int $tipoUsuario, int $tipoMaterial)
    {
        $this->diasVencidos = $diasVencidos;
        $this->tipoUsuario = $tipoUsuario;
        $this->tipoMaterial = $tipoMaterial;
    }
}
