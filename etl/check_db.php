<?php
require_once dirname(__DIR__) . '/config.php';
$pdo = new PDO('sqlite:' . DB_PATH);
$total_oc   = $pdo->query('SELECT COUNT(*) FROM oc_resumen')->fetchColumn();
$total_dias = $pdo->query('SELECT COUNT(*) FROM etl_control')->fetchColumn();
$min_f      = $pdo->query('SELECT MIN(fecha_envio) FROM oc_resumen')->fetchColumn();
$max_f      = $pdo->query('SELECT MAX(fecha_envio) FROM oc_resumen')->fetchColumn();
$ultimo     = $pdo->query("SELECT MAX(anio||'-'||printf('%02d',mes)||'-'||printf('%02d',dia)) FROM etl_control")->fetchColumn();
echo "OCs en DB       : $total_oc\n";
echo "Dias procesados : $total_dias\n";
echo "Primer envio    : $min_f\n";
echo "Ultimo envio    : $max_f\n";
echo "Ultimo dia ETL  : $ultimo\n";
