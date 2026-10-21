<?php
namespace Sigebi\Circulacion;

use Sigebi\Config\Configuracion;
use Sigebi\Multa\CalculadoraMulta;
use Sigebi\Multa\CondicionesMora;

/** F2 refactorizado: ya no copia la regla de multas; delega en CalculadoraMulta (Move Method). */
final class RenovacionAction
{
    const DIAS_RENOVACION = 7;

    /** @var RepositorioCirculacion */
    private $repositorio;
    /** @var CalculadoraMulta */
    private $calculadora;
    /** @var Configuracion */
    private $configuracion;

    public function __construct(RepositorioCirculacion $repositorio, CalculadoraMulta $calculadora, Configuracion $configuracion)
    {
        $this->repositorio = $repositorio;
        $this->calculadora = $calculadora;
        $this->configuracion = $configuracion;
    }

    public function renovar(int $idPrestamo, ?string $hoy = null): ResultadoRenovacion
    {
        $hoy = $hoy ?? date('Y-m-d');
        $prestamo = $this->repositorio->buscarPrestamo($idPrestamo);

        $multa = $this->calculadora->calcular(new CondicionesMora(
            (int) round((strtotime($hoy) - strtotime($prestamo['fecha_venc'])) / 86400),
            (int) $prestamo['tipo_usr'],
            (int) $prestamo['tipo_mat']
        ));
        if ($multa > 0) {
            return ResultadoRenovacion::multaPendiente($multa);
        }
        if ($prestamo['renovaciones'] >= $this->maximoDeRenovaciones()) {
            return ResultadoRenovacion::limiteAlcanzado();
        }
        $nueva = date('Y-m-d', strtotime('+' . self::DIAS_RENOVACION . ' days', strtotime($hoy)));
        $this->repositorio->registrarRenovacion($idPrestamo, $nueva);
        return ResultadoRenovacion::renovada($nueva);
    }

    private function maximoDeRenovaciones(): int
    {
        // Antes: el 2 estaba escrito en el código aunque ya existía en config_general (dato21).
        return $this->configuracion->maximoRenovaciones();
    }
}
