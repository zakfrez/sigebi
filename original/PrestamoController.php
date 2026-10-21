<?php
// F3 - PrestamoController.php, metodo registrar() (codigo ORIGINAL, sin modificar)
class PrestamoController {
    function registrar() {
        $cod = $_POST['codigo_usuario'];
        $ejem = $_POST['ejemplar'];
        $u = mysql_fetch_array(mysql_query("SELECT * FROM usuarios WHERE codigo='$cod'"));
        if ($u['estado'] == 'A' || $u['estado'] == 'a') {
            $n = mysql_num_rows(mysql_query("SELECT id FROM prestamos WHERE id_usr=" . $u['id'] . " AND fecha_dev IS NULL"));
            $limite = ($u['tipo'] == 2) ? 10 : 5;
            if ($n < $limite) {
                $e = mysql_fetch_array(mysql_query("SELECT * FROM ejemplares WHERE codigo_barras='$ejem'"));
                if ($e['disponible'] == 1) {
                    $venc = date('Y-m-d', strtotime("+7 days"));
                    mysql_query("INSERT INTO prestamos (id_usr, id_ejem, fecha_pres, fecha_venc, tipo_usr, tipo_mat)
                        VALUES (" . $u['id'] . "," . $e['id'] . ",'" . date('Y-m-d') . "','$venc'," . $u['tipo'] . "," . $e['tipo'] . ")");
                    mysql_query("UPDATE ejemplares SET disponible=0 WHERE id=" . $e['id']);
                    echo "<div class='ok'>Préstamo registrado para " . $u['nombre'] . ". Vence el " . $venc . "</div>";
                } else {
                    echo "<div class='error'>El ejemplar no está disponible</div>";
                }
            } else {
                echo "<div class='error'>Límite de préstamos alcanzado</div>";
            }
        } else {
            echo "<div class='error'>Usuario inactivo</div>";
        }
    }
}
