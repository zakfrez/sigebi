<?php
/**
 * SIGEBI · Aplicación web (préstamos, devoluciones y renovaciones).
 * Las reglas de negocio son las clases refactorizadas de src/ (Actividades 3 y 4);
 * este archivo solo recibe la petición, llama al servicio y muestra el resultado.
 *
 * Ejecutar:  php -S localhost:8000 -t public     y abrir http://localhost:8000
 */
require __DIR__ . '/../app/bootstrap.php';

use Sigebi\Circulacion\DevolucionAction;
use Sigebi\Circulacion\RenovacionAction;
use Sigebi\Circulacion\RepositorioCirculacionPdo;
use Sigebi\Circulacion\ResultadoRenovacion;
use Sigebi\Circulacion\VistaCirculacion;
use Sigebi\Config\Configuracion;
use Sigebi\Multa\CalculadoraMulta;
use Sigebi\Multa\CondicionesMora;
use Sigebi\Prestamos\PrestamoService;

session_start();

// ---------------------------------------------------------------- dependencias (inyección manual)
$repositorio  = new RepositorioCirculacionPdo($pdo);
$calculadora  = new CalculadoraMulta();
$config       = Configuracion::cargar($pdo);
$vista        = new VistaCirculacion();
$consultas    = new ConsultasPanel($pdo);

// "Fecha de operación": permite simular otro día para ver multas sin esperar.
$hoy = $_SESSION['fecha'] ?? date('Y-m-d');

function e($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function pesos(int $v): string { return '$' . number_format($v, 0, ',', '.'); }
function avisar(bool $ok, string $html): void { $_SESSION['aviso'] = ['ok' => $ok, 'html' => $html]; }
function ir(string $pagina): void { header('Location: ?p=' . $pagina); exit; }
function diasEntre(string $desde, string $hasta): int { return (int) round((strtotime($hasta) - strtotime($desde)) / 86400); }
function tipoUsuario(int $t): string { return [1 => 'Estudiante', 2 => 'Docente', 3 => 'Externo'][$t] ?? 'Otro'; }
function tipoMaterial(int $t): string { return $t === 3 ? 'Reserva' : 'General'; }

// ---------------------------------------------------------------- acciones (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'registrar') {                       // F3: PrestamoService
        $r = (new PrestamoService($repositorio))->registrar(trim($_POST['codigo_usuario'] ?? ''), trim($_POST['ejemplar'] ?? ''), $hoy);
        avisar($r->exito, e($r->mensaje));
        ir($r->exito ? 'activos' : 'registrar');
    }

    if ($accion === 'devolver') {                        // F1: DevolucionAction + CalculadoraMulta
        $id = (int) $_POST['id'];
        $multa = (new DevolucionAction($repositorio, $calculadora))->devolver($id, $hoy);
        $consultas->liberarEjemplarDelPrestamo($id);
        avisar($multa === 0, $multa > 0 ? 'Devolución registrada. Multa generada: <b>' . pesos($multa) . '</b>'
                                        : 'Devolución registrada a tiempo, sin multa.');
        ir('activos');
    }

    if ($accion === 'renovar') {                         // F2 + F4: RenovacionAction + Configuracion
        $r = (new RenovacionAction($repositorio, $calculadora, $config))->renovar((int) $_POST['id'], $hoy);
        if ($r->tipo === ResultadoRenovacion::MULTA_PENDIENTE) {
            avisar(false, 'No se puede renovar: tiene una multa pendiente de <b>' . pesos($r->valorMulta) . '</b>. '
                . 'Debe pagarla primero (' . e($vista->urlPagoMulta($r->valorMulta)) . ').');
        } elseif ($r->tipo === ResultadoRenovacion::LIMITE) {
            avisar(false, 'No se permiten más renovaciones (máximo ' . $config->maximoRenovaciones() . ', según la configuración).');
        } else {
            avisar(true, 'Préstamo renovado hasta el <b>' . e($r->nuevaFecha) . '</b>.');
        }
        ir('activos');
    }

    if ($accion === 'fecha') {
        $f = $_POST['fecha'] ?? '';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f)) { $_SESSION['fecha'] = $f; }
        if (isset($_POST['hoy_real'])) { unset($_SESSION['fecha']); }
        ir($_POST['volver'] ?? 'activos');
    }

    if ($accion === 'reiniciar') {
        BaseDatos::reiniciar($pdo);
        unset($_SESSION['fecha']);
        avisar(true, 'Datos de demostración reiniciados.');
        ir('activos');
    }
}

// ---------------------------------------------------------------- vista
$pagina = $_GET['p'] ?? 'activos';
$aviso = $_SESSION['aviso'] ?? null;
unset($_SESSION['aviso']);
$paginas = ['activos' => 'Préstamos activos', 'registrar' => 'Registrar préstamo', 'historial' => 'Devoluciones',
            'usuarios' => 'Usuarios', 'ejemplares' => 'Ejemplares', 'config' => 'Configuración'];
