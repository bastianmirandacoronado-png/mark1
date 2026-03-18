<?php
require_once dirname(__DIR__) . '/config.php';
$pdo = new PDO('sqlite:' . DB_PATH);

echo "=== Plazos en espec_comprador (obras) ===\n";
$rows = $pdo->query("
    SELECT r.codigo_oc, r.nombre_oc, r.p_nombre, r.fecha_envio, r.fecha_aceptacion,
           r.total_bruto, r.estado_oc, d.espec_comprador
    FROM oc_resumen r
    JOIN oc_detalles d ON r.codigo_oc = d.codigo_oc
    WHERE r.c_cod_unidad IN ('1057976','1180747')
      AND (LOWER(d.espec_comprador) LIKE '%plazo%'
        OR LOWER(d.espec_comprador) LIKE '%días%'
        OR LOWER(d.espec_comprador) LIKE '%dias%'
        OR LOWER(d.espec_comprador) LIKE '%meses%'
        OR LOWER(d.espec_comprador) LIKE '%semanas%')
    GROUP BY r.codigo_oc
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    // Extract plazo with regex patterns
    preg_match('/(\d+)\s*(d[ií]as?|meses?|semanas?|corridos|h[áa]biles?)/i', $r['espec_comprador'], $m);
    $plazo = $m[0] ?? '?';
    printf("  [%s] %s\n  Plazo encontrado: %-25s | \$%s | %s\n\n",
        $r['codigo_oc'], substr($r['nombre_oc'],0,60),
        $plazo, number_format($r['total_bruto'],0,',','.'), $r['estado_oc']);
}

echo "\n=== Tiempo envío→aceptación obras (días) ===\n";
$rows = $pdo->query("
    SELECT codigo_oc, nombre_oc, p_nombre, fecha_envio, fecha_aceptacion, total_bruto, estado_oc,
        CAST(julianday(fecha_aceptacion) - julianday(fecha_envio) AS INTEGER) AS dias_tramite
    FROM oc_resumen
    WHERE c_cod_unidad IN ('1057976','1180747')
      AND fecha_aceptacion IS NOT NULL AND fecha_aceptacion != ''
      AND fecha_envio IS NOT NULL AND fecha_envio != ''
    ORDER BY total_bruto DESC LIMIT 15
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r)
    printf("  %-55s | %3d días trámite | \$%s | %s\n",
        substr($r['nombre_oc'],0,55), $r['dias_tramite'] ?? 0,
        number_format($r['total_bruto'],0,',','.'), $r['estado_oc']);

echo "\n=== Resumen estadístico plazos trámite ===\n";
$rows = $pdo->query("
    SELECT
        COUNT(*) as n,
        ROUND(AVG(julianday(fecha_aceptacion) - julianday(fecha_envio))) AS prom_dias,
        MIN(CAST(julianday(fecha_aceptacion) - julianday(fecha_envio) AS INTEGER)) AS min_dias,
        MAX(CAST(julianday(fecha_aceptacion) - julianday(fecha_envio) AS INTEGER)) AS max_dias
    FROM oc_resumen
    WHERE c_cod_unidad IN ('1057976','1180747')
      AND fecha_aceptacion IS NOT NULL AND fecha_aceptacion != ''
      AND fecha_envio IS NOT NULL AND fecha_envio != ''
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r)
    printf("  N=%d | Prom: %d días | Min: %d | Max: %d\n",
        $r['n'], $r['prom_dias'], $r['min_dias'], $r['max_dias']);

echo "\n=== Verificar API MP para licitación obras ===\n";
$ticket = MP_TICKET;
$cod = '1057976-19-LR22'; // CESFAM Chuyaca - obra grande
$url = "https://api.mercadopublico.cl/servicios/v1/publico/licitaciones.json?ticket={$ticket}&codigo={$cod}";
echo "  Consultando: $url\n";
$ctx = stream_context_create(['http'=>['timeout'=>15,'ignore_errors'=>true]]);
$resp = @file_get_contents($url, false, $ctx);
if ($resp) {
    $data = json_decode($resp, true);
    if (isset($data['Listado'][0])) {
        $lit = $data['Listado'][0];
        echo "  Nombre: ".($lit['Nombre']??'N/A')."\n";
        echo "  Estado: ".($lit['CodigoEstado']??'N/A')." ".(($lit['Descripcion']??'')?:'')."\n";
        echo "  FechaCreacion: ".($lit['Fechas']['FechaCreacion']??'N/A')."\n";
        echo "  FechaCierre:   ".($lit['Fechas']['FechaCierre']??'N/A')."\n";
        echo "  FechaAdjudicacion: ".($lit['Fechas']['FechaAdjudicacion']??'N/A')."\n";
        echo "  Campos disponibles en Fechas: ".implode(', ', array_keys($lit['Fechas']??[]))."\n";
        echo "  Campos top-level: ".implode(', ', array_keys($lit))."\n";
    } else {
        echo "  Respuesta: ".substr($resp,0,300)."\n";
    }
} else {
    echo "  Sin respuesta del API\n";
}
