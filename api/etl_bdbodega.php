<?php
/**
 * api/etl_bdbodega.php
 * Endpoint que ejecuta el ETL Python para importar bdbodega.xlsx → SQLite.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$pythonExe = 'python';
$script    = __DIR__ . '/etl_bdbodega.py';
$reset     = isset($_GET['reset']) ? ' --reset' : '';

if (!file_exists($script)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Script ETL no encontrado']);
    exit;
}

$cmd    = escapeshellarg($pythonExe) . ' ' . escapeshellarg($script) . $reset . ' 2>&1';
$output = [];
$code   = 0;
exec($cmd, $output, $code);

echo json_encode([
    'ok'     => $code === 0,
    'codigo' => $code,
    'salida' => implode("\n", $output),
], JSON_UNESCAPED_UNICODE);
