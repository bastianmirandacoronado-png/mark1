<?php
/**
 * ╔══════════════════════════════════════════════════════════╗
 * ║   MARK 1 — ETL · Refresh de estados de OCs activas      ║
 * ╚══════════════════════════════════════════════════════════╝
 *
 * Actualiza el estado de OCs con estados no-finales
 * (En proceso, Enviada a proveedor, Aceptada) consultando
 * la API de Mercado Público OC por OC.
 *
 * Uso:
 *   php etl/refresh_estados.php             → actualiza estados no-finales
 *   php etl/refresh_estados.php --todos     → incluye también Aceptada
 *   php etl/refresh_estados.php --anio=2023 → solo OCs de ese año
 */

define('BASE', dirname(__DIR__));
require_once BASE . '/config.php';
require_once BASE . '/api/Mark1Database.php';

$esCLI = php_sapi_name() === 'cli';
if (!$esCLI) {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    ob_implicit_flush(true);
}

set_time_limit(0);
ini_set('memory_limit', '128M');

$args   = $esCLI ? getopt('', ['todos', 'anio:']) : $_GET;
$todos  = isset($args['todos']);
$anio   = isset($args['anio']) ? (int)$args['anio'] : null;

// ── Estados no-finales a refrescar ────────────────────────
// Sin --todos: solo estados claramente pendientes
// Con --todos: incluye Aceptada (más lento, ~528 OCs extra)
$estados = $todos
    ? ["'En proceso'", "'Enviada a proveedor'", "'Aceptada'"]
    : ["'En proceso'", "'Enviada a proveedor'"];

$db  = new Mark1Database();
$pdo = new PDO('sqlite:' . DB_PATH);
$ctx = stream_context_create(['http' => ['timeout' => 60, 'ignore_errors' => true]]);

emit("🔄 MARK 1 — Refresh de estados");
emit("📋 Estados objetivo: " . implode(', ', $estados));
if ($anio) emit("📅 Filtrando año: {$anio}");

// ── Obtener OCs candidatas ────────────────────────────────
$where = 'estado_oc IN (' . implode(',', $estados) . ')';
if ($anio) $where .= " AND anio = {$anio}";

$filas = $pdo->query("SELECT codigo_oc, estado_oc, anio FROM oc_resumen WHERE {$where} ORDER BY anio ASC")->fetchAll(PDO::FETCH_ASSOC);
$total = count($filas);

emit("📦 OCs a verificar: {$total}");
if ($total === 0) { emit("✅ Nada que actualizar."); exit; }

// ── Refresh OC por OC ─────────────────────────────────────
$actualizadas = 0;
$sinCambio    = 0;
$errores      = 0;

foreach ($filas as $i => $fila) {
    $codigo      = $fila['codigo_oc'];
    $estadoViejo = $fila['estado_oc'];
    $num         = $i + 1;

    usleep(ETL_DELAY_MS * 1000);

    $oc = fetchOCCompleta($codigo, $ctx);
    if (empty($oc)) {
        emit("  ⚠️  [{$num}/{$total}] {$codigo} — sin respuesta");
        $errores++;
        continue;
    }

    $estadoNuevo = $oc['Estado'] ?? '';
    if ($estadoNuevo === $estadoViejo) {
        $sinCambio++;
        continue;
    }

    // Actualizar OC completa con datos frescos
    $ocNorm = normalizarOC($oc);
    $db->upsertOC($ocNorm);

    emit("  ✅ [{$num}/{$total}] {$codigo} | {$estadoViejo} → {$estadoNuevo}");
    $actualizadas++;
}

emit("\n════════════════════════════════════");
emit("✅ REFRESH COMPLETADO");
emit("🔁 Actualizadas : {$actualizadas}");
emit("➡️  Sin cambio   : {$sinCambio}");
emit("⚠️  Errores      : {$errores}");
emit("════════════════════════════════════");

// ── Funciones (reutilizadas de sync.php) ──────────────────

function fetchOCCompleta(string $codigo, $ctx): array
{
    $url  = MP_BASE_URL . '?codigo=' . urlencode($codigo) . '&ticket=' . MP_TICKET;
    $raw  = apiGet($url, $ctx, 3);
    $data = json_decode($raw, true);
    return $data['Listado'][0] ?? [];
}

