<?php
namespace Sigebi\Config;

use PDO;

/**
 * F4 refactorizado (Encapsulate Field + Rename): las columnas dato3, dato4... dejan de usarse
 * directamente; cada parámetro tiene un nombre que dice qué es. El mapa dato -> significado
 * queda en UN solo lugar (el diccionario reconstruido por ingeniería inversa).
 */
final class Configuracion
{
    /** @var array */
    private $fila;

    private function __construct(array $fila)
    {
        $this->fila = $fila;
    }

    public static function desdeFila(array $fila): self
    {
        return new self($fila);
    }

    public static function cargar(PDO $pdo): self
    {
        $st = $pdo->prepare('SELECT * FROM config_general WHERE id = ?');
        $st->execute([1]);
        return new self($st->fetch(PDO::FETCH_ASSOC) ?: []);
    }

    public function diasPrestamoEstudiante(): int { return (int) $this->fila['dato3']; }
    public function diasPrestamoDocente(): int { return (int) $this->fila['dato4']; }
    public function tarifaBase(): int { return (int) $this->fila['dato17']; }
    public function maximoRenovaciones(): int { return (int) $this->fila['dato21']; }
    public function correoRemitente(): string { return (string) $this->fila['dato35']; }

    /**
     * dato52: significado DESCONOCIDO (cambia el comportamiento del módulo de avisos).
     * Se expone con un nombre honesto hasta que el equipo lo verifique con el negocio.
     */
    public function datoDesconocido52(): int { return (int) $this->fila['dato52']; }
}
