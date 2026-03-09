<?php
/**
 * ╔══════════════════════════════════════════════════════════╗
 * ║   MARK 1 — ETL · Carga histórica API → SQLite           ║
 * ╚══════════════════════════════════════════════════════════╝
 *
 * Uso:
 *   php etl/sync.php                    → sincroniza todo el histórico
 *   php etl/sync.php --anio=2025        → solo un año
 *   php etl/sync.php --desde=2024-01    → desde mes específico
 *   php etl/sync.php --hoy              → solo el día de hoy
 *   php etl/sync.php --forzar           → re-procesa días ya cargados
 *
 * Puede ejecutarse también desde el panel admin en el navegador.
 */

define('BASE', dirname(__DIR__));
require_once BASE . '/config.php';
require_once BASE . '/api/Mark1Database.php';

// ── Detectar si corre desde web o CLI ────────────────────
$esCLI = php_sapi_name() === 'cli';
if (!$esCLI) {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    ob_implicit_flush(true);
}

set_time_limit(0);
ini_set('memory_limit', '256M');

// ── Parsear argumentos ────────────────────────────────────
$args   = $esCLI ? getopt('', ['anio:', 'desde:', 'hoy', 'forzar']) : $_GET;
$forzar = isset($args['forzar']);
$hoy    = isset($args['hoy']);

if ($hoy) {
    $anioDesde = (int)date('Y'); $mesDesde = (int)date('m'); $diaDesde = (int)date('d');
    $anioHasta = $anioDesde;     $mesHasta = $mesDesde;
} elseif (isset($args['anio'])) {
    $anioDesde = (int)$args['anio']; $mesDesde = 1; $diaDesde = 1;
    $anioHasta = $anioDesde;         $mesHasta = 12;
} elseif (isset($args['desde'])) {
    [$anioDesde, $mesDesde] = explode('-', $args['desde']);
    $anioDesde = (int)$anioDesde; $mesDesde = (int)$mesDesde; $diaDesde = 1;
    $anioHasta = (int)date('Y');  $mesHasta = (int)date('m');
} else {
    $anioDesde = ETL_ANIO_DESDE; $mesDesde = 1; $diaDesde = 1;
    $anioHasta = (int)date('Y'); $mesHasta = (int)date('m');
}

// ── Inicializar ───────────────────────────────────────────
$db  = new Mark1Database();
$log = [];

emit("🚀 MARK 1 ETL — iniciando sincronización");
emit("📅 Rango: {$anioDesde}-{$mesDesde} → {$anioHasta}-{$mesHasta}");
emit("🏢 Organismo: Servicio de Salud Osorno (7296)");

$totalOCs = 0; $totalDias = 0; $errores = 0;
$ctx = stream_context_create(['http' => ['timeout' => 60, 'ignore_errors' => true]]);

emit("🏢 Organismo: " . MP_ORGANISMO . " — Servicio de Salud Osorno");

// ── LOOP PRINCIPAL ────────────────────────────────────────
for ($anio = $anioDesde; $anio <= $anioHasta; $anio++) {
    $mInicio = ($anio === $anioDesde) ? $mesDesde  : 1;
    $mFin    = ($anio === $anioHasta) ? $mesHasta  : 12;

    for ($mes = $mInicio; $mes <= $mFin; $mes++) {
        $diasMes  = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
        $dInicio  = ($anio === $anioDesde && $mes === $mesDesde) ? ($diaDesde ?? 1) : 1;
        $hoyDate  = new DateTime('today');

        emit("\n📆 Procesando {$anio}-" . str_pad($mes, 2, '0', STR_PAD_LEFT));

        for ($dia = $dInicio; $dia <= $diasMes; $dia++) {
            $fechaDia = new DateTime(sprintf('%04d-%02d-%02d', $anio, $mes, $dia));
            if ($fechaDia > $hoyDate) break;

            // Saltar si ya está procesado (a menos que --forzar)
            if (!$forzar && $db->diaYaProcesado($anio, $mes, $dia)) continue;

            $fecha    = sprintf('%02d%02d%04d', $dia, $mes, $anio);
            emit("  ⏳ Consultando {$fecha}...");
            $ocsDelDia = fetchDia($fecha, $ctx);
            $totalDias++;

            if (empty($ocsDelDia)) {
                $db->marcarDiaProcesado($anio, $mes, $dia, 0);
                continue;
            }

            // Procesar cada OC del día
            $ocsProcesadas = 0;
            $db->beginTransaction();
            try {
                foreach ($ocsDelDia as $item) {
                    $codigo = $item['Codigo'] ?? '';
                    if (empty($codigo)) continue;

                    // Obtener detalle completo
                    usleep(ETL_DELAY_MS * 1000);
                    $oc = fetchOCCompleta($codigo, $ctx);
                    if (empty($oc)) { $errores++; continue; }

                    // Normalizar y guardar OC resumen
                    $ocNorm = normalizarOC($oc);
                    $db->upsertOC($ocNorm);

                    // Guardar detalles (Items)
                    $items = normalizarItems($codigo, $oc['Items']['Listado'] ?? []);
                    $db->upsertDetalles($codigo, $items);

                    $ocsProcesadas++;
                    $totalOCs++;
                }
                $db->commit();
            } catch (Exception $e) {
                $db->rollback();
                emit("⚠️  Error en {$fecha}: " . $e->getMessage());
                $errores++;
            }

            $db->marcarDiaProcesado($anio, $mes, $dia, $ocsProcesadas);
            if ($ocsProcesadas > 0) {
                emit("  ✅ {$fecha}: {$ocsProcesadas} OCs guardadas");
            }
        }
    }
}