function apiGet(string $url, $ctx, int $intentos = 2): string
{
    for ($i = 0; $i < $intentos; $i++) {
        if ($i > 0) sleep(2);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) continue;
        $check = json_decode($raw, true);
        if (!empty($check['Mensaje']) && strpos($check['Mensaje'], 'simult') !== false) {
            sleep(3); continue;
        }
        return $raw;
    }
    return '{}';
}

function normalizarOC(array $oc): array
{
    $codigo = $oc['Codigo']    ?? '';
    $tipo   = strtoupper($oc['Tipo'] ?? '');
    $fechas = $oc['Fechas']    ?? [];
    $fecha  = $fechas['FechaEnvio'] ?? $fechas['FechaCreacion'] ?? '';
    $estado = $oc['Estado']    ?? '';
    $anio   = (int) substr($fecha, 0, 4);
    $origen = TIPO_ORIGEN_MAP[$tipo] ?? inferirOrigenDeCodigo($codigo);
    $comp   = $oc['Comprador'] ?? [];
    $prov   = $oc['Proveedor'] ?? [];
    return [
        'codigo_oc'        => $codigo,
        'nombre_oc'        => $oc['Nombre']               ?? '',
        'estado_oc'        => $estado,
        'codigo_estado'    => $oc['CodigoEstado']          ?? '',
        'tipo_oc'          => $tipo,
        'origen_compra'    => $origen,
        'seguimiento'      => calcularSeguimiento($estado, $fecha),
        'fecha_envio'      => substr($fecha, 0, 10),
        'fecha_creacion'   => substr($fechas['FechaCreacion']  ?? '', 0, 10),
        'fecha_aceptacion' => substr($fechas['FechaAceptacion'] ?? '', 0, 10),
        'anio'             => $anio > 2000 ? $anio : (int)date('Y'),
        'total_bruto'      => (float)($oc['Total']         ?? 0),
        'total_neto'       => (float)($oc['TotalNeto']     ?? 0),
        'impuestos'        => (float)($oc['Impuestos']     ?? 0),
        'pct_iva'          => (float)($oc['PorcentajeIva'] ?? 0),
        'c_cod_unidad'     => $comp['CodigoUnidad']        ?? '',
        'c_unidad'         => $comp['NombreUnidad']        ?? '',
        'c_rut_unidad'     => $comp['RutUnidad']           ?? '',
        'c_comuna'         => $comp['ComunaUnidad']        ?? '',
        'c_region'         => $comp['RegionUnidad']        ?? '',
        'c_contacto'       => $comp['NombreContacto']      ?? '',
        'p_nombre'         => $prov['Nombre']              ?? '',
        'rut_proveedor'    => $prov['RutSucursal']         ?? '',
        'p_comuna'         => $prov['Comuna']              ?? '',
        'p_region'         => $prov['Region']              ?? '',
        'descripcion'      => $oc['Descripcion']           ?? '',
        'cod_licitacion'   => $oc['CodigoLicitacion']      ?? '',
        'forma_pago'       => $oc['FormaPago']             ?? '',
        'financiamiento'   => $oc['Financiamiento']        ?? '',
    ];
}

function inferirOrigenDeCodigo(string $codigo): string
{
    if (preg_match('/-([A-Z]+)\d{2}$/', $codigo, $m)) {
        return TIPO_ORIGEN_MAP[$m[1]] ?? 'Otros Procesos';
    }
    return 'Otros Procesos';
}

function calcularSeguimiento(string $estado, string $fecha): string
{
    $e = strtolower($estado);
    if (str_contains($e, 'enviada'))   return 'Por Aceptar';
    if (str_contains($e, 'aceptada'))  return 'Aceptada';
    if (str_contains($e, 'recepción') || str_contains($e, 'conforme')) return 'Recibida';
    if (str_contains($e, 'cancelada')) return 'Cancelada';
    return 'En Proceso';
}

function emit(string $msg): void
{
    global $esCLI;
    $line = date('[H:i:s]') . ' ' . $msg;
    if ($esCLI) {
        echo $line . PHP_EOL;
    } else {
        echo "data: " . json_encode($line) . "\n\n";
        if (ob_get_level()) ob_flush();
        flush();
    }
}
