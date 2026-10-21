<?php
namespace Sigebi\Circulacion;

final class ResultadoRenovacion
{
    const MULTA_PENDIENTE = 'multa';
    const LIMITE = 'limite';
    const RENOVADA = 'renovada';

    /** @var string */
    public $tipo;
    /** @var int */
    public $valorMulta = 0;
    /** @var string */
    public $nuevaFecha = '';

    private function __construct(string $tipo)
    {
        $this->tipo = $tipo;
    }

    public static function multaPendiente(int $valor): self
    {
        $r = new self(self::MULTA_PENDIENTE);
        $r->valorMulta = $valor;
        return $r;
    }

    public static function limiteAlcanzado(): self
    {
        return new self(self::LIMITE);
    }

    public static function renovada(string $nuevaFecha): self
    {
        $r = new self(self::RENOVADA);
        $r->nuevaFecha = $nuevaFecha;
        return $r;
    }
}
