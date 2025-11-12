<?php
// mostrar errores mientras arreglamos
error_reporting(E_ALL);
ini_set('display_errors', 1);

// cargo conexión y consultas
require_once __DIR__.'/inc/db.php';
require_once __DIR__.'/inc/consultas.php';

// utilidades
function fecha_bonita($iso){ [$y,$m,$d]=explode('-',$iso); return "$d/$m/$y"; }

// fecha seleccionada (o hoy)
$fechaISO = isset($_GET['fecha']) && $_GET['fecha'] !== '' ? $_GET['fecha'] : date('Y-m-d');

try {
  $pdo = getPDO();

  // datos base
  $precioDia  = precioDia($pdo, $fechaISO);
  $filasHoras = horasDia($pdo, $fechaISO);   // devuelve [hora_texto, hnum, precio]
  $mensual    = mensual($pdo);               // medias por mes últimos 12

  // preparar arrays para gráfica + min/max
  $horas=[]; $preciosHoras=[];
  $minHora=null; $minPrecio=null; $maxHora=null; $maxPrecio=null;
  $porHora = array_fill(0,24,null);

  foreach($filasHoras as $f){
    $h=(int)$f['hnum']; $p=(float)$f['precio'];
    $horas[] = $f['hora_texto'];
    $preciosHoras[] = $p;
    $porHora[$h] = $p;
    if($minPrecio===null || $p<$minPrecio){ $minPrecio=$p; $minHora=$h; }
    if($maxPrecio===null || $p>$maxPrecio){ $maxPrecio=$p; $maxHora=$h; }
  }

  // mejor tramo de 2 o 3 horas entre 7 y 21
  $mejorInicio=null; $mejorTam=null; $mejorMedia=null; $bestAvg=INF;
  foreach([[2,7,21],[3,7,21]] as [$tam,$ini,$fin]){
    for($h=$ini; $h<=$fin-$tam; $h++){
      $s=0; for($k=0;$k<$tam;$k++) $s += $porHora[$h+$k] ?? INF;
      $avg=$s/$tam;
      if($avg<$bestAvg){ $bestAvg=$avg; $mejorInicio=$h; $mejorTam=$tam; $mejorMedia=$avg; }
    }
  }

  // mensual → etiquetas y valores
  function etiqueta_mes_es($ym){
    static $n=[1=>"enero",2=>"febrero",3=>"marzo",4=>"abril",5=>"mayo",6=>"junio",7=>"julio",8=>"agosto",9=>"septiembre",10=>"octubre",11=>"noviembre",12=>"diciembre"];
    [$y,$m]=array_map('intval',explode('-',$ym)); return $n[$m].' '.substr($y,-2);
  }
  $mesesLabel=[]; $mesProm=[];
  foreach($mensual as $r){ $mesesLabel[]=etiqueta_mes_es($r['ym']); $mesProm[]=(float)$r['media']; }

} catch(Exception $e){
  $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="styles.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <title>Precio de la luz</title>
</head>
<body>
<header>
  <img src="img/tarifaluzhora-white.svg" alt="Logo">
  <div class="header-nav">
    <ul>
      <li>Mercado Luz y Gas</li>
      <li>Compañias</li>
      <li>Trámites</li>
      <li>Distribuidoras</li>
      <li>Ahorro</li>
      <li>Herramientas</li>
    </ul>
  </div>
</header>

<div class="divtit">
  <h1 class="titulo">Consulta el precio de la luz: Detalles y evolución de la tarifa PVPC</h1>
</div>

<div class="actualizacion">
  <p>
    <img src="img/calendar-days-svgrepo-com (2).svg">
    Actualizado el día <?php echo fecha_bonita(date('Y-m-d')); ?>
    <img src="img/clock-three-svgrepo-com (1).svg"> 3 minutos de lectura
  </p>
</div>

<div class="subtitulo"><h2>Precio de la luz por horas</h2></div>
<div class="subtitulo2"><h2 id="lugarfecha"><?php echo fecha_bonita($fechaISO); ?></h2></div>

<form method="get" class="datepicker" style="margin-bottom:1rem;">
  <input type="date" name="fecha" value="<?php echo htmlspecialchars($fechaISO); ?>" onchange="this.form.submit()">
  <noscript><button type="submit">Ver</button></noscript>
</form>

<!-- GRÁFICA POR HORAS -->
<div class="primeragrafica">
  <div class="canva1grafica">
    <canvas id="grafica"></canvas>
  </div>
</div>
<!-- ¿SABÍAS QUÉ? -->
<div class="big sabiasque">
  <p><b>¿Sabías qué?</b></p>
  <p>Si no quieres modificar tu rutina, hoy podrías utilizar este tramo para poner lavadoras o cocinar ya que está ligeramente más bajo.</p>
  <?php if ($mejorInicio !== null): ?>
    <h2><?php printf("%02d-%02dh", $mejorInicio, $mejorInicio+$mejorTam); ?></h2>
    <p class="verde">↓ <?php echo number_format($mejorMedia, 3, ',', '.'); ?> €/kWh</p>
    <p>Este es el tramo de 2 o 3 horas más económicas durante el día (de 7 a 21 horas).</p>
  <?php else: ?>
    <p>No tengo datos horarios para este día.</p>
  <?php endif; ?>
</div>

<!-- TRES CAJONES ( -->
<div class="cajones">
  <div class="cajon1">
    <p>Precio medio del día</p>
    <p><small><?php echo fecha_bonita($fechaISO); ?></small></p>
    <h2><?php echo $precioDia !== null ? number_format($precioDia, 4, ',', '.') : '—'; ?></h2>
    <small>€/kWh</small>
  </div>
  <div class="cajon2">
    <p>Precio mas bajo del día</p>
    <p><small><?php echo fecha_bonita($fechaISO); ?></small></p>
    <h2><?php echo $minHora !== null ? sprintf("%02d-%02dh", $minHora, $minHora+1) : '—'; ?></h2>
    <p class="verde"><?php echo $minPrecio !== null ? number_format($minPrecio, 4, ',', '.') . " €/kWh" : '—'; ?></p>
  </div>
  <div class="cajon3">
    <p>Precio mas alto del día</p>
    <p><small><?php echo fecha_bonita($fechaISO); ?></small></p>
    <h2><?php echo $maxHora !== null ? sprintf("%02d-%02dh", $maxHora, $maxHora+1) : '—'; ?></h2>
    <p class="rojo"><?php echo $maxPrecio !== null ? number_format($maxPrecio, 4, ',', '.') . " €/kWh" : '—'; ?></p>
  </div>
</div>

<!-- LISTA POR HORAS -->
<div class="lista-horas">
  <h3>Precio del kWh de luz por hora</h3>
  <ul class="horas">
    <?php foreach ($filasHoras as $fila):
      $h = (int)$fila['hnum'];
      $inicio = sprintf("%02d:00", $h);
      $fin    = sprintf("%02d:00", ($h + 1) % 24);
      $p      = (float)$fila['precio'];
      $cls = ($p < 0.10) ? 'verde' : (($p <= 0.12) ? 'naranja' : 'rojo');
    ?>
      <li>
        <span class="dot <?php echo $cls; ?>"></span>
        <span class="tramo"><?php echo $inicio; ?> - <?php echo $fin; ?></span>
        <span class="precio <?php echo $cls; ?>"><?php echo number_format($p, 4, ',', '.'); ?> €/kWh</span>
      </li>
    <?php endforeach; ?>
  </ul>
</div>

<!-- GRÁFICA MENSUAL -->
<div class="grafica-mensual" style="max-width:900px;margin:30px auto;">
  <h3>Precio medio mensual del PVPC</h3>
  <div style="position:relative;height:360px;"><canvas id="graficaMensual"></canvas></div>
</div>

<?php if (!empty($error)): ?>
  <pre style="color:red;"><?php echo $error; ?></pre>
<?php endif; ?>

<!-- PASO DATOS A JS Y CARGO app.js -->
<script>
  window.DATA_HORARIA = {
    etiquetas: <?php echo json_encode($horas); ?>,
    valores:   <?php echo json_encode($preciosHoras); ?>
  };
  window.DATA_MENSUAL = {
    etiquetas: <?php echo json_encode($mesesLabel); ?>,
    valores:   <?php echo json_encode($mesProm); ?>
  };
</script>
<script src="public/js/app.js"></script>
</body>
</html>

