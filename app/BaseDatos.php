<?php
/**
 * Base de datos de la aplicación (SQLite en un archivo, sin instalar MySQL).
 * Usa el mismo esquema que el caso: usuarios, ejemplares, prestamos y config_general.
 */
final class BaseDatos
{
    const TIPO_ESTUDIANTE = 1;
    const TIPO_DOCENTE = 2;
    const TIPO_EXTERNO = 3;
    const MATERIAL_GENERAL = 1;
    const MATERIAL_RESERVA = 3;

    public static function conectar(string $archivo): PDO
    {
        $nueva = !is_file($archivo);
        $pdo = new PDO('sqlite:' . $archivo);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if ($nueva) {
            self::crearEsquema($pdo);
            self::sembrar($pdo);
        }
        return $pdo;
    }

    /** Borra todo y vuelve a cargar los datos de demostración. */
    public static function reiniciar(PDO $pdo): void
    {
        foreach (['prestamos', 'ejemplares', 'usuarios', 'config_general'] as $tabla) {
            $pdo->exec("DROP TABLE IF EXISTS $tabla");
        }
        self::crearEsquema($pdo);
        self::sembrar($pdo);
    }

    private static function crearEsquema(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE usuarios (id INTEGER PRIMARY KEY, codigo TEXT UNIQUE, nombre TEXT, estado TEXT, tipo INTEGER)");
        $pdo->exec("CREATE TABLE ejemplares (id INTEGER PRIMARY KEY, codigo_barras TEXT UNIQUE, titulo TEXT, disponible INTEGER, tipo INTEGER)");
        $pdo->exec("CREATE TABLE prestamos (id INTEGER PRIMARY KEY AUTOINCREMENT, id_usr INTEGER, id_ejem INTEGER,
            fecha_pres TEXT, fecha_venc TEXT, fecha_dev TEXT, multa INTEGER DEFAULT 0, tipo_usr INTEGER, tipo_mat INTEGER,
            renovaciones INTEGER DEFAULT 0)");
        $pdo->exec("CREATE TABLE config_general (id INTEGER PRIMARY KEY, dato3 INTEGER, dato4 INTEGER, dato17 INTEGER,
            dato21 INTEGER, dato35 TEXT, dato52 INTEGER)");
    }

    private static function sembrar(PDO $pdo): void
    {
        $usuarios = [
            ['U001', 'Ana Pérez', 'A', self::TIPO_ESTUDIANTE],
            ['U002', 'Luis Gómez', 'I', self::TIPO_ESTUDIANTE],
            ['U003', 'Sofía Torres', 'A', self::TIPO_ESTUDIANTE],
            ['D001', 'Marta Ruiz', 'a', self::TIPO_DOCENTE],
            ['D002', 'Jorge Castillo', 'A', self::TIPO_DOCENTE],
            ['E001', 'Carlos Díaz', 'A', self::TIPO_EXTERNO],
        ];
        $st = $pdo->prepare('INSERT INTO usuarios (codigo, nombre, estado, tipo) VALUES (?, ?, ?, ?)');
        foreach ($usuarios as $u) { $st->execute($u); }

        $ejemplares = [
            ['B001', 'Cien años de soledad', self::MATERIAL_GENERAL],
            ['B002', 'Fundamentos de bases de datos', self::MATERIAL_GENERAL],
            ['B003', 'Cálculo de una variable (reserva)', self::MATERIAL_RESERVA],
            ['B004', 'Refactoring, 2.ª ed.', self::MATERIAL_GENERAL],
            ['B005', 'Física universitaria (reserva)', self::MATERIAL_RESERVA],
            ['B006', 'Clean Code', self::MATERIAL_GENERAL],
            ['B007', 'Estructuras de datos en Java', self::MATERIAL_GENERAL],
            ['B008', 'Álgebra lineal (reserva)', self::MATERIAL_RESERVA],
        ];
        $st = $pdo->prepare('INSERT INTO ejemplares (codigo_barras, titulo, disponible, tipo) VALUES (?, ?, 1, ?)');
        foreach ($ejemplares as $e) { $st->execute($e); }

        // config_general con columnas datoN, como en el caso (F4). Configuracion les da nombre.
        $pdo->exec("INSERT INTO config_general VALUES (1, 7, 14, 500, 2, 'biblioteca@villaverde.edu.co', 0)");

        // Préstamos de ejemplo: uno al día, dos vencidos (para ver multas) y uno con 2 renovaciones.
        self::prestamo($pdo, 'U003', 'B002', -3, 4, 0);   // vence en 4 días
        self::prestamo($pdo, 'U001', 'B005', -10, -3, 0); // estudiante, reserva, 3 días vencido -> $4.000
        self::prestamo($pdo, 'E001', 'B006', -9, -2, 0);  // externo, 2 días vencido -> $3.000
        self::prestamo($pdo, 'D002', 'B007', -20, 2, 2);  // docente al día, ya renovó 2 veces
    }

    private static function prestamo(PDO $pdo, string $codUsuario, string $codEjemplar, int $diasPrestamo, int $diasVence, int $renov): void
    {
        $u = $pdo->query("SELECT * FROM usuarios WHERE codigo = " . $pdo->quote($codUsuario))->fetch(PDO::FETCH_ASSOC);
        $e = $pdo->query("SELECT * FROM ejemplares WHERE codigo_barras = " . $pdo->quote($codEjemplar))->fetch(PDO::FETCH_ASSOC);
        $st = $pdo->prepare('INSERT INTO prestamos (id_usr, id_ejem, fecha_pres, fecha_venc, tipo_usr, tipo_mat, renovaciones)
                             VALUES (?, ?, ?, ?, ?, ?, ?)');
        $st->execute([$u['id'], $e['id'], date('Y-m-d', strtotime("$diasPrestamo days")),
            date('Y-m-d', strtotime("$diasVence days")), $u['tipo'], $e['tipo'], $renov]);
        $pdo->prepare('UPDATE ejemplares SET disponible = 0 WHERE id = ?')->execute([$e['id']]);
    }
}