// ── Resumen final ─────────────────────────────────────────
$estado = $db->estadoETL();
emit("\n════════════════════════════════════");
emit("✅ SINCRONIZACIÓN COMPLETADA");
emit("📦 OCs procesadas esta sesión : {$totalOCs}");
emit("📊 Total en base de datos      : {$estado['total']}");
emit("🔗 Detalles (líneas)           : {$estado['detalles']}");
emit("⚠️  Errores                     : {$errores}");
emit("════════════════════════════════════");

// ── FUNCIONES ─────────────────────────────────────────────

function fetchDia(string $fecha, $ctx): array
{
    // Filtro directo por organismo 7296 en la API
    $url = MP_BASE_URL . '?fecha=' . $fecha . '&CodigoOrganismo=' . MP_ORGANISMO . '&ticket=' . MP_TICKET;
    emit("    🌐 Descargando día {$fecha}...");
    $raw  = apiGet($url, $ctx);
    $data = json_decode($raw, true);
    $listado = $data['Listado'] ?? [];
    if (empty($listado)) return [];
    emit("    📦 " . count($listado) . " OCs del SSO");
    return $listado;
}



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
        // Reintentar si hay peticiones simultáneas
        if (!empty($check['Mensaje']) && strpos($check['Mensaje'], 'simult') !== false) {
            sleep(3); continue;
        }
        return $raw;
    }
    return '{}';
}

function normalizarOC(array $oc): array
{
    $codigo   = $oc['Codigo']    ?? '';
    $tipo     = strtoupper($oc['Tipo'] ?? '');
    $fechas   = $oc['Fechas']    ?? [];
    $fecha    = $fechas['FechaEnvio'] ?? $fechas['FechaCreacion'] ?? '';
    $estado   = $oc['Estado']    ?? '';
    $anio     = (int) substr($fecha, 0, 4);

    // Origen desde sufijo del código (ej: SE26 → SE)
    $origen = TIPO_ORIGEN_MAP[$tipo] ?? inferirOrigenDeCodigo($codigo);

    $comp = $oc['Comprador'] ?? [];
    $prov = $oc['Proveedor'] ?? [];
    return [
        // Identificación
        'codigo_oc'       => $codigo,
        'nombre_oc'       => $oc['Nombre']               ?? '',
        'estado_oc'       => $estado,
        'codigo_estado'   => $oc['CodigoEstado']          ?? '',
        'tipo_oc'         => $tipo,
        'origen_compra'   => $origen,
        'seguimiento'     => calcularSeguimiento($estado, $fecha),
        // Fechas
        'fecha_envio'     => substr($fecha, 0, 10),
        'fecha_creacion'  => substr($fechas['FechaCreacion'] ?? '', 0, 10),
        'fecha_aceptacion'=> substr($fechas['FechaAceptacion'] ?? '', 0, 10),
        'anio'            => $anio > 2000 ? $anio : (int)date('Y'),
        // Montos
        'total_bruto'     => (float)($oc['Total']         ?? 0),
        'total_neto'      => (float)($oc['TotalNeto']     ?? 0),
        'impuestos'       => (float)($oc['Impuestos']     ?? 0),
        'pct_iva'         => (float)($oc['PorcentajeIva'] ?? 0),
        // Comprador (unidad SSO)
        'c_cod_unidad'    => $comp['CodigoUnidad']        ?? '',
        'c_unidad'        => $comp['NombreUnidad']        ?? '',
        'c_rut_unidad'    => $comp['RutUnidad']           ?? '',
        'c_comuna'        => $comp['ComunaUnidad']        ?? '',
        'c_region'        => $comp['RegionUnidad']        ?? '',
        'c_contacto'      => $comp['NombreContacto']      ?? '',
        // Proveedor
        'p_nombre'        => $prov['Nombre']              ?? '',
        'rut_proveedor'   => $prov['RutSucursal']         ?? '',
        'p_comuna'        => $prov['Comuna']              ?? '',
        'p_region'        => $prov['Region']              ?? '',
        // Otros
        'descripcion'     => $oc['Descripcion']           ?? '',
        'cod_licitacion'  => $oc['CodigoLicitacion']      ?? '',
        'forma_pago'      => $oc['FormaPago']             ?? '',
        'financiamiento'  => $oc['Financiamiento']        ?? '',
    ];
}

function normalizarItems(string $codigoOC, array $items): array
{
    $resultado = [];
    foreach ($items as $item) {
        $resultado[] = [
            'correlativo'     => $item['Correlativo']             ?? '',
            'cod_categoria'   => $item['CodigoCategoria']         ?? '',
            'categoria'       => $item['Categoria']               ?? '',
            'cod_producto'    => $item['CodigoProducto']          ?? '',
            'producto'        => $item['Producto']                ?? '',
            'espec_comprador' => $item['EspecificacionComprador'] ?? '',
            'espec_proveedor' => $item['EspecificacionProveedor'] ?? '',
            'cantidad'        => (float)($item['Cantidad']        ?? 0),
            'unidad'          => $item['Unidad']                  ?? '',
            'precio_neto'     => (float)($item['PrecioNeto']      ?? 0),
            'total_impuestos' => (float)($item['TotalImpuestos']  ?? 0),
            'total_linea'     => (float)($item['Total']           ?? 0),
        ];
    }
    return $resultado;
}

function inferirOrigenDeCodigo(string $codigo): string
{
    // Extraer sufijo del código OC: "1057532-234-SE26" → "SE"
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
