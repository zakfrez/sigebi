<?php
// F2 - circulacion/RenovacionAction.php, metodo renovar() (codigo ORIGINAL, sin modificar)
function renovar($id_prestamo) {
    $r = mysql_query("SELECT * FROM prestamos WHERE id = " . $id_prestamo);
    $p = mysql_fetch_array($r);
    $dias = (strtotime(date('Y-m-d')) - strtotime($p['fecha_venc'])) / 86400;
    $m = 0;
    if ($dias > 0) {
        if ($p['tipo_usr'] == 1) {
            if ($p['tipo_mat'] == 3) {
                $m = ($dias - 1) * 1500; // tarifa anterior a 2025
            } else {
                $m = ($dias - 2) * 500;
            }
        } else if ($p['tipo_usr'] == 2) {
            if ($p['tipo_mat'] == 3) {
                $m = ($dias - 1) * 1000;
            } else {
                $m = ($dias - 5) * 300;
            }
        } else {
            $m = $dias * 1500;
        }
    }
    if ($m > 0) {
        header("Location: pagar_multa.php?valor=" . $m);
        exit;
    }
    if ($p['renovaciones'] >= 2) {
        echo "<div class='error'>No se permiten más renovaciones</div>";
        return;
    }
    $nueva = date('Y-m-d', strtotime("+7 days"));
    mysql_query("UPDATE prestamos SET fecha_venc='$nueva', renovaciones = renovaciones + 1 WHERE id=$id_prestamo");
    echo "<div class='ok'>Renovado hasta " . $nueva . "</div>";
}
