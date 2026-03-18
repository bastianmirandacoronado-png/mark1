<?php
/**
 * sync_obras_plazo.php
 * ETL: Para OCs activas de obras (1057976/1180747) que no tienen plazo
 * en obras_licitaciones, extrae el código de licitación referenciado
 * en nombre_oc o espec_comprador y consulta la API de Mercado Público.
 *
 * Estrategias en orden:
 *   1. cod_licitacion propio de la OC (ya debería estar en obras_licitaciones)
 *   2. Código LR/LE/LP/LQ/LS extraído del nombre_oc con regex
 *   3. Código extraído del espec_comprador con regex
 *
 * Uso: php etl/sync_obras_plazo.php [--forzar]
 */

define('BASE', dirname(__DIR__));
require_once BASE . '/config.php';
require_once BASE . '/api/Mark1Database.php';

set_time_limit(0);

$forzar = in_array('--forzar', $argv);

$db  = new Mark1Database();
$pdo = $db->getPDO();

echo "=== sync_obras_plazo.php ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// Añadir columna ref_licitacion si no existe (para registrar qué código usamos)
try {
    $pdo->exec("ALTER TABLE obras_licitaciones ADD COLUMN ref_codigo TEXT");
    echo "Columna ref_codigo agregada a obras_licitaciones\n";
} catch (Exception $e) { /* ya existe */ }

// Patrón regex para extraer códigos de licitación del texto
// Matches: 1057976-19-LR22, 1180747-4-LR23, 1057976-3-LR19, etc.
$patron = '/\b(\d{7}-\d+-[A-Z]{2,3}\d+)\b/';

