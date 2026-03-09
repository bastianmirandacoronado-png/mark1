<?php
/**
 * api/data.php — Mark 1
 * Endpoint JSON que alimenta el dashboard.
 * Lee desde SQLite (instantáneo), nunca desde la API externa.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

define('BASE', dirname(__DIR__));
require_once BASE . '/config.php';
require_once __DIR__ . '/Mark1Database.php';
require_once __DIR__ . '/Mark1Engine.php';

$action   = $_GET['action']    ?? 'stats';
$filtros  = [
    'anioDesde' => (int)($_GET['anioDesde'] ?? 2020),
    'anioHasta' => (int)($_GET['anioHasta'] ?? (int)date('Y')),
    'unidad'    => $_GET['unidad']    ?? '',
    'origen'    => $_GET['origen']    ?? '',
    'proveedor' => $_GET['proveedor'] ?? '',
    'busqueda'  => $_GET['busqueda']  ?? '',
];

try {
    $engine = new Mark1Engine();
    $db     = new Mark1Database();

    switch ($action) {

        case 'equipos':
            $raw      = $_GET['terminos'] ?? '';
            $terminos = array_filter(array_map('trim', explode(',', $raw)));
            if (!$terminos) { echo json_encode(['error' => 'terminos requerido']); exit; }
            $matches  = $engine->buscarEquipos($filtros, $terminos);
            echo json_encode([
                'matches'  => $matches,
                'total'    => count($matches),
                'monto'    => array_sum(array_column($matches, 'total_bruto')),
                'montoFmt' => $engine->fmt(array_sum(array_column($matches, 'total_bruto'))),
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'tendencia':
            $prov = trim($_GET['proveedor'] ?? '');
            if (!$prov) { echo json_encode(['error' => 'proveedor requerido']); exit; }
            echo json_encode([
                'datos'       => $engine->tendenciaProveedor($filtros, $prov),
                'proveedores' => $engine->proveedoresDisponibles($filtros),
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'etl_status':
            echo json_encode($db->estadoETL(), JSON_UNESCAPED_UNICODE);
            break;

        case 'listado':
            $unidad  = $_GET['unidad']  ?? '';
            $origen  = $_GET['origen']  ?? '';
            $filtrosL = array_merge($filtros, ['unidad'=>$unidad,'origen'=>$origen]);
            $pdo  = (new Mark1Database())->getPDO();
            $where = ['1=1']; $params = [];
            if (!empty($filtrosL['anioDesde'])) { $where[]='r.anio>=:ad'; $params[':ad']=$filtrosL['anioDesde']; }
            if (!empty($filtrosL['anioHasta'])) { $where[]='r.anio<=:ah'; $params[':ah']=$filtrosL['anioHasta']; }
            if (!empty($filtrosL['unidad']))    { $where[]='r.c_unidad=:un'; $params[':un']=$filtrosL['unidad']; }
            if (!empty($filtrosL['origen']))    { $where[]='r.origen_compra=:or'; $params[':or']=$filtrosL['origen']; }
            $sql  = 'SELECT r.* FROM oc_resumen r WHERE '.implode(' AND ',$where).' ORDER BY r.fecha_envio DESC';
            $stmt = $pdo->prepare($sql); $stmt->execute($params);
            $ocs  = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['ocs'=>$ocs,'total'=>count($ocs)], JSON_UNESCAPED_UNICODE);
            break;

        default: // stats
            echo json_encode([
                'kpis'       => $engine->kpis($filtros),
                'topProv'    => $engine->topProveedores($filtros, 10),
                'distEstado' => $engine->distEstado($filtros),
                'distOrigen' => $engine->distOrigen($filtros),
                'evolucion'  => $engine->evolucionAnual($filtros),
                'topItems'   => $engine->topItems($filtros, 20),
                'frecProv'   => $engine->frecuenciaProveedores($filtros),
                'anios'      => array_column($engine->aniosDisponibles(), 'anio'),
                'proveedores'=> $engine->proveedoresDisponibles($filtros),
                'unidades'   => $engine->unidadesDisponibles(),
                'meta'       => $db->estadoETL(),
            ], JSON_UNESCAPED_UNICODE);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
