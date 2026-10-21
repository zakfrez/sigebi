<?php
namespace Sigebi\Prestamos;

use Sigebi\Circulacion\RepositorioCirculacion;

/**
 * F3 refactorizado: registrar() se divide con Extract Method y se mueve (Move Method)
 * de PrestamoController a un servicio de negocio sin SQL ni HTML.
 */
final class PrestamoService
{
    const ESTADO_ACTIVO = 'A';
    const TIPO_DOCENTE = 2;
    const LIMITE_DOCENTE = 10;   // antes: número mágico dentro de un ternario
    const LIMITE_GENERAL = 5;
    const DIAS_PRESTAMO = 7;

    /** @var RepositorioCirculacion */
    private $repositorio;

    public function __construct(RepositorioCirculacion $repositorio)
    {
        $this->repositorio = $repositorio;
    }

    public function registrar(string $codigoUsuario, string $codigoBarras, ?string $hoy = null): ResultadoPrestamo
    {
        $hoy = $hoy ?? date('Y-m-d');

        $usuario = $this->repositorio->buscarUsuarioPorCodigo($codigoUsuario);
        if (!$this->estaActivo($usuario)) {
            return ResultadoPrestamo::rechazado('Usuario inactivo');
        }
        if (!$this->puedePedirPrestado($usuario)) {
            return ResultadoPrestamo::rechazado('Límite de préstamos alcanzado');
        }
        $ejemplar = $this->repositorio->buscarEjemplar($codigoBarras);
        if (!$this->estaDisponible($ejemplar)) {
            return ResultadoPrestamo::rechazado('El ejemplar no está disponible');
        }

        $vence = date('Y-m-d', strtotime('+' . self::DIAS_PRESTAMO . ' days', strtotime($hoy)));
        $this->repositorio->crearPrestamo($usuario, $ejemplar, $hoy, $vence);
        return ResultadoPrestamo::registrado($usuario['nombre'], $vence);
    }

    private function estaActivo(?array $usuario): bool
    {
        return $usuario !== null && strtoupper($usuario['estado']) === self::ESTADO_ACTIVO;
    }

    private function puedePedirPrestado(array $usuario): bool
    {
        return $this->repositorio->contarPrestamosActivos((int) $usuario['id']) < $this->limiteDePrestamos($usuario);
    }

    /** Replace Temp with Query: reemplaza la variable $limite del original. */
    private function limiteDePrestamos(array $usuario): int
    {
        return (int) $usuario['tipo'] === self::TIPO_DOCENTE ? self::LIMITE_DOCENTE : self::LIMITE_GENERAL;
    }

    private function estaDisponible(?array $ejemplar): bool
    {
        return $ejemplar !== null && (int) $ejemplar['disponible'] === 1;
    }
}
