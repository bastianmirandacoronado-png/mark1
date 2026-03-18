<?php
require_once dirname(__DIR__) . '/config.php';
$pdo = new PDO('sqlite:' . DB_PATH);

echo "=== espec_comprador de OCs SE sin cod_licitacion (obras grandes) ===\n\n";
$rows = $pdo->query("
    SELECT r.codigo_oc, r.nombre_oc, r.p_nombre, r.fecha_envio,
           r.total_bruto, r.estado_oc, r.anio, r.cod_licitacion,
           d.espec_comprador
    FROM oc_resumen r
    JOIN oc_detalles d ON r.codigo_oc = d.codigo_oc
    WHERE r.c_cod_unidad IN ('1057976','1180747')
      AND (r.cod_licitacion IS NULL OR r.cod_licitacion = '')
      AND r.tipo_oc LIKE '%SE%'
      AND r.total_bruto > 50000000
    GROUP BY r.codigo_oc
    ORDER BY r.total_bruto DESC
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    printf("OC: %s | %d | \$%s\n",
        $r['codigo_oc'], $r['anio'],
        number_format($r['total_bruto'],0,',','.'));
    printf("nombre_oc    : %s\n", $r['nombre_oc']);
    printf("espec_comprad: %s\n", $r['espec_comprador']);
    printf("proveedor    : %s | %s\n\n", $r['p_nombre'], $r['estado_oc']);
}

echo "\n=== Muestra espec_comprador TODAS las obras (patrones) ===\n\n";
$rows = $pdo->query("
    SELECT r.codigo_oc, r.anio, r.total_bruto, d.espec_comprador
    FROM oc_resumen r
    JOIN oc_detalles d ON r.codigo_oc = d.codigo_oc
    WHERE r.c_cod_unidad IN ('1057976','1180747')
    GROUP BY r.codigo_oc
    ORDER BY r.total_bruto DESC LIMIT 30
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    printf("[%s|%d|\$%s] %s\n",
        $r['codigo_oc'], $r['anio'],
        number_format($r['total_bruto'],0,',','.'),
        substr($r['espec_comprador'], 0, 200));
}

echo "\n=== ¿Cuántos OCs SE tienen espec con 'Res.' o 'Resolución'? ===\n";
$rows = $pdo->query("
    SELECT COUNT(DISTINCT r.codigo_oc) as n
    FROM oc_resumen r JOIN oc_detalles d ON r.codigo_oc=d.codigo_oc
    WHERE r.c_cod_unidad IN ('1057976','1180747')
      AND (LOWER(d.espec_comprador) LIKE '%res.%'
        OR LOWER(d.espec_comprador) LIKE '%resoluci%'
        OR LOWER(d.espec_comprador) LIKE '%construcci%'
        OR LOWER(d.espec_comprador) LIKE '%cesfam%'
        OR LOWER(d.espec_comprador) LIKE '%cecosf%'
        OR LOWER(d.espec_comprador) LIKE '%hospital%'
        OR LOWER(d.espec_comprador) LIKE '%rass%'
        OR LOWER(d.espec_comprador) LIKE '%reposi%')
")->fetchAll(PDO::FETCH_ASSOC);
echo "  OCs con nombre de obra en espec_comprador: ".$rows[0]['n']."\n";
