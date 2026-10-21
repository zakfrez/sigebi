<?php
// F4 - config/Parametros.php (codigo ORIGINAL, sin modificar)
$cfg = mysql_fetch_array(mysql_query("SELECT * FROM config_general WHERE id=1"));
$dias_prestamo_est = $cfg['dato3'];
$dias_prestamo_doc = $cfg['dato4'];
$tarifa_base = $cfg['dato17'];
$max_renov = $cfg['dato21'];
$correo_remitente = $cfg['dato35'];
if ($cfg['dato52'] == 1) { /* ¿modo mantenimiento? ¿envío de recordatorios? nadie lo sabe */ }
