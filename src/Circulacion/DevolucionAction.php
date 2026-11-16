<?php
namespace Sigebi\Circulacion;

use Sigebi\Multa\CalculadoraMulta;
use Sigebi\Multa\CondicionesMora;
use Sigebi\Multa\DiasVencidos;

/** F1 refactorizado: devolver() ya no mezcla negocio, SQL y HTML. */
final class DevolucionAction
{
    /** @var RepositorioCirculacion */
    private $repositorio;
    /** @var CalculadoraMulta */
    private $calculadora;

    public function __construct(RepositorioCirculacion $repositorio, CalculadoraMulta $calculadora)
    {
        $this->repositorio = $repositorio;
        $this->calculadora = $calculadora;
    }

    /** @return int multa generada */
    public function devolver(int $idPrestamo, ?string $hoy = null): int
    {
        $hoy = $hoy ?? date('Y-m-d');
        $prestamo = $this->repositorio->buscarPrestamo($idPrestamo);
        $multa = $this->calculadora->calcular($this->condicionesDe($prestamo, $hoy));
        $this->repositorio->registrarDevolucion($idPrestamo, $hoy, $multa);
        return $multa;
    }

    /** Extract Method + Replace Temp with Query: los días vencidos se consultan, no se guardan en una variable. */
    private function condicionesDe(array $prestamo, string $hoy): CondicionesMora
    {
        return new CondicionesMora(
            DiasVencidos::entre($prestamo['fecha_venc'], $hoy),
            (int) $prestamo['tipo_usr'],
            (int) $prestamo['tipo_mat']
        );
    }
}
