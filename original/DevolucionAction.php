<?php
// F1 - circulacion/DevolucionAction.php, metodo devolver() (codigo ORIGINAL, sin modificar)
function devolver($id_prestamo) {
    $r = mysql_query("SELECT * FROM prestamos WHERE id = " . $id_prestamo);
    $p = mysql_fetch_array($r);
    $hoy = date('Y-m-d');
    $dias = (strtotime($hoy) - strtotime($p['fecha_venc'])) / 86400;
    $multa = 0;
    if ($dias > 0) {
        if ($p['tipo_usr'] == 1) { // estudiante
            if ($p['tipo_mat'] == 3) { // material de reserva
                $multa = ($dias - 1) * 2000;
            } else {
                $multa = ($dias - 2) * 500;
            }
        } else if ($p['tipo_usr'] == 2) { // docente
            if ($p['tipo_mat'] == 3) {
                $multa = ($dias - 1) * 1000;
            } else {
                $multa = ($dias - 5) * 300;
            }
        } else { // externo
            $multa = $dias * 1500;
        }
    }
    mysql_query("UPDATE prestamos SET fecha_dev='$hoy', multa=$multa WHERE id=$id_prestamo");
    if ($multa > 0) {
        echo "<div class='error'>Multa generada: $" . $multa . "</div>";
    }
}