if (!isset($paginas[$pagina])) { $pagina = 'activos'; }
$simulada = isset($_SESSION['fecha']) && $_SESSION['fecha'] !== date('Y-m-d');
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SIGEBI · <?= e($paginas[$pagina]) ?></title>
<link rel="stylesheet" href="estilos.css">
</head>
<body>
<header class="barra">
  <div class="marca">
    <span class="logo">SIGEBI</span>
    <span class="sub">Biblioteca Universitaria Villa Verde</span>
  </div>
  <form method="post" class="fecha <?= $simulada ? 'simulada' : '' ?>">
    <input type="hidden" name="accion" value="fecha">
    <input type="hidden" name="volver" value="<?= e($pagina) ?>">
    <label>Fecha de operación
      <input type="date" name="fecha" value="<?= e($hoy) ?>" onchange="this.form.submit()">
    </label>
    <?php if ($simulada): ?><button name="hoy_real" value="1" class="mini">Volver a hoy</button><?php endif; ?>
  </form>
</header>
<nav class="menu">
  <?php foreach ($paginas as $clave => $nombre): ?>
    <a href="?p=<?= $clave ?>" class="<?= $clave === $pagina ? 'activo' : '' ?>"><?= e($nombre) ?></a>
  <?php endforeach; ?>
</nav>
<main>
<?php if ($simulada): ?>
  <p class="nota">Está simulando el día <b><?= e($hoy) ?></b>. Las multas y fechas se calculan como si hoy fuera ese día.</p>
<?php endif; ?>
<?php if ($aviso): ?>
  <div class="aviso <?= $aviso['ok'] ? 'ok' : 'error' ?>"><?= $aviso['html'] ?></div>
<?php endif; ?>

<?php if ($pagina === 'activos'): $filas = $consultas->prestamosActivos(); ?>
  <h1>Préstamos activos</h1>
  <p class="lead">Devuelva o renueve un préstamo. La multa se calcula con <code>CalculadoraMulta</code>, la misma para devolver y renovar.</p>
  <?php if (!$filas): ?><p class="vacio">No hay préstamos activos.</p><?php else: ?>
  <div class="tabla"><table>
    <thead><tr><th>#</th><th>Usuario</th><th>Ejemplar</th><th>Vence</th><th>Estado</th><th class="num">Multa hoy</th><th class="num">Renov.</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($filas as $f):
        $dias = diasEntre($f['fecha_venc'], $hoy);
        $multa = $calculadora->calcular(new CondicionesMora($dias, (int) $f['tipo_usr'], (int) $f['tipo_mat'])); ?>
      <tr>
        <td><?= (int) $f['id'] ?></td>
        <td><?= e($f['nombre']) ?><small><?= e($f['cod_usuario']) ?> · <?= tipoUsuario((int) $f['tipo_usr']) ?></small></td>
        <td><?= e($f['titulo']) ?><small><?= e($f['codigo_barras']) ?> · <?= tipoMaterial((int) $f['tipo_mat']) ?></small></td>
        <td><?= e($f['fecha_venc']) ?></td>
        <td><?php if ($dias > 0): ?><span class="chip rojo">Vencido <?= $dias ?> d</span>
            <?php else: ?><span class="chip verde">Al día</span><?php endif; ?></td>
        <td class="num <?= $multa > 0 ? 'deuda' : '' ?>"><?= pesos($multa) ?></td>
        <td class="num"><?= (int) $f['renovaciones'] ?>/<?= $config->maximoRenovaciones() ?></td>
        <td><div class="acciones">
          <form method="post"><input type="hidden" name="accion" value="devolver"><input type="hidden" name="id" value="<?= (int) $f['id'] ?>"><button>Devolver</button></form>
          <form method="post"><input type="hidden" name="accion" value="renovar"><input type="hidden" name="id" value="<?= (int) $f['id'] ?>"><button class="sec">Renovar</button></form>
        </div></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>

<?php elseif ($pagina === 'registrar'): ?>
  <h1>Registrar préstamo</h1>
  <p class="lead">Lo procesa <code>PrestamoService</code>: valida usuario activo, límite de préstamos (5 o 10 para docentes) y disponibilidad. Las consultas son preparadas, así que la inyección SQL no funciona.</p>
  <form method="post" class="tarjeta formulario">
    <input type="hidden" name="accion" value="registrar">
    <label>Código del usuario <input name="codigo_usuario" required placeholder="Ej. U001" autofocus></label>
    <label>Código de barras del ejemplar <input name="ejemplar" required placeholder="Ej. B001"></label>
    <button>Registrar préstamo</button>
  </form>
  <p class="pista">Pruebe también con un usuario inactivo (U002), un ejemplar ya prestado o el texto <code>zzz' OR codigo='U001</code>.</p>

