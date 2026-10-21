<?php
final class TestDb
{
    public static function crear(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE TABLE usuarios (id INTEGER PRIMARY KEY, codigo TEXT, nombre TEXT, estado TEXT, tipo INTEGER)");
        $pdo->exec("CREATE TABLE ejemplares (id INTEGER PRIMARY KEY, codigo_barras TEXT, disponible INTEGER, tipo INTEGER)");
        $pdo->exec("CREATE TABLE prestamos (id INTEGER PRIMARY KEY AUTOINCREMENT, id_usr INTEGER, id_ejem INTEGER,
            fecha_pres TEXT, fecha_venc TEXT, fecha_dev TEXT, multa INTEGER DEFAULT 0, tipo_usr INTEGER, tipo_mat INTEGER,
            renovaciones INTEGER DEFAULT 0)");
        $pdo->exec("CREATE TABLE config_general (id INTEGER PRIMARY KEY, dato3 INTEGER, dato4 INTEGER, dato17 INTEGER,
            dato21 INTEGER, dato35 TEXT, dato52 INTEGER)");
        return $pdo;
    }

    /** Inserta un préstamo vencido hace $dias días y devuelve su id. */
    public static function prestamoVencido(PDO $pdo, int $dias, int $tipoUsr, int $tipoMat, int $renovaciones = 0): int
    {
        $venc = date('Y-m-d', strtotime("-$dias days"));
        $st = $pdo->prepare("INSERT INTO prestamos (id_usr, id_ejem, fecha_pres, fecha_venc, tipo_usr, tipo_mat, renovaciones)
                             VALUES (1, 1, '2026-01-01', ?, ?, ?, ?)");
        $st->execute([$venc, $tipoUsr, $tipoMat, $renovaciones]);
        return (int) $pdo->lastInsertId();
    }

    /** Datos base del caso de préstamos (usuarios y ejemplares). */
    public static function sembrarCatalogo(PDO $pdo): void
    {
        $pdo->exec("INSERT INTO usuarios VALUES (1,'U001','Ana Pérez','A',1), (2,'U002','Luis Gómez','I',1), (3,'D001','Marta Ruiz','a',2)");
        $pdo->exec("INSERT INTO ejemplares VALUES (1,'B001',1,1), (2,'B002',0,1), (3,'B003',1,3)");
    }

    public static function prestamosActivos(PDO $pdo, int $idUsr, int $cuantos): void
    {
        for ($i = 0; $i < $cuantos; $i++) {
            $pdo->exec("INSERT INTO prestamos (id_usr, id_ejem, fecha_pres, fecha_venc, tipo_usr, tipo_mat) VALUES ($idUsr, 1, '2026-01-01', '2026-12-31', 1, 1)");
        }
    }
}
