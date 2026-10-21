<?php
namespace Sigebi\Circulacion;

/** Acceso a datos del módulo de circulación (separado de la lógica de negocio y de la vista). */
interface RepositorioCirculacion
{
    public function buscarPrestamo(int $id): ?array;
    public function registrarDevolucion(int $id, string $fecha, int $multa): void;
    public function registrarRenovacion(int $id, string $nuevaFechaVencimiento): void;
    public function buscarUsuarioPorCodigo(string $codigo): ?array;
    public function contarPrestamosActivos(int $idUsuario): int;
    public function buscarEjemplar(string $codigoBarras): ?array;
    public function crearPrestamo(array $usuario, array $ejemplar, string $hoy, string $vence): void;
}
