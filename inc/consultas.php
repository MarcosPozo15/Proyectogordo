<?php
function precioDia($pdo, $fecha) {
  $st = $pdo->prepare("SELECT precio FROM dias WHERE fecha=:f");
  $st->execute([':f'=>$fecha]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  return $r ? (float)$r['precio'] : null;
}

function horasDia($pdo, $fecha) {
  $sql = "
    SELECT TIME_FORMAT(ph.hora, '%H:00') AS hora_texto,
           HOUR(ph.hora) AS hnum,
           ph.precio
    FROM precios_horas ph
    JOIN dias d ON ph.id_dia = d.id
    WHERE d.fecha = :f
    ORDER BY ph.hora
  ";
  $st = $pdo->prepare($sql);
  $st->execute([':f'=>$fecha]);
  return $st->fetchAll(PDO::FETCH_ASSOC);
}

function mensual($pdo) {
  $primero = date('Y-m-01');
  $desde = date('Y-m-01', strtotime('-11 months', strtotime($primero)));
  $st = $pdo->prepare("
    SELECT DATE_FORMAT(fecha,'%Y-%m') ym, AVG(precio) media
    FROM dias
    WHERE fecha >= :d
    GROUP BY ym
    ORDER BY ym
  ");
  $st->execute([':d'=>$desde]);
  return $st->fetchAll(PDO::FETCH_ASSOC);
}

