<?php
namespace Sigebi\Circulacion;

use PDO;

/** Implementación con consultas PREPARADAS: los datos del usuario nunca se concatenan al SQL. */
final class RepositorioCirculacionPdo implements RepositorioCirculacion
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function buscarPrestamo(int $id): ?array
    {
        return $this->uno('SELECT * FROM prestamos WHERE id = ?', [$id]);
    }

    public function registrarDevolucion(int $id, string $fecha, int $multa): void
    {
        $this->ejecutar('UPDATE prestamos SET fecha_dev = ?, multa = ? WHERE id = ?', [$fecha, $multa, $id]);
    }

    public function registrarRenovacion(int $id, string $nuevaFechaVencimiento): void
    {
        $this->ejecutar('UPDATE prestamos SET fecha_venc = ?, renovaciones = renovaciones + 1 WHERE id = ?',
            [$nuevaFechaVencimiento, $id]);
    }

    public function buscarUsuarioPorCodigo(string $codigo): ?array
    {
        return $this->uno('SELECT * FROM usuarios WHERE codigo = ?', [$codigo]);
    }

    public function contarPrestamosActivos(int $idUsuario): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM prestamos WHERE id_usr = ? AND fecha_dev IS NULL');
        $st->execute([$idUsuario]);
        return (int) $st->fetchColumn();
    }

    public function buscarEjemplar(string $codigoBarras): ?array
    {
        return $this->uno('SELECT * FROM ejemplares WHERE codigo_barras = ?', [$codigoBarras]);
    }

    public function crearPrestamo(array $usuario, array $ejemplar, string $hoy, string $vence): void
    {
        $this->ejecutar('INSERT INTO prestamos (id_usr, id_ejem, fecha_pres, fecha_venc, tipo_usr, tipo_mat)
                         VALUES (?, ?, ?, ?, ?, ?)',
            [$usuario['id'], $ejemplar['id'], $hoy, $vence, $usuario['tipo'], $ejemplar['tipo']]);
        $this->ejecutar('UPDATE ejemplares SET disponible = 0 WHERE id = ?', [$ejemplar['id']]);
    }

    private function uno(string $sql, array $parametros): ?array
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($parametros);
        $fila = $st->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    private function ejecutar(string $sql, array $parametros): void
    {
        $this->pdo->prepare($sql)->execute($parametros);
    }
}
