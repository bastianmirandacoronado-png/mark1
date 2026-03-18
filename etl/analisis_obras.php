<?php
require_once dirname(__DIR__) . '/config.php';
$pdo = new PDO('sqlite:' . DB_PATH);

echo "=== Unidades disponibles en oc_resumen ===\n";
$rows = $pdo->query("SELECT c_cod_unidad, c_unidad, COUNT(*) as n, MIN(anio) as desde, MAX(anio) as hasta
    FROM oc_resumen GROUP BY c_cod_unidad ORDER BY n DESC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r)
    printf("  %-12s %-45s %4d OCs  (%d-%d)\n", $r['c_cod_unidad'], substr($r['c_unidad'],0,45), $r['n'], $r['desde'], $r['hasta']);

echo "\n=== OCs unidad 1057976 (Obras) ===\n";
$rows = $pdo->query("SELECT anio, origen_compra, estado_oc, COUNT(*) as n, ROUND(SUM(total_bruto)/1e6,1) as monto_M
    FROM oc_resumen WHERE c_cod_unidad='1057976'
    GROUP BY anio, origen_compra, estado_oc ORDER BY anio DESC, monto_M DESC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r)
    printf("  %d | %-30s | %-25s | %3d OCs | \$%.1fM\n", $r['anio'], $r['origen_compra'], $r['estado_oc'], $r['n'], $r['monto_M']);

echo "\n=== OCs unidad 1180747 (GORE) ===\n";
$rows = $pdo->query("SELECT anio, origen_compra, estado_oc, COUNT(*) as n, ROUND(SUM(total_bruto)/1e6,1) as monto_M
    FROM oc_resumen WHERE c_cod_unidad='1180747'
    GROUP BY anio, origen_compra, estado_oc ORDER BY anio DESC, monto_M DESC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r)
    printf("  %d | %-30s | %-25s | %3d OCs | \$%.1fM\n", $r['anio'], $r['origen_compra'], $r['estado_oc'], $r['n'], $r['monto_M']);

echo "\n=== Top 15 proveedores obras (1057976 + 1180747) ===\n";
$rows = $pdo->query("SELECT p_nombre, COUNT(*) as n, ROUND(SUM(total_bruto)/1e6,1) as monto_M,
        MIN(anio) as desde, MAX(anio) as hasta
    FROM oc_resumen WHERE c_cod_unidad IN ('1057976','1180747') AND p_nombre != ''
    GROUP BY p_nombre ORDER BY monto_M DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r)
    printf("  %-50s %3d OCs  \$%.1fM  (%d-%d)\n", substr($r['p_nombre'],0,50), $r['n'], $r['monto_M'], $r['desde'], $r['hasta']);

echo "\n=== Montos totales por año (ambas unidades) ===\n";
$rows = $pdo->query("SELECT anio, c_cod_unidad, COUNT(*) as n,
        ROUND(SUM(total_bruto)/1e6,1) as monto_M
    FROM oc_resumen WHERE c_cod_unidad IN ('1057976','1180747')
    GROUP BY anio, c_cod_unidad ORDER BY anio DESC, c_cod_unidad")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r)
    printf("  %d | %-12s | %3d OCs | \$%.1fM\n", $r['anio'], $r['c_cod_unidad'], $r['n'], $r['monto_M']);

echo "\n=== Muestras de OCs obras (nombre, cod_licitacion, fechas) ===\n";
$rows = $pdo->query("SELECT codigo_oc, cod_licitacion, nombre_oc, p_nombre,
        fecha_envio, fecha_entrega, total_bruto, estado_oc, anio
    FROM oc_resumen WHERE c_cod_unidad IN ('1057976','1180747')
    AND total_bruto > 0 ORDER BY total_bruto DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r)
    printf("  %s | Lic:%s | %s\n    Prov: %-40s | Envío:%s | Entrega:%s | \$%s | %s\n",
        $r['codigo_oc'], $r['cod_licitacion'] ?? 'N/A',
        substr($r['nombre_oc'],0,60),
        substr($r['p_nombre'],0,40),
        $r['fecha_envio'] ?? '—', $r['fecha_entrega'] ?? '—',
        number_format($r['total_bruto'],0,',','.'), $r['estado_oc']);

echo "\n=== Columnas disponibles en oc_resumen ===\n";
$cols = $pdo->query("PRAGMA table_info(oc_resumen)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  ".$c['name']." (".$c['type'].")\n";

echo "\n=== Columnas disponibles en oc_detalles ===\n";
$cols = $pdo->query("PRAGMA table_info(oc_detalles)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  ".$c['name']." (".$c['type'].")\n";
