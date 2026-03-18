<?php
/**
 * api/data.php — Mark 1
 * Endpoint JSON que alimenta el dashboard.
 * Lee desde SQLite (instantáneo), nunca desde la API externa.
 */

// Verificar sesión activa
require_once dirname(__DIR__) . '/auth.php';

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
                'monto'    => array_sum(array_column($matches, 'monto')),
                'montoFmt' => $engine->fmt(array_sum(array_column($matches, 'monto'))),
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

        case 'inversiones':
            echo json_encode([
                'kpis'            => $engine->inversionesKPIs(),
                'histograma'      => $engine->histogramaAnualInv(),
                'compraAgilAnual'   => $engine->compraAgilAnualInv(),
                'compraAgilMensual' => $engine->compraAgilMensualInv(),
                'topProveedores'  => $engine->topProveedoresInv(10),
                'indiceAdj'       => $engine->indiceAdjudicacionInv(15),
                'adjModalidad'    => $engine->adjModalidadInv(15),
                'litPorAnio'      => $engine->licitacionesPorAnioInv(),
                'topProductos'    => $engine->topProductosInv(10),
                'patronesProd'    => $engine->patronesProvProd(2, 30),
                'proveedores'     => $engine->proveedoresInv(),
                'tendenciaAG'     => $engine->kpiTendenciaAG(),
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'inv_tend':
            $prov = trim($_GET['proveedor'] ?? '');
            if (!$prov) { echo json_encode(['error' => 'proveedor requerido']); exit; }
            echo json_encode([
                'datos' => $engine->tendenciaProveedorInv($prov),
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'trazabilidad':
            echo json_encode([
                'kpis'       => $engine->bodegaKPIs(),
                'distEstado' => $engine->bodegaDistEstado(),
                'distTipo'   => $engine->bodegaDistTipo(),
                'distBodega' => $engine->bodegaDistBodega(),
                'evolucion'  => $engine->bodegaEvolucionMensual(),
                'alertas'    => $engine->bodegaAlertasTipo(),
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'trazabilidad_buscar':
            $q = trim($_GET['q'] ?? '');
            echo json_encode($engine->bodegaBuscarOC($q, 30), JSON_UNESCAPED_UNICODE);
            break;

        case 'trazabilidad_oc':
            $oc = trim($_GET['oc'] ?? '');
            if (!$oc) { echo json_encode(['error' => 'OC requerida']); exit; }
            echo json_encode($engine->bodegaDetalleOC($oc), JSON_UNESCAPED_UNICODE);
            break;

        case 'compraagil':
            echo json_encode([
                'kpis'         => $engine->agKPIs(),
                'evolucion'        => $engine->agEvolucionAnual(),
                'evolucionMensual' => $engine->agEvolucionMensual(),
                'distEstado'   => $engine->agDistEstado(),
                'convocatoria' => $engine->agConvocatoria(),
                'montoAnual'   => $engine->agMontoAnual(),
                'topProv'      => $engine->agTopProveedores(10),
                'topItems'     => $engine->agTopItems(15),
                'competencia'  => $engine->agCompetenciaAnual(),
                'estadoOC'     => $engine->agEstadoOCAnual(),
                'anios'        => $engine->agAnios(),
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'obras':
            echo json_encode([
                'kpis'         => $engine->obrasKPIs(),
                'evolucion'    => $engine->obrasEvolucionAnual(),
                'topProv'      => $engine->obrasTopProveedores(10),
                'distCategoria'=> $engine->obrasDistNombre(),
                'distEstado'   => $engine->obrasDistEstado(),
                'activas'      => $engine->obrasActivas(),
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'infejec':
            echo json_encode([
                'semaforo'        => $engine->infEjecSemaforo($filtros),
                'concentracion'   => $engine->infEjecConcentracion($filtros),
                'tratoDirecto'    => $engine->infEjecTratoDirecto($filtros),
                'variacionPrecios'=> $engine->infEjecVariacionPrecios($filtros, 20),
                'sinCompra'       => $engine->infEjecSinCompra($filtros, 12),
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ── GARANTÍAS DE SERIEDAD ─────────────────────────────
        case 'garantias_listar':
            $est    = trim($_GET['estado'] ?? '');
            $uc     = trim($_GET['unidad'] ?? '');
            echo json_encode([
                'kpis'       => $engine->garantiaKpis(),
                'garantias'  => $engine->garantiaListar($est, $uc),
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'garantias_buscar_licit':
            $cod = trim($_GET['cod'] ?? '');
            echo json_encode($engine->garantiaBuscarLicit($cod), JSON_UNESCAPED_UNICODE);
            break;

        case 'garantias_registrar':
            // Solo acepta POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['ok'=>false,'msg'=>'Método no permitido']);
                break;
            }
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            // Validar campos obligatorios
            if (empty($body['cod_licitacion']) || empty($body['empresa']) || empty($body['fecha_vencimiento'])) {
                http_response_code(400);
                echo json_encode(['ok'=>false,'msg'=>'Faltan campos obligatorios: cod_licitacion, empresa, fecha_vencimiento']);
                break;
            }
            echo json_encode($engine->garantiaRegistrar($body), JSON_UNESCAPED_UNICODE);
            break;

        case 'garantias_devolver':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['ok'=>false,'msg'=>'Método no permitido']);
                break;
            }
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $id   = (int)($body['id'] ?? 0);
            $fd   = trim($body['fecha_devolucion'] ?? date('Y-m-d'));
            $da   = trim($body['devuelta_a'] ?? '');
            $ob   = trim($body['observacion'] ?? '');
            if (!$id) {
                http_response_code(400);
                echo json_encode(['ok'=>false,'msg'=>'ID inválido']);
                break;
            }
            echo json_encode($engine->garantiaDevolver($id, $fd, $da, $ob), JSON_UNESCAPED_UNICODE);
            break;

        // ── COMPRA ÁGIL — CANCELADAS / DESIERTAS ─────────────
        case 'ag_canceladas':
            $anio = (int)($_GET['anio'] ?? 0);
            echo json_encode([
                'kpis'  => $engine->agCanceladasKpis($anio),
                'lista' => $engine->agCanceladasDesiertas($anio),
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'ag_guardar_motivo':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405); echo json_encode(['ok'=>false]); break;
            }
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $idC  = trim($body['id_cotizacion'] ?? '');
            $mot  = trim($body['motivo'] ?? '');
            $reg  = trim($body['registrado_por'] ?? '');
            if (!$idC) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'id requerido']); break; }
            echo json_encode($engine->agGuardarMotivo($idC, $mot, $reg), JSON_UNESCAPED_UNICODE);
            break;

        case 'liberacion':
            echo json_encode([
                'kpis'       => $engine->liberacionKPIs(),
                'porAnio'    => $engine->liberacionPorAnio(),
                'porUnidad'  => $engine->liberacionPorUnidad(),
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'liberacion_detalle':
            $est = trim($_GET['estado']   ?? '');
            $uni = trim($_GET['unidad']   ?? '');
            $bq  = trim($_GET['busqueda'] ?? '');
            echo json_encode([
                'rows' => $engine->liberacionDetalle($est, $uni, $bq),
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
                'kpis'            => $engine->kpis($filtros),
                'topProv'         => $engine->topProveedores($filtros, 10),
                'distEstado'      => $engine->distEstado($filtros),
                'distOrigen'      => $engine->distOrigen($filtros),
                'evolucion'       => $engine->evolucionAnual($filtros),
                'topItems'        => $engine->topItems($filtros, 20),
                'frecProv'        => $engine->frecuenciaProveedores($filtros),
                'tendenciaGeneral'=> $engine->kpiTendenciaGeneral($filtros),
                'anios'           => array_column($engine->aniosDisponibles(), 'anio'),
                'proveedores'     => $engine->proveedoresDisponibles($filtros),
                'unidades'        => $engine->unidadesDisponibles(),
                'meta'            => $db->estadoETL(),
            ], JSON_UNESCAPED_UNICODE);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
