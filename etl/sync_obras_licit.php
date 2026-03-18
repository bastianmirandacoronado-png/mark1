<?php
/**
 * sync_obras_licit.php
 * ETL: Consulta API Mercado Público para cada cod_licitacion
 * de las unidades de obras (1057976 y 1180747) y hace UPSERT
 * en la tabla obras_licitaciones.
 *
 * Uso: php etl/sync_obras_licit.php
 */

define('BASE', dirname(__DIR__));
require_once BASE . '/config.php';
require_once BASE . '/api/Mark1Database.php';

set_time_limit(0);

$db  = new Mark1Database();
$pdo = $db->getPDO();

// 1. Obtener todos los cod_licitacion DISTINCT de las unidades de obras
$stmt = $pdo->query("
    SELECT DISTINCT cod_licitacion
    FROM oc_resumen
    WHERE c_cod_unidad IN ('1057976','1180747')
      AND cod_licitacion != ''
      AND cod_licitacion IS NOT NULL
    ORDER BY cod_licitacion
");
$codigos = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Total licitaciones a procesar: " . count($codigos) . "\n";

// 2. Obtener los que ya están en obras_licitaciones
$stmtExist = $pdo->query("SELECT cod_licitacion FROM obras_licitaciones");
$existentes = array_flip($stmtExist->fetchAll(PDO::FETCH_COLUMN));

$pendientes = array_filter($codigos, fn($c) => !isset($existentes[$c]));
$pendientes = array_values($pendientes);

echo "Ya cargados: " . count($existentes) . "\n";
echo "A procesar ahora: " . count($pendientes) . "\n\n";

if (empty($pendientes)) {
    echo "Nada nuevo que procesar. Fin.\n";
    exit(0);
}

// UPSERT preparado
$upsert = $pdo->prepare("
    INSERT INTO obras_licitaciones
        (cod_licitacion, nombre_licitacion, tiempo_duracion, unidad_tiempo,
         fecha_adjudicacion, cod_bip, fuente_financiamiento, monto_estimado,
         fecha_creacion, fecha_cierre, cargado_en)
    VALUES
        (:cod, :nombre, :tiempo, :unidad_tiempo,
         :fecha_adj, :cod_bip, :fuente, :monto,
         :fecha_crea, :fecha_cierre, datetime('now'))
    ON CONFLICT(cod_licitacion) DO UPDATE SET
        nombre_licitacion     = excluded.nombre_licitacion,
        tiempo_duracion       = excluded.tiempo_duracion,
        unidad_tiempo         = excluded.unidad_tiempo,
        fecha_adjudicacion    = excluded.fecha_adjudicacion,
        cod_bip               = excluded.cod_bip,
        fuente_financiamiento = excluded.fuente_financiamiento,
        monto_estimado        = excluded.monto_estimado,
        fecha_creacion        = excluded.fecha_creacion,
        fecha_cierre          = excluded.fecha_cierre,
        cargado_en            = datetime('now')
");

$ticket  = MP_TICKET;
$ok      = 0;
$errores = 0;
$total   = count($pendientes);

foreach ($pendientes as $i => $cod) {
    $n = $i + 1;
    echo "[{$n}/{$total}] Consultando: {$cod} ... ";

    $url = "https://api.mercadopublico.cl/servicios/v1/publico/licitaciones.json"
         . "?ticket={$ticket}&codigo=" . urlencode($cod);

    $ctx = stream_context_create(['http' => [
        'timeout'       => 15,
        'ignore_errors' => true,
    ]]);

    $raw = @file_get_contents($url, false, $ctx);

    if ($raw === false) {
        echo "ERROR (no se pudo conectar)\n";
        $errores++;
        usleep(300000);
        continue;
    }

    // Verificar código HTTP
    $httpCode = 0;
    foreach ($http_response_header as $h) {
        if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $h, $m)) {
            $httpCode = (int)$m[1];
        }
    }

    if ($httpCode !== 200) {
        echo "HTTP {$httpCode} - saltando\n";
        $errores++;
        usleep(300000);
        continue;
    }

    $data = json_decode($raw, true);
    if (!$data || !isset($data['Listado']) || empty($data['Listado'])) {
        echo "Sin datos (respuesta vacía o sin Listado)\n";
        // Insertar con datos vacíos para no volver a consultar
        $upsert->execute([
            ':cod'          => $cod,
            ':nombre'       => null,
            ':tiempo'       => null,
            ':unidad_tiempo'=> null,
            ':fecha_adj'    => null,
            ':cod_bip'      => null,
            ':fuente'       => null,
            ':monto'        => null,
            ':fecha_crea'   => null,
            ':fecha_cierre' => null,
        ]);
        $ok++;
        usleep(300000);
        continue;
    }

    $lic    = $data['Listado'][0];
    $fechas = $lic['Fechas'] ?? [];

    // Extraer FechaAdjudicacion desde el array de Fechas
    $fechaAdj    = null;
    $fechaCrea   = null;
    $fechaCierre = null;
    if (is_array($fechas)) {
        foreach ($fechas as $f) {
            $tipo  = $f['Tipo']  ?? ($f['tipo']  ?? '');
            $valor = $f['Valor'] ?? ($f['valor'] ?? '');
            if (stripos($tipo, 'Adjudicacion') !== false || stripos($tipo, 'Adjudicación') !== false) {
                $fechaAdj = $valor;
            }
            if (stripos($tipo, 'Creacion') !== false || stripos($tipo, 'Creación') !== false) {
                $fechaCrea = $valor;
            }
            if (stripos($tipo, 'Cierre') !== false) {
                $fechaCierre = $valor;
            }
        }
        // Fallback: algunos usan FechaAdjudicacion directamente en el objeto
        if (!$fechaAdj && isset($lic['FechaAdjudicacion'])) {
            $fechaAdj = $lic['FechaAdjudicacion'];
        }
    } else {
        // Algunos usan campos directos
        $fechaAdj    = $lic['FechaAdjudicacion'] ?? null;
        $fechaCrea   = $lic['FechaCreacion']     ?? null;
        $fechaCierre = $lic['FechaCierre']        ?? null;
    }

    $nombre  = $lic['Nombre']                   ?? ($lic['nombre'] ?? null);
    $tiempo  = $lic['TiempoDuracionContrato']    ?? null;
    $uTiempo = $lic['UnidadTiempoDuracionContrato'] ?? null;
    $codBip  = $lic['CodigoBIP']                ?? ($lic['CodigoBip'] ?? null);
    $fuente  = $lic['FuenteFinanciamiento']      ?? null;
    $monto   = $lic['MontoEstimado']             ?? null;
    if ($monto !== null) $monto = (float)$monto;
    if ($tiempo !== null) $tiempo = (int)$tiempo;

    $upsert->execute([
        ':cod'          => $cod,
        ':nombre'       => $nombre,
        ':tiempo'       => $tiempo,
        ':unidad_tiempo'=> $uTiempo,
        ':fecha_adj'    => $fechaAdj,
        ':cod_bip'      => $codBip,
        ':fuente'       => $fuente,
        ':monto'        => $monto,
        ':fecha_crea'   => $fechaCrea,
        ':fecha_cierre' => $fechaCierre,
    ]);

    echo "OK — " . substr($nombre ?? '(sin nombre)', 0, 60) . "\n";
    $ok++;

    usleep(300000); // 300ms entre llamadas
}

echo "\n─────────────────────────────────────────\n";
echo "Procesados: {$ok} OK, {$errores} errores\n";
echo "Total en obras_licitaciones: ";
echo $pdo->query("SELECT COUNT(*) FROM obras_licitaciones")->fetchColumn() . "\n";
