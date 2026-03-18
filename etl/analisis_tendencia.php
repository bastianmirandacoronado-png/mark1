<?php
require_once dirname(__DIR__) . '/config.php';
$pdo = new PDO('sqlite:' . DB_PATH);

echo "=== Promedio mensual histórico AG (2021-2025) ===\n";
$rows = $pdo->query("
    SELECT mes, ROUND(AVG(cnt),1) AS prom_ocs, ROUND(AVG(monto)/1e6,2) AS prom_M
    FROM (
        SELECT anio, strftime('%m', fecha_envio) AS mes, COUNT(*) AS cnt, SUM(total_bruto) AS monto
        FROM oc_resumen
        WHERE c_cod_unidad='1057922' AND origen_compra='Compra Ágil'
          AND anio BETWEEN 2021 AND 2025
          AND fecha_envio IS NOT NULL AND fecha_envio != ''
        GROUP BY anio, strftime('%m', fecha_envio)
    ) GROUP BY mes ORDER BY mes
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r)
    printf("  Mes %s: prom %.1f OCs / \$%.2fM\n", $r['mes'], $r['prom_ocs'], $r['prom_M']);

echo "\n=== Ritmo acum. ene-mar: 2024/2025/2026 ===\n";
$rows = $pdo->query("
    SELECT anio, COUNT(*) AS ocs, ROUND(SUM(total_bruto)/1e6,2) AS monto_M,
           ROUND(COUNT(*)/3.0,1) AS ritmo,
           ROUND(COUNT(*)/3.0*12) AS proy_anual
    FROM oc_resumen
    WHERE c_cod_unidad='1057922' AND origen_compra='Compra Ágil'
      AND strftime('%m', fecha_envio) IN ('01','02','03')
      AND anio IN (2024,2025,2026) AND fecha_envio IS NOT NULL
    GROUP BY anio ORDER BY anio
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r)
    printf("  %d: %d OCs | \$%.2fM | ritmo %.1f/mes | proy.anual ~%.0f\n",
        $r['anio'], $r['ocs'], $r['monto_M'], $r['ritmo'], $r['proy_anual']);

echo "\n=== Velocidad: últ.3 meses vs 3 meses previos (AG) ===\n";
$rows = $pdo->query("
    SELECT
        SUM(CASE WHEN fecha_envio >= date('now','-3 months') THEN 1 ELSE 0 END) AS ult3,
        SUM(CASE WHEN fecha_envio >= date('now','-6 months') AND fecha_envio < date('now','-3 months') THEN 1 ELSE 0 END) AS prev3,
        ROUND(SUM(CASE WHEN fecha_envio >= date('now','-3 months') THEN total_bruto ELSE 0 END)/1e6,2) AS monto_ult3,
        ROUND(SUM(CASE WHEN fecha_envio >= date('now','-6 months') AND fecha_envio < date('now','-3 months') THEN total_bruto ELSE 0 END)/1e6,2) AS monto_prev3
    FROM oc_resumen
    WHERE c_cod_unidad='1057922' AND origen_compra='Compra Ágil' AND fecha_envio IS NOT NULL
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r)
    printf("  Últ.3m: %d OCs / \$%.2fM  |  Prev.3m: %d OCs / \$%.2fM  |  Var: %+.1f%%\n",
        $r['ult3'], $r['monto_ult3'], $r['prev3'], $r['monto_prev3'],
        $r['prev3'] > 0 ? ($r['ult3']-$r['prev3'])/$r['prev3']*100 : 0);

echo "\n=== YTD 2026 vs mismo periodo año anterior ===\n";
$rows = $pdo->query("
    SELECT anio, COUNT(*) AS n, ROUND(SUM(total_bruto)/1e6,2) AS monto_M
    FROM oc_resumen
    WHERE c_cod_unidad='1057922' AND origen_compra='Compra Ágil'
      AND strftime('%m-%d', fecha_envio) <= strftime('%m-%d','now')
      AND anio IN (2025,2026) AND fecha_envio IS NOT NULL
    GROUP BY anio ORDER BY anio
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r)
    printf("  %d YTD: %d OCs / \$%.2fM\n", $r['anio'], $r['n'], $r['monto_M']);

echo "\n=== Proyección fin de año 2026 (ritmo lineal YTD) ===\n";
$rows = $pdo->query("
    SELECT COUNT(*) AS ocs_ytd, ROUND(SUM(total_bruto)/1e6,2) AS monto_ytd,
           CAST(strftime('%m','now') AS INTEGER) AS mes_act
    FROM oc_resumen
    WHERE c_cod_unidad='1057922' AND origen_compra='Compra Ágil'
      AND anio=2026 AND fecha_envio IS NOT NULL
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $proy_ocs   = round($r['ocs_ytd']  / max($r['mes_act'],1) * 12);
    $proy_monto = round($r['monto_ytd'] / max($r['mes_act'],1) * 12, 2);
    printf("  YTD: %d OCs / \$%.2fM en %d meses → Proy.dic: ~%d OCs / ~\$%.2fM\n",
        $r['ocs_ytd'], $r['monto_ytd'], $r['mes_act'], $proy_ocs, $proy_monto);
}

echo "\n=== Mes pico histórico por año ===\n";
$rows = $pdo->query("
    SELECT anio, strftime('%m', fecha_envio) AS mes, COUNT(*) AS n
    FROM oc_resumen
    WHERE c_cod_unidad='1057922' AND origen_compra='Compra Ágil'
      AND fecha_envio IS NOT NULL AND anio >= 2022
    GROUP BY anio, strftime('%m', fecha_envio)
    HAVING n = (
        SELECT MAX(cnt) FROM (
            SELECT COUNT(*) AS cnt FROM oc_resumen
            WHERE c_cod_unidad='1057922' AND origen_compra='Compra Ágil'
              AND fecha_envio IS NOT NULL
              AND anio = r2.anio
            GROUP BY strftime('%m', fecha_envio)
        )
    )
    ORDER BY anio
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) { // alias workaround
}
// simpler version
$rows = $pdo->query("
    SELECT anio, strftime('%m', fecha_envio) AS mes_pico, MAX(cnt) AS n_pico
    FROM (
        SELECT anio, strftime('%m', fecha_envio) AS mes, COUNT(*) AS cnt
        FROM oc_resumen
        WHERE c_cod_unidad='1057922' AND origen_compra='Compra Ágil'
          AND fecha_envio IS NOT NULL AND anio >= 2022
        GROUP BY anio, strftime('%m', fecha_envio)
    )
    GROUP BY anio ORDER BY anio
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r)
    printf("  %d: pico mes %s con %d OCs\n", $r['anio'], $r['mes_pico'], $r['n_pico']);
