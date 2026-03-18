<?php
require_once dirname(__DIR__) . '/config.php';
$pdo = new PDO('sqlite:' . DB_PATH);

echo "=== Columnas oc_resumen ===\n";
$cols = $pdo->query("PRAGMA table_info(oc_resumen)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  ".$c['name']." (".$c['type'].")\n";

echo "\n=== Columnas oc_detalles ===\n";
$cols = $pdo->query("PRAGMA table_info(oc_detalles)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  ".$c['name']." (".$c['type'].")\n";

echo "\n=== Muestra OCs obras (top por monto) ===\n";
$rows = $pdo->query("SELECT codigo_oc, cod_licitacion, nombre_oc, p_nombre,
        fecha_envio, total_bruto, estado_oc, anio
    FROM oc_resumen WHERE c_cod_unidad IN ('1057976','1180747')
    AND total_bruto > 0 ORDER BY total_bruto DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    printf("  [%s] Lic: %s\n  Nombre: %s\n  Proveedor: %s\n  Envío: %s | \$%s | Estado: %s\n\n",
        $r['codigo_oc'], $r['cod_licitacion'] ?? 'N/A',
        substr($r['nombre_oc'],0,70),
        substr($r['p_nombre'],0,50),
        $r['fecha_envio'] ?? '—',
        number_format($r['total_bruto'],0,',','.'), $r['estado_oc']);
}

echo "\n=== Muestra oc_detalles para obras ===\n";
$rows = $pdo->query("SELECT d.* FROM oc_detalles d
    JOIN oc_resumen r ON d.codigo_oc = r.codigo_oc
    WHERE r.c_cod_unidad IN ('1057976','1180747') LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) print_r($r);

echo "\n=== Distribución estado_oc obras ===\n";
$rows = $pdo->query("SELECT estado_oc, COUNT(*) as n, ROUND(SUM(total_bruto)/1e6,1) as monto_M
    FROM oc_resumen WHERE c_cod_unidad IN ('1057976','1180747')
    GROUP BY estado_oc ORDER BY monto_M DESC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r)
    printf("  %-30s %3d OCs  \$%.1fM\n", $r['estado_oc'], $r['n'], $r['monto_M']);
