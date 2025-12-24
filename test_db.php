<?php
require __DIR__ . '/config/db.php';
try{
  $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
  echo "OK — Connected to MySQL " . htmlspecialchars($ver);
}catch(Throwable $e){
  http_response_code(500);
  echo "DB error";
}
