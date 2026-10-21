<?php
// Reemplazo mínimo de las funciones mysql_* usando el PDO de $GLOBALS['__pdo'].
if (!class_exists('FakeResult')) {
    final class FakeResult
    {
        public $filas;
        public $pos = 0;
        public function __construct(array $filas) { $this->filas = $filas; }
    }
}
if (!function_exists('mysql_query')) {
    function mysql_query($sql)
    {
        $GLOBALS['__consultas'][] = $sql;
        $st = $GLOBALS['__pdo']->query($sql);
        if (stripos(ltrim($sql), 'SELECT') === 0) {
            return new FakeResult($st->fetchAll(PDO::FETCH_BOTH));
        }
        return true;
    }
    function mysql_fetch_array($res)
    {
        if (!$res instanceof FakeResult) { return false; }
        return $res->filas[$res->pos++] ?? false;
    }
    function mysql_num_rows($res)
    {
        return count($res->filas);
    }
}
