<!--PHP-->
<?php
//Datos de conexión a la base de datos
$dsn  = "mysql:host=localhost;dbname=PrecioLuz";
$user = "root";
$pass = "";

//Si no se elige fecha, se pone la de hoy
$fechaISO = isset($_REQUEST['fecha']) && $_REQUEST['fecha'] !== ''
  ? $_REQUEST['fecha']
  : date('Y-m-d');

//Variables para guardar los datos
$precioDia = null;      // precio medio del día
$horas = [];        // horas del día (00:00, 01:00, etc)
$preciosHoras = [];     // precios por hora
$filasHoras = [];      // para mostrar la tabla

//Función para pasar fecha de yyyy-mm-dd a dd/mm/yyyy
function fecha_bonita($iso) {
  [$y,$m,$d] = explode('-', $iso);
  return "$d/$m/$y";
}

try {
  //Conexión con la base de datos
  $pdo = new PDO($dsn, $user, $pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  //Precio medio del día seleccionado
  $stmt = $pdo->prepare("SELECT precio FROM dias WHERE fecha = :f");
  $stmt->execute([':f' => $fechaISO]);
  if ($fila = $stmt->fetch()) {
    $precioDia = $fila['precio'];
  }

  //Precios por horas del día seleccionado
  $stmt2 = $pdo->prepare("
    SELECT TIME_FORMAT(ph.hora, '%H:00') AS hora_texto, ph.precio
    FROM precios_horas ph
    JOIN dias d ON ph.id_dia = d.id
    WHERE d.fecha = :f
    ORDER BY ph.hora
  ");
  $stmt2->execute([':f' => $fechaISO]);
  $filasHoras = $stmt2->fetchAll();

  //Guardamos las horas y precios para la gráfica
  foreach ($filasHoras as $fila) {
    $horas[] = $fila['hora_texto'];
    $preciosHoras[] = (float)$fila['precio'];
  }

} catch (Exception $e) {
  $error = $e->getMessage();
}
?>


<!--HTML-->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="styles.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <title>Precio de la tarifa de luz por horas</title>
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
    <img src="img/calendar-days-svgrepo-com (2).svg">Actualizado el día 
    <?php 
    echo fecha_bonita(date('Y-m-d')); 
    ?>
    <img src="img/clock-three-svgrepo-com (1).svg"> 3 minutos de lectura
  </p>
</div>

<div class="subtitulo">
  <h2>Precio de la luz por horas</h2>
</div>

<div class="subtitulo2">
  <!-- Aquí muestro la fecha seleccionada -->
  <h2 id="lugarfecha"><?php echo fecha_bonita($fechaISO); ?></h2>
</div>

<!--Formulario para elegir la fecha -->
<form method="get" class="datepicker" style="margin-bottom:1rem;">
  <input type="date" id="fecha" name="fecha"
         value="<?php echo htmlspecialchars($fechaISO); ?>"
         onchange="this.form.submit()">
  <noscript><button type="submit">Ver</button></noscript>
</form>

<!-- Muestra el precio medio del día -->
<?php if ($precioDia !== null): ?>
  <p><strong>Precio medio del día (€/kWh):</strong> <?php echo number_format($precioDia, 3, ',', '.'); ?></p>
<?php else: ?>
  <p><strong>Precio del día:</strong> — (sin dato en la base de datos)</p>
<?php endif; ?>

<!-- Gráfica -->
<div class="primeragrafica">
  <div class="canva1grafica">
    <canvas id="grafica"></canvas>
  </div>
</div>
<!--Este es el error si ocurre algo con la BBDD-->
<?php if (!empty($error)): ?>
  <pre style="color:red;"><?php echo $error; ?></pre>
<?php endif; ?>
<div class="sabiasque">
  <p><b>¿Sabias qué?</b></p>
  <p>Si no quieres modificar tu rutina, hoy podrías utilizar este tramo para poner lavadoras o cocinar ya que está ligeramente más bajo.</p>
  <div id="sabiasvalores">
    <p></p>
    <p></p>
  </div>
  <p>Este es el tramo de 2 o 3 horas más económicas durante el día (de 7 a 21 horas), que puede o no contener la hora más económica del día</p>
  
</div>
<div class="cajones">
<div class="cajon1">
<p>Precio medio del día</p>
</div>
<div class="cajon2">
<p>Precio mas bajo del día</p>
</div>
<div class="cajon3">
<p>Precio mas alto del día</p>
</div>
</div>

<script>
  function encontrarTramo() {
    let arr1=[[]];
    valores.forEach(element => {
      if (element.precio<0.10) {

        
      }
      
    });
    
  }
// Aquí preparo los datos para la gráfica ---
const etiquetas = <?php echo json_encode($horas); ?>;   // las horas
const valores = <?php echo json_encode($preciosHoras); ?>; // los precios
const colores = valores.map(precio =>{
  if (precio< 0.10){
    return 'rgba(0, 200, 0, 0.6)';
  }else if (precio>=0.10 && precio<= 0.20){
    return 'rgba(255, 165, 0, 0.7)';
  }else{
    return'rgba(255, 0, 0, 0.6)';
  }
})
// Crear la gráfica
const ctx = document.getElementById('grafica').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: etiquetas,
    datasets: [{
      label: 'Precio €/kWh por hora',
      data: valores,
      backgroundColor: colores ,
      borderColor: 'rgba(54,162,235,1)',
      borderWidth: 1
    }]
  },
  options: {
     responsive: true,
    maintainAspectRatio: false, 
    scales: { y: { beginAtZero: true } }
  }
});
</script>
</body>
</html>
