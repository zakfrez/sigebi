<?php
/**
 * Consultas de solo lectura para las pantallas (listados). Están aparte de
 * RepositorioCirculacion para no modificar el código refactorizado de las Actividades 3 y 4.
 */
final class ConsultasPanel
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function prestamosActivos(): array
    {
        return $this->pdo->query("SELECT p.*, u.codigo AS cod_usuario, u.nombre, e.codigo_barras, e.titulo
            FROM prestamos p JOIN usuarios u ON u.id = p.id_usr JOIN ejemplares e ON e.id = p.id_ejem
            WHERE p.fecha_dev IS NULL ORDER BY p.fecha_venc")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function historial(): array
    {
        return $this->pdo->query("SELECT p.*, u.nombre, e.titulo FROM prestamos p
            JOIN usuarios u ON u.id = p.id_usr JOIN ejemplares e ON e.id = p.id_ejem
            WHERE p.fecha_dev IS NOT NULL ORDER BY p.id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function usuarios(): array
    {
        return $this->pdo->query("SELECT u.*, (SELECT COUNT(*) FROM prestamos p WHERE p.id_usr = u.id AND p.fecha_dev IS NULL) AS activos
            FROM usuarios u ORDER BY u.codigo")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ejemplares(): array
    {
        return $this->pdo->query("SELECT * FROM ejemplares ORDER BY codigo_barras")->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Al devolver, el ejemplar vuelve a estar disponible. */
    public function liberarEjemplarDelPrestamo(int $idPrestamo): void
    {
        $this->pdo->prepare('UPDATE ejemplares SET disponible = 1
            WHERE id = (SELECT id_ejem FROM prestamos WHERE id = ?)')->execute([$idPrestamo]);
    }
}
