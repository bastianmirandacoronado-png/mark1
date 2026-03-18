<?php
require_once dirname(__DIR__) . '/config.php';
$pdo = new PDO('sqlite:' . DB_PATH);
$rows = $pdo->query('SELECT anio, fecha_publicacion FROM ag_cotizaciones LIMIT 15')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo $r['anio'].' | '.$r['fecha_publicacion'].PHP_EOL;
