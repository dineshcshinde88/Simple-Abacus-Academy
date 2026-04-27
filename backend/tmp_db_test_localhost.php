<?php
try {
  $pdo = new PDO("mysql:host=localhost;port=3306;dbname=abacus_db;charset=utf8mb4","root","");
  echo "OK";
} catch (Throwable $e) {
  echo $e->getMessage();
}