// Obtener todas las OCs activas con info de espec
$ocs = $pdo->query("
    SELECT
        r.codigo_oc,
        r.nombre_oc,
        r.cod_licitacion,
        r.fecha_envio,
        r.fecha_aceptacion,
        r.total_bruto,
        r.c_cod_unidad,
        COALESCE(d.espec_comprador,'') AS espec
    FROM oc_resumen r
    LEFT JOIN oc_detalles d ON d.codigo_oc = r.codigo_oc AND d.correlativo = 1
    WHERE r.c_cod_unidad IN ('1057976','1180747')
      AND r.estado_oc IN ('Aceptada','Enviada a proveedor')
    ORDER BY r.total_bruto DESC
")->fetchAll(PDO::FETCH_ASSOC);

echo "OCs activas encontradas: " . count($ocs) . "\n\n";

// Obtener licitaciones ya en la tabla
$existentes = $pdo->query("SELECT cod_licitacion, tiempo_duracion, unidad_tiempo FROM obras_licitaciones")
                  ->fetchAll(PDO::FETCH_ASSOC);
$existMap = [];
foreach ($existentes as $e) {
    $existMap[$e['cod_licitacion']] = $e;
}

// UPSERT preparado
$upsert = $pdo->prepare("
    INSERT INTO obras_licitaciones
        (cod_licitacion, nombre_licitacion, tiempo_duracion, unidad_tiempo,
         fecha_adjudicacion, cod_bip, fuente_financiamiento, monto_estimado,
         fecha_creacion, fecha_cierre, ref_codigo, cargado_en)
    VALUES
        (:cod, :nombre, :tiempo, :unidad_tiempo,
         :fecha_adj, :cod_bip, :fuente, :monto,
         :fecha_crea, :fecha_cierre, :ref_codigo, datetime('now'))
    ON CONFLICT(cod_licitacion) DO UPDATE SET
        nombre_licitacion     = excluded.nombre_licitacion,
        tiempo_duracion       = CASE WHEN excluded.tiempo_duracion > 0
                                     THEN excluded.tiempo_duracion
                                     ELSE obras_licitaciones.tiempo_duracion END,
        unidad_tiempo         = CASE WHEN excluded.tiempo_duracion > 0
                                     THEN excluded.unidad_tiempo
                                     ELSE obras_licitaciones.unidad_tiempo END,
        fecha_adjudicacion    = COALESCE(excluded.fecha_adjudicacion, obras_licitaciones.fecha_adjudicacion),
        cod_bip               = COALESCE(excluded.cod_bip, obras_licitaciones.cod_bip),
        fuente_financiamiento = COALESCE(excluded.fuente_financiamiento, obras_licitaciones.fuente_financiamiento),
        monto_estimado        = COALESCE(excluded.monto_estimado, obras_licitaciones.monto_estimado),
        ref_codigo            = excluded.ref_codigo,
        cargado_en            = datetime('now')
");

// Agregar columna oc_licit_ref en oc_resumen para guardar el cod_licitacion resuelto
try {
    $pdo->exec("ALTER TABLE oc_resumen ADD COLUMN cod_licit_resuelto TEXT");
    echo "Columna cod_licit_resuelto agregada a oc_resumen\n";
} catch (Exception $e) { /* ya existe */ }

$ticket    = MP_TICKET;
$procesado = 0;
$nuevo     = 0;
$sinDatos  = 0;

foreach ($ocs as $oc) {
    $codigo_oc = $oc['codigo_oc'];

    // Estrategia 1: cod_licitacion propio
    $codLicit = $oc['cod_licitacion'] ?? '';

    // ¿Ya tiene datos útiles?
    $tienePlazo = false;
    if ($codLicit && isset($existMap[$codLicit])) {
        $e = $existMap[$codLicit];
        if ((int)($e['tiempo_duracion'] ?? 0) > 0) {
            $tienePlazo = true;
            // Registrar el codigo resuelto
            $pdo->prepare("UPDATE oc_resumen SET cod_licit_resuelto=? WHERE codigo_oc=?")
                ->execute([$codLicit, $codigo_oc]);
        }
    }

    if ($tienePlazo && !$forzar) {
        echo "  [OK-skip] $codigo_oc → $codLicit ya tiene plazo\n";
        continue;
    }

    // Estrategia 2 & 3: extraer del nombre_oc o espec
    $textosBuscar = [$oc['nombre_oc'], $oc['espec']];
    $codExtraido  = null;

    foreach ($textosBuscar as $texto) {
        if (preg_match($patron, $texto, $m)) {
            $candidato = $m[1];
            // Que no sea el propio código de la OC y que sea de obra (1057976 o 1180747)
            // Aceptar cualquier código de licitación encontrado
            if ($candidato !== $codigo_oc) {
                $codExtraido = $candidato;
                break;
            }
        }
    }

    // Decidir qué código consultar
    $codConsultar = $codExtraido ?? ($codLicit ?: null);

    if (!$codConsultar) {
        echo "  [SIN-COD] $codigo_oc | " . substr($oc['nombre_oc'], 0, 50) . "\n";
        $sinDatos++;
        continue;
    }

    // ¿Ya está en la tabla con datos válidos?
    if (!$forzar && isset($existMap[$codConsultar]) && (int)($existMap[$codConsultar]['tiempo_duracion'] ?? 0) > 0) {
        echo "  [CACHED] $codigo_oc → $codConsultar (plazo ya en BD)\n";
        $pdo->prepare("UPDATE oc_resumen SET cod_licit_resuelto=? WHERE codigo_oc=?")
            ->execute([$codConsultar, $codigo_oc]);
        continue;
    }

    // Consultar API
    $url = "https://api.mercadopublico.cl/servicios/v1/publico/licitaciones.json"
         . "?ticket={$ticket}&codigo=" . urlencode($codConsultar);

    echo "  [API] $codigo_oc → consultando $codConsultar ... ";
    $ctx = stream_context_create(['http' => ['timeout' => 15, 'ignore_errors' => true]]);
    $raw = @file_get_contents($url, false, $ctx);

    $httpCode = 0;
    foreach (($http_response_header ?? []) as $h) {
        if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $h, $m)) $httpCode = (int)$m[1];
    }

    if ($raw === false || $httpCode !== 200) {
        echo "HTTP $httpCode - error\n";
        $sinDatos++;
        usleep(400000);
        continue;
    }

    $data = json_decode($raw, true);
    if (!$data || !isset($data['Listado']) || empty($data['Listado'])) {
        echo "sin datos en API\n";
        $sinDatos++;
        usleep(400000);
        continue;
    }

    $lic    = $data['Listado'][0];
    $fechas = $lic['Fechas'] ?? [];

    $fechaAdj = $fechaCrea = $fechaCierre = null;
    if (is_array($fechas)) {
        foreach ($fechas as $f) {
            $tipo  = $f['Tipo']  ?? ($f['tipo']  ?? '');
            $valor = $f['Valor'] ?? ($f['valor'] ?? '');
            if (stripos($tipo, 'djudicac') !== false) $fechaAdj    = $valor;
            if (stripos($tipo, 'reac')      !== false) $fechaCrea   = $valor;
            if (stripos($tipo, 'ierre')     !== false) $fechaCierre = $valor;
        }
        if (!$fechaAdj && isset($lic['FechaAdjudicacion'])) $fechaAdj = $lic['FechaAdjudicacion'];
    } else {
        $fechaAdj    = $lic['FechaAdjudicacion'] ?? null;
        $fechaCrea   = $lic['FechaCreacion']     ?? null;
        $fechaCierre = $lic['FechaCierre']        ?? null;
    }

    $nombre  = $lic['Nombre']                        ?? null;
    $tiempo  = (int)($lic['TiempoDuracionContrato']  ?? 0);
    $uTiempo = $lic['UnidadTiempoDuracionContrato']  ?? null;
    $codBip  = $lic['CodigoBIP']                     ?? ($lic['CodigoBip'] ?? null);
    $fuente  = $lic['FuenteFinanciamiento']           ?? null;
    $monto   = isset($lic['MontoEstimado']) ? (float)$lic['MontoEstimado'] : null;

    $upsert->execute([
        ':cod'          => $codConsultar,
        ':nombre'       => $nombre,
        ':tiempo'       => $tiempo,
        ':unidad_tiempo'=> $uTiempo,
        ':fecha_adj'    => $fechaAdj,
        ':cod_bip'      => $codBip,
        ':fuente'       => $fuente,
        ':monto'        => $monto,
        ':fecha_crea'   => $fechaCrea,
        ':fecha_cierre' => $fechaCierre,
        ':ref_codigo'   => $codExtraido ? $codigo_oc : null,
    ]);

    // Actualizar oc_resumen con el código resuelto
    $pdo->prepare("UPDATE oc_resumen SET cod_licit_resuelto=? WHERE codigo_oc=?")
        ->execute([$codConsultar, $codigo_oc]);

    // Actualizar el mapa local
    $existMap[$codConsultar] = ['tiempo_duracion' => $tiempo, 'unidad_tiempo' => $uTiempo];

    echo "OK | Plazo: $tiempo (unidad: $uTiempo) | BIP: " . ($codBip ?: '—') . "\n";
    $nuevo++;
    $procesado++;
    usleep(400000);
}

