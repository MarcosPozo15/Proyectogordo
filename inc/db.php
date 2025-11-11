<?php
function getPDO() {
  $dsn  = "mysql:host=localhost;dbname=PrecioLuz";
  $user = "root";
  $pass = "";
  $pdo = new PDO($dsn, $user, $pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  return $pdo;
}

