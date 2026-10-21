<?php
namespace Sigebi\Circulacion;

use Sigebi\Prestamos\ResultadoPrestamo;

/** Move Method: el HTML sale de la lógica de negocio y vive solo aquí. */
final class VistaCirculacion
{
    public function mensajeDevolucion(int $multa): string
    {
        return $multa > 0 ? "<div class='error'>Multa generada: $" . $multa . "</div>" : '';
    }

    public function mensajeRenovacion(ResultadoRenovacion $r): string
    {
        if ($r->tipo === ResultadoRenovacion::LIMITE) {
            return "<div class='error'>No se permiten más renovaciones</div>";
        }
        if ($r->tipo === ResultadoRenovacion::RENOVADA) {
            return "<div class='ok'>Renovado hasta " . $r->nuevaFecha . "</div>";
        }
        return '';
    }

    public function urlPagoMulta(int $valor): string
    {
        return 'pagar_multa.php?valor=' . $valor;
    }

    public function mensajePrestamo(ResultadoPrestamo $r): string
    {
        return "<div class='" . ($r->exito ? 'ok' : 'error') . "'>" . $r->mensaje . "</div>";
    }
}
