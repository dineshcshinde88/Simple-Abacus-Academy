<?php
try {
  $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=abacus_db;charset=utf8mb4","root","");
  echo "OK";
} catch (Throwable $e) {
  echo $e->getMessage();
}
