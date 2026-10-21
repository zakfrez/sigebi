<?php
namespace Sigebi\Prestamos;

final class ResultadoPrestamo
{
    /** @var bool */
    public $exito;
    /** @var string */
    public $mensaje;

    private function __construct(bool $exito, string $mensaje)
    {
        $this->exito = $exito;
        $this->mensaje = $mensaje;
    }

    public static function registrado(string $nombre, string $vence): self
    {
        return new self(true, 'Préstamo registrado para ' . $nombre . '. Vence el ' . $vence);
    }

    public static function rechazado(string $motivo): self
    {
        return new self(false, $motivo);
    }
}
