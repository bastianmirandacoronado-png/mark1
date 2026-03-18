<?php
require_once dirname(__DIR__) . '/config.php';
$pdo = new PDO('sqlite:' . DB_PATH);

echo "=== Columnas obras_licitaciones ===\n";
$cols = $pdo->query('PRAGMA table_info(obras_licitaciones)')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo '  '.$c['name'].' ('.$c['type'].")\n";

echo "\n=== Muestra datos (plazo) ===\n";
$rows = $pdo->query('SELECT cod_licitacion, tiempo_duracion, unidad_tiempo, fecha_adjudicacion, cod_bip, fuente_financiamiento FROM obras_licitaciones LIMIT 8')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    printf("  %s | Plazo: %s %s | Adjudic: %s | BIP: %s\n",
        $r['cod_licitacion'], $r['tiempo_duracion'], $r['unidad_tiempo'],
        $r['fecha_adjudicacion'], $r['cod_bip']);
}

echo "\n=== Distribución unidad_tiempo ===\n";
$rows = $pdo->query('SELECT unidad_tiempo, COUNT(*) as n FROM obras_licitaciones GROUP BY unidad_tiempo')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo '  ['.$r['unidad_tiempo'].'] -> '.$r['n']." registros\n";

echo "\n=== OCs activas CON ficha licitacion ===\n";
$rows = $pdo->query("
    SELECT r.codigo_oc, substr(r.nombre_oc,0,55) as nombre,
           r.fecha_envio, r.fecha_aceptacion, r.total_bruto,
           ol.tiempo_duracion, ol.unidad_tiempo, ol.fecha_adjudicacion, ol.cod_bip
    FROM oc_resumen r
    JOIN obras_licitaciones ol ON r.cod_licitacion = ol.cod_licitacion
    WHERE r.c_cod_unidad IN ('1057976','1180747')
      AND r.estado_oc = 'Aceptada'
    ORDER BY r.total_bruto DESC
")->fetchAll(PDO::FETCH_ASSOC);
echo "  Total con ficha: ".count($rows)."\n\n";
foreach ($rows as $r) {
    $inicio = $r['fecha_aceptacion'] ?? $r['fecha_envio'];
    $plazo_dias = null;
    $ut = strtolower($r['unidad_tiempo'] ?? '');
    if (str_contains($ut,'d')) $plazo_dias = (int)$r['tiempo_duracion'];
    elseif (str_contains($ut,'m')) $plazo_dias = (int)$r['tiempo_duracion'] * 30;
    $dias_trans = $inicio ? (int)((time() - strtotime($inicio)) / 86400) : null;
    $pct = ($plazo_dias && $dias_trans) ? round($dias_trans/$plazo_dias*100) : null;
    printf("  %s | %s\n  Plazo: %s %s | Inicio: %s | Transcurridos: %s días | Avance: %s%%\n\n",
        $r['codigo_oc'], $r['nombre'],
        $r['tiempo_duracion'], $r['unidad_tiempo'],
        $inicio ?? '—', $dias_trans ?? '?', $pct ?? '?');
}

echo "\n=== OCs activas SIN ficha (SE / Trato Directo) ===\n";
$rows2 = $pdo->query("
    SELECT r.codigo_oc, substr(r.nombre_oc,0,55) as nombre, r.fecha_aceptacion, r.fecha_envio, r.total_bruto,
           substr(d.espec_comprador,0,300) as espec
    FROM oc_resumen r
    LEFT JOIN obras_licitaciones ol ON r.cod_licitacion = ol.cod_licitacion
    LEFT JOIN oc_detalles d ON r.codigo_oc = d.codigo_oc
    WHERE r.c_cod_unidad IN ('1057976','1180747')
      AND r.estado_oc = 'Aceptada'
      AND ol.cod_licitacion IS NULL
    GROUP BY r.codigo_oc
    ORDER BY r.total_bruto DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
echo "  Total sin ficha: ".count($rows2)." (mostrando top 10)\n\n";
foreach ($rows2 as $r) {
    preg_match('/(\d+)\s*(d[ií]as?|meses?|semanas?)/i', $r['espec'], $m);
    $plazo_txt = $m[0] ?? 'no encontrado';
    $inicio = $r['fecha_aceptacion'] ?? $r['fecha_envio'];
    $dias_trans = $inicio ? (int)((time() - strtotime($inicio)) / 86400) : null;
    printf("  %s | %s | $%s | inicio: %s | %d días\n  Plazo en espec: %s\n\n",
        $r['codigo_oc'], $r['nombre'],
        number_format($r['total_bruto'],0,',','.'), $inicio, $dias_trans ?? 0, $plazo_txt);
}