<?php elseif ($pagina === 'historial'): $filas = $consultas->historial(); ?>
  <h1>Devoluciones</h1>
  <?php if (!$filas): ?><p class="vacio">Todavía no hay devoluciones registradas.</p><?php else: ?>
  <div class="tabla"><table>
    <thead><tr><th>#</th><th>Usuario</th><th>Ejemplar</th><th>Vencía</th><th>Devuelto</th><th class="num">Multa</th></tr></thead>
    <tbody><?php foreach ($filas as $f): ?>
      <tr><td><?= (int) $f['id'] ?></td><td><?= e($f['nombre']) ?></td><td><?= e($f['titulo']) ?></td>
          <td><?= e($f['fecha_venc']) ?></td><td><?= e($f['fecha_dev']) ?></td>
          <td class="num <?= $f['multa'] > 0 ? 'deuda' : '' ?>"><?= pesos((int) $f['multa']) ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php endif; ?>

<?php elseif ($pagina === 'usuarios'): ?>
  <h1>Usuarios</h1>
  <div class="tabla"><table>
    <thead><tr><th>Código</th><th>Nombre</th><th>Tipo</th><th>Estado</th><th class="num">Préstamos activos</th></tr></thead>
    <tbody><?php foreach ($consultas->usuarios() as $u): ?>
      <tr><td><code><?= e($u['codigo']) ?></code></td><td><?= e($u['nombre']) ?></td><td><?= tipoUsuario((int) $u['tipo']) ?></td>
          <td><?= strtoupper($u['estado']) === 'A' ? '<span class="chip verde">Activo</span>' : '<span class="chip gris">Inactivo</span>' ?></td>
          <td class="num"><?= (int) $u['activos'] ?> / <?= (int) $u['tipo'] === 2 ? 10 : 5 ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>

<?php elseif ($pagina === 'ejemplares'): ?>
  <h1>Ejemplares</h1>
  <div class="tabla"><table>
    <thead><tr><th>Código</th><th>Título</th><th>Material</th><th>Estado</th></tr></thead>
    <tbody><?php foreach ($consultas->ejemplares() as $x): ?>
      <tr><td><code><?= e($x['codigo_barras']) ?></code></td><td><?= e($x['titulo']) ?></td><td><?= tipoMaterial((int) $x['tipo']) ?></td>
          <td><?= $x['disponible'] ? '<span class="chip verde">Disponible</span>' : '<span class="chip gris">Prestado</span>' ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>

<?php elseif ($pagina === 'config'): ?>
  <h1>Configuración</h1>
  <p class="lead">La tabla <code>config_general</code> sigue teniendo columnas <code>datoN</code>; la clase <code>Configuracion</code> les da nombre (F4).</p>
  <div class="tabla"><table>
    <thead><tr><th>Parámetro</th><th>Columna</th><th>Valor</th></tr></thead>
    <tbody>
      <tr><td>Días de préstamo (estudiante)</td><td><code>dato3</code></td><td><?= $config->diasPrestamoEstudiante() ?></td></tr>
      <tr><td>Días de préstamo (docente)</td><td><code>dato4</code></td><td><?= $config->diasPrestamoDocente() ?></td></tr>
      <tr><td>Tarifa base</td><td><code>dato17</code></td><td><?= pesos($config->tarifaBase()) ?></td></tr>
      <tr><td>Máximo de renovaciones</td><td><code>dato21</code></td><td><?= $config->maximoRenovaciones() ?></td></tr>
      <tr><td>Correo remitente</td><td><code>dato35</code></td><td><?= e($config->correoRemitente()) ?></td></tr>
      <tr><td>Dato desconocido (pendiente de validar)</td><td><code>dato52</code></td><td><?= $config->datoDesconocido52() ?></td></tr>
    </tbody>
  </table></div>
  <h2>Regla de multas (<code>CalculadoraMulta</code>)</h2>
  <div class="tabla"><table>
    <thead><tr><th>Usuario</th><th>Material</th><th class="num">Días de gracia</th><th class="num">Tarifa por día</th></tr></thead>
    <tbody>
      <tr><td>Estudiante</td><td>Reserva</td><td class="num">1</td><td class="num">$2.000</td></tr>
      <tr><td>Estudiante</td><td>General</td><td class="num">2</td><td class="num">$500</td></tr>
      <tr><td>Docente</td><td>Reserva</td><td class="num">1</td><td class="num">$1.000</td></tr>
      <tr><td>Docente</td><td>General</td><td class="num">5</td><td class="num">$300</td></tr>
      <tr><td>Externo</td><td>Cualquiera</td><td class="num">0</td><td class="num">$1.500</td></tr>
    </tbody>
  </table></div>
  <p class="pista">Multa = (días vencidos − días de gracia) × tarifa, y nunca negativa.</p>
  <form method="post" onsubmit="return confirm('¿Borrar todo y volver a los datos de demostración?')">
    <input type="hidden" name="accion" value="reiniciar"><button class="peligro">Reiniciar datos de demostración</button>
  </form>
<?php endif; ?>
</main>
<footer>SIGEBI refactorizado · Universidad de Cundinamarca · PHP <?= PHP_VERSION ?></footer>
</body>
</html>