echo "\n────────────────────────────────\n";
echo "Procesados: $procesado | Nuevos/actualizados: $nuevo | Sin datos: $sinDatos\n";
echo "Total en obras_licitaciones: " . $pdo->query("SELECT COUNT(*) FROM obras_licitaciones")->fetchColumn() . "\n";

// Resumen final: OCs activas con plazo resuelto
echo "\n=== Resumen plazos OCs activas ===\n";
$res = $pdo->query("
    SELECT r.codigo_oc, substr(r.nombre_oc,0,50) as nombre,
           COALESCE(r.cod_licit_resuelto, r.cod_licitacion) as licit_ref,
           ol.tiempo_duracion, ol.unidad_tiempo,
           r.fecha_aceptacion, r.total_bruto
    FROM oc_resumen r
    LEFT JOIN obras_licitaciones ol
        ON ol.cod_licitacion = COALESCE(r.cod_licit_resuelto, r.cod_licitacion)
    WHERE r.c_cod_unidad IN ('1057976','1180747')
      AND r.estado_oc = 'Aceptada'
    ORDER BY r.total_bruto DESC
")->fetchAll(PDO::FETCH_ASSOC);

$conPlazo = 0; $sinPlazo = 0;
foreach ($res as $r) {
    $tiene = ($r['tiempo_duracion'] ?? 0) > 0;
    if ($tiene) $conPlazo++; else $sinPlazo++;
    $icon = $tiene ? '✓' : '✗';
    $plazo = $tiene ? $r['tiempo_duracion'].' (u:'.$r['unidad_tiempo'].')' : 'sin datos';
    printf("  %s %s | %s | plazo: %s\n",
        $icon, $r['codigo_oc'], substr($r['nombre'],0,45), $plazo);
}
echo "\nCon plazo: $conPlazo | Sin plazo: $sinPlazo\n";
