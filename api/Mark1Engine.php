<?php
/**
 * Mark1Engine.php
 * ─────────────────────────────────────────────────────────
 * Motor de consultas sobre SQLite — alimenta el dashboard.
 * Toda la lógica analítica está aquí; el frontend solo
 * hace fetch() a api/data.php.
 */

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/Mark1Database.php';

class Mark1Engine
{
    private Mark1Database $db;

    public function __construct()
    {
        $this->db = new Mark1Database();
    }

    // ─────────────────────────────────────────────────────
    // CONSTRUCCIÓN DE WHERE según filtros
    // ─────────────────────────────────────────────────────
    private function where(array $f): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($f['anioDesde'])) { $where[] = 'r.anio >= :ad'; $params[':ad'] = $f['anioDesde']; }
        if (!empty($f['anioHasta'])) { $where[] = 'r.anio <= :ah'; $params[':ah'] = $f['anioHasta']; }
        if (!empty($f['unidad']))    { $where[] = 'r.c_unidad = :un'; $params[':un'] = $f['unidad']; }
        if (!empty($f['origen']))    { $where[] = 'r.origen_compra = :or'; $params[':or'] = $f['origen']; }
        if (!empty($f['proveedor'])) { $where[] = 'r.p_nombre = :pv'; $params[':pv'] = $f['proveedor']; }
        if (!empty($f['busqueda'])) {
            $where[]       = "(r.nombre_oc LIKE :bq OR r.descripcion LIKE :bq OR r.p_nombre LIKE :bq)";
            $params[':bq'] = '%' . $f['busqueda'] . '%';
        }

        return [implode(' AND ', $where), $params];
    }

    // ─────────────────────────────────────────────────────
    // KPIs
    // ─────────────────────────────────────────────────────
    public function kpis(array $f): array
    {
        [$where, $params] = $this->where($f);
        $row = $this->db->query("
            SELECT
                COALESCE(SUM(total_bruto), 0)  AS inversion,
                COUNT(DISTINCT p_nombre)        AS proveedores,
                COUNT(*)                        AS registros,
                COALESCE(AVG(total_bruto), 0)   AS promedio_oc
            FROM oc_resumen r WHERE {$where}
              AND r.estado_oc NOT IN ('Cancelada')
        ", $params)[0] ?? [];

        return [
            'inversionTotal'   => (float)($row['inversion']   ?? 0),
            'inversionFormato' => $this->fmt($row['inversion'] ?? 0),
            'proveedores'      => (int)($row['proveedores']    ?? 0),
            'registros'        => (int)($row['registros']      ?? 0),
            'promedioOC'       => $this->fmt($row['promedio_oc'] ?? 0),
        ];
    }

    // ─────────────────────────────────────────────────────
    // TOP PROVEEDORES
    // ─────────────────────────────────────────────────────
    public function topProveedores(array $f, int $n = 10): array
    {
        [$where, $params] = $this->where($f);
        $params[':n']     = $n;
        return $this->db->query("
            SELECT p_nombre AS nombre,
                   COUNT(*) AS ocs,
                   SUM(total_bruto) AS monto
            FROM oc_resumen r WHERE {$where}
            GROUP BY p_nombre
            ORDER BY monto DESC
            LIMIT :n
        ", $params);
    }

    // ─────────────────────────────────────────────────────
    // DISTRIBUCIÓN ESTADO
    // ─────────────────────────────────────────────────────
    public function distEstado(array $f): array
    {
        [$where, $params] = $this->where($f);
        $rows = $this->db->query("
            SELECT estado_oc AS estado, COUNT(*) AS n
            FROM oc_resumen r WHERE {$where}
            GROUP BY estado_oc ORDER BY n DESC
        ", $params);
        $result = [];
        foreach ($rows as $r) $result[$r['estado']] = (int)$r['n'];
        return $result;
    }

    // ─────────────────────────────────────────────────────
    // DISTRIBUCIÓN ORIGEN
    // ─────────────────────────────────────────────────────
    public function distOrigen(array $f): array
    {
        [$where, $params] = $this->where($f);
        $rows = $this->db->query("
            SELECT origen_compra AS origen, COUNT(*) AS n
            FROM oc_resumen r WHERE {$where}
            GROUP BY origen_compra ORDER BY n DESC
        ", $params);
        $result = [];
        foreach ($rows as $r) $result[$r['origen']] = (int)$r['n'];
        return $result;
    }

    // ─────────────────────────────────────────────────────
    // EVOLUCIÓN ANUAL POR ORIGEN
    // ─────────────────────────────────────────────────────
    public function evolucionAnual(array $f): array
    {
        [$where, $params] = $this->where($f);
        $rows = $this->db->query("
            SELECT anio, origen_compra AS origen, COUNT(*) AS n
            FROM oc_resumen r WHERE {$where}
            GROUP BY anio, origen_compra ORDER BY anio
        ", $params);
        $result = [];
        foreach ($rows as $r) {
            $result[$r['anio']][$r['origen']] = (int)$r['n'];
        }
        return $result;
    }

    // ─────────────────────────────────────────────────────
    // TOP 20 ITEMS POR ESPEC COMPRADOR
    // ─────────────────────────────────────────────────────
    public function topItems(array $f, int $n = 20): array
    {
        [$where, $params] = $this->where($f);
        $params[':n']     = $n;
        return $this->db->query("
            SELECT d.espec_comprador AS espec,
                   COUNT(*)          AS veces,
                   SUM(d.total_linea)AS monto
            FROM oc_detalles d
            JOIN oc_resumen r ON r.codigo_oc = d.codigo_oc
            WHERE {$where}
              AND d.espec_comprador != ''
            GROUP BY d.espec_comprador
            ORDER BY veces DESC
            LIMIT :n
        ", $params);
    }

    // ─────────────────────────────────────────────────────
    // BÚSQUEDA DE EQUIPOS
    // ─────────────────────────────────────────────────────
    public function buscarEquipos(array $f, array $terminos): array
    {
        [$where, $params] = $this->where($f);
        $likes = [];
        foreach ($terminos as $i => $t) {
            $key          = ":t{$i}";
            $likes[]      = "d.espec_comprador LIKE {$key}";
            $params[$key] = '%' . $t . '%';
        }
        $likeSQL = implode(' OR ', $likes);

        return $this->db->query("
            SELECT r.codigo_oc, r.p_nombre AS proveedor, r.anio,
                   r.total_bruto AS monto, d.espec_comprador AS espec,
                   d.producto, d.total_linea
            FROM oc_detalles d
            JOIN oc_resumen r ON r.codigo_oc = d.codigo_oc
            WHERE {$where} AND ({$likeSQL})
            ORDER BY r.anio DESC, r.total_bruto DESC
        ", $params);
    }

    // ─────────────────────────────────────────────────────
    // TENDENCIA POR PROVEEDOR
    // ─────────────────────────────────────────────────────
    public function tendenciaProveedor(array $f, string $proveedor): array
    {
        [$where, $params] = $this->where($f);
        $params[':pv2']   = $proveedor;
        return $this->db->query("
            SELECT r.anio, d.producto,
                   COUNT(DISTINCT r.codigo_oc) AS ocs,
                   SUM(d.total_linea)           AS monto
            FROM oc_detalles d
            JOIN oc_resumen r ON r.codigo_oc = d.codigo_oc
            WHERE {$where} AND r.p_nombre = :pv2
              AND d.producto != ''
            GROUP BY r.anio, d.producto
            ORDER BY r.anio, monto DESC
        ", $params);
    }

    // ─────────────────────────────────────────────────────
    // FRECUENCIA PROVEEDORES
    // ─────────────────────────────────────────────────────
    public function frecuenciaProveedores(array $f): array
    {
        [$where, $params] = $this->where($f);
        return $this->db->query("
            SELECT p_nombre     AS nombre,
                   COUNT(*)     AS ocs,
                   SUM(total_bruto) AS monto,
                   origen_compra AS origen_predominante,
                   estado_oc    AS estado
            FROM oc_resumen r WHERE {$where}
            GROUP BY p_nombre
            ORDER BY ocs DESC
        ", $params);
    }

    // ─────────────────────────────────────────────────────
    // METADATOS PARA SELECTORES
    // ─────────────────────────────────────────────────────
    public function aniosDisponibles(): array
    {
        return $this->db->query("SELECT DISTINCT anio FROM oc_resumen ORDER BY anio");
    }

    public function proveedoresDisponibles(array $f): array
    {
        [$where, $params] = $this->where($f);
        return array_column($this->db->query("
            SELECT DISTINCT p_nombre FROM oc_resumen r
            WHERE {$where} AND p_nombre != ''
            ORDER BY p_nombre
        ", $params), 'p_nombre');
    }

    public function unidadesDisponibles(): array
    {
        return array_column($this->db->query("
            SELECT DISTINCT c_unidad FROM oc_resumen
            WHERE c_unidad != '' ORDER BY c_unidad
        "), 'c_unidad');
    }

    // ─────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────
    public function fmt(float $v): string
    {
        return '$ ' . number_format($v, 0, ',', '.');
    }

    // ═════════════════════════════════════════════════════
    // INVERSIONES OC — Unidad de Compras 1057922
    // ═════════════════════════════════════════════════════

    /** WHERE base para unidad 1057922 */
    private function whereInv(): array
    {
        return ["r.c_cod_unidad = '1057922'", []];
    }

    /** KPIs principales + comparación AG año actual vs año anterior */
    public function inversionesKPIs(): array
    {
        [$where, $params] = $this->whereInv();

        $row = $this->db->query("
            SELECT COALESCE(SUM(total_bruto),0) AS inv,
                   COUNT(DISTINCT p_nombre)      AS provs,
                   COUNT(*)                      AS ocs,
                   COALESCE(AVG(total_bruto),0)  AS prom
            FROM oc_resumen r WHERE {$where}
        ", $params)[0] ?? [];

        $agRow = $this->db->query("
            SELECT COUNT(*) AS n, COALESCE(SUM(total_bruto),0) AS monto
            FROM oc_resumen r WHERE {$where} AND r.origen_compra = 'Compra Ágil'
        ", $params)[0] ?? [];

        $anioActual = (int)date('Y');
        $anioPasado = $anioActual - 1;

        // Comparación solo del mes en curso
        $mesNum   = date('m');           // "03"
        $mesesEs  = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo',
                     '06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre',
                     '11'=>'Noviembre','12'=>'Diciembre'];
        $periodoLabel = $mesesEs[$mesNum] ?? date('F');

        $agA = $this->db->query("
            SELECT COUNT(*) n, COALESCE(SUM(total_bruto),0) monto
            FROM oc_resumen WHERE c_cod_unidad='1057922' AND origen_compra='Compra Ágil'
              AND anio=:ay AND strftime('%m', fecha_envio)=:mes
        ", [':ay' => $anioActual, ':mes' => $mesNum])[0] ?? [];

        $agP = $this->db->query("
            SELECT COUNT(*) n, COALESCE(SUM(total_bruto),0) monto
            FROM oc_resumen WHERE c_cod_unidad='1057922' AND origen_compra='Compra Ágil'
              AND anio=:ay AND strftime('%m', fecha_envio)=:mes
        ", [':ay' => $anioPasado, ':mes' => $mesNum])[0] ?? [];

        $varPct = ($agP['n'] > 0)
            ? round((($agA['n'] - $agP['n']) / $agP['n']) * 100, 1)
            : ($agA['n'] > 0 ? 100 : 0);

        return [
            'inversionTotal'  => (float)($row['inv']   ?? 0),
            'inversionFmt'    => $this->fmt($row['inv'] ?? 0),
            'totalOcs'        => (int)($row['ocs']      ?? 0),
            'proveedores'     => (int)($row['provs']    ?? 0),
            'promedioOC'      => $this->fmt($row['prom'] ?? 0),
            'agOcs'           => (int)($agRow['n']      ?? 0),
            'agMonto'         => (float)($agRow['monto'] ?? 0),
            'agMontoFmt'      => $this->fmt($agRow['monto'] ?? 0),
            'agComp'          => [
                'actual' => ['anio'=>$anioActual,'n'=>(int)($agA['n']??0),'monto'=>(float)($agA['monto']??0),'fmt'=>$this->fmt($agA['monto']??0)],
                'pasado' => ['anio'=>$anioPasado,'n'=>(int)($agP['n']??0),'monto'=>(float)($agP['monto']??0),'fmt'=>$this->fmt($agP['monto']??0)],
                'varPct'  => $varPct,
                'periodo' => $periodoLabel,
            ],
        ];
    }

    /**
     * KPIs de tendencia y pronóstico para Estadísticas Históricas.
     * Respeta filtros de unidad/origen/proveedor pero siempre
     * compara año actual vs año anterior (ignora filtro de rango de años).
     */
    public function kpiTendenciaGeneral(array $f): array
    {
        $mesActual  = (int)date('m');
        $anioActual = (int)date('Y');
        $anioPasado = $anioActual - 1;
        $mesStr     = date('m');
        $meses      = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

        // WHERE base sin restricción de año (respeta unidad/origen/proveedor)
        $baseWhere  = ['r.estado_oc NOT IN (\'Cancelada\')'];
        $baseParams = [];
        if (!empty($f['unidad']))    { $baseWhere[] = 'r.c_unidad = :un'; $baseParams[':un'] = $f['unidad']; }
        if (!empty($f['origen']))    { $baseWhere[] = 'r.origen_compra = :or'; $baseParams[':or'] = $f['origen']; }
        if (!empty($f['proveedor'])) { $baseWhere[] = 'r.p_nombre = :pv'; $baseParams[':pv'] = $f['proveedor']; }
        $bw = implode(' AND ', $baseWhere);

        // YTD año actual (hasta mes vigente)
        $ytdA = $this->db->query("
            SELECT COUNT(*) AS n, COALESCE(SUM(total_bruto),0) AS monto
            FROM oc_resumen r
            WHERE {$bw} AND r.anio=:ay
              AND strftime('%m',r.fecha_envio) <= :mes
              AND r.fecha_envio IS NOT NULL
        ", array_merge($baseParams, [':ay'=>$anioActual, ':mes'=>$mesStr]))[0] ?? [];

        // YTD año anterior (mismo corte de mes)
        $ytdP = $this->db->query("
            SELECT COUNT(*) AS n, COALESCE(SUM(total_bruto),0) AS monto
            FROM oc_resumen r
            WHERE {$bw} AND r.anio=:ay
              AND strftime('%m',r.fecha_envio) <= :mes
              AND r.fecha_envio IS NOT NULL
        ", array_merge($baseParams, [':ay'=>$anioPasado, ':mes'=>$mesStr]))[0] ?? [];

        // Cierre completo año anterior
        $totalP = $this->db->query("
            SELECT COUNT(*) AS n, COALESCE(SUM(total_bruto),0) AS monto
            FROM oc_resumen r
            WHERE {$bw} AND r.anio=:ay AND r.fecha_envio IS NOT NULL
        ", array_merge($baseParams, [':ay'=>$anioPasado]))[0] ?? [];

        // Proyección lineal
        $nYTD      = (int)($ytdA['n'] ?? 0);
        $ritmo     = $mesActual > 0 ? round($nYTD / $mesActual, 1) : 0;
        $proyAnual = (int)round($ritmo * 12);

        // Variaciones
        $nP       = (int)($ytdP['n'] ?? 0);
        $varOcs   = $nP > 0 ? round(($nYTD - $nP) / $nP * 100) : ($nYTD > 0 ? 100 : 0);
        $mA       = (float)($ytdA['monto'] ?? 0);
        $mP       = (float)($ytdP['monto'] ?? 0);
        $varMonto = $mP > 0 ? round(($mA - $mP) / $mP * 100) : ($mA > 0 ? 100 : 0);

        // Promedio histórico mensual (5 años anteriores) para peak
        $anioHistDesde = $anioActual - 5;
        $promedios = $this->db->query("
            SELECT mes, ROUND(AVG(cnt),1) AS prom
            FROM (
                SELECT r.anio,
                       CAST(strftime('%m', r.fecha_envio) AS INTEGER) AS mes,
                       COUNT(*) AS cnt
                FROM oc_resumen r
                WHERE {$bw}
                  AND r.anio BETWEEN :ad AND :ah
                  AND r.fecha_envio IS NOT NULL
                GROUP BY r.anio, strftime('%m', r.fecha_envio)
            ) WHERE mes BETWEEN 1 AND 12
            GROUP BY mes ORDER BY prom DESC
        ", array_merge($baseParams, [':ad'=>$anioHistDesde, ':ah'=>$anioPasado]));

        // Próximo peak: mes futuro con mayor promedio histórico
        $peakMes  = null; $peakProm = 0;
        $peakMontoMes = null; $peakMontoProm = 0;
        foreach ($promedios as $p) {
            if ((int)$p['mes'] > $mesActual && (float)$p['prom'] > $peakProm) {
                $peakProm = (float)$p['prom'];
                $peakMes  = (int)$p['mes'];
            }
        }
        if (!$peakMes && !empty($promedios)) {
            $peakMes  = (int)$promedios[0]['mes'];
            $peakProm = (float)$promedios[0]['prom'];
        }

        // Promedio mensual histórico de monto para ese mes peak
        $peakMontoData = $peakMes ? $this->db->query("
            SELECT ROUND(AVG(monto)/1e6,1) AS prom_m
            FROM (
                SELECT r.anio, COALESCE(SUM(r.total_bruto),0) AS monto
                FROM oc_resumen r
                WHERE {$bw}
                  AND r.anio BETWEEN :ad AND :ah
                  AND CAST(strftime('%m',r.fecha_envio) AS INTEGER) = :mes
                  AND r.fecha_envio IS NOT NULL
                GROUP BY r.anio
            )
        ", array_merge($baseParams, [':ad'=>$anioHistDesde, ':ah'=>$anioPasado, ':mes'=>$peakMes]))[0] : null;

        $periodoLabel = $mesActual === 1 ? 'Ene' : 'Ene–' . ($meses[$mesActual] ?? date('M'));

        // Contexto del filtro para mostrar en el card
        $contexto = '';
        if (!empty($f['origen']))    $contexto = $f['origen'];
        elseif (!empty($f['unidad'])) $contexto = $f['unidad'];
        else                          $contexto = 'Todas las modalidades';

        return [
            'anioActual'    => $anioActual,
            'anioPasado'    => $anioPasado,
            'periodo'       => $periodoLabel,
            'proyAnual'     => $proyAnual,
            'ritmo'         => $ritmo,
            'refTotal'      => (int)($totalP['n'] ?? 0),
            'refMontoFmt'   => $this->fmt($totalP['monto'] ?? 0),
            'ytdActual'     => $nYTD,
            'ytdPasado'     => $nP,
            'ytdMontoFmt'   => $this->fmt($mA),
            'ytdMontoPFmt'  => $this->fmt($mP),
            'varOcs'        => $varOcs,
            'varMonto'      => $varMonto,
            'peakMes'       => $peakMes,
            'peakNombre'    => $meses[$peakMes] ?? '—',
            'peakProm'      => $peakProm,
            'peakMontoM'    => (float)($peakMontoData['prom_m'] ?? 0),
            'contexto'      => $contexto,
            'anioHistDesde' => $anioHistDesde,
        ];
    }

    /** KPIs de tendencia y pronóstico — Compra Ágil */
    public function kpiTendenciaAG(): array
    {
        $mesActual  = (int)date('m');
        $anioActual = (int)date('Y');
        $anioPasado = $anioActual - 1;
        $mesStr     = date('m');
        $meses      = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

        // YTD año actual
        $ytdA = $this->db->query("
            SELECT COUNT(*) AS n, COALESCE(SUM(total_bruto),0) AS monto
            FROM oc_resumen
            WHERE c_cod_unidad='1057922' AND origen_compra='Compra Ágil'
              AND anio=:ay AND strftime('%m',fecha_envio) <= :mes AND fecha_envio IS NOT NULL
        ", [':ay'=>$anioActual, ':mes'=>$mesStr])[0] ?? [];

        // YTD año anterior (mismo corte de mes)
        $ytdP = $this->db->query("
            SELECT COUNT(*) AS n, COALESCE(SUM(total_bruto),0) AS monto
            FROM oc_resumen
            WHERE c_cod_unidad='1057922' AND origen_compra='Compra Ágil'
              AND anio=:ay AND strftime('%m',fecha_envio) <= :mes AND fecha_envio IS NOT NULL
        ", [':ay'=>$anioPasado, ':mes'=>$mesStr])[0] ?? [];

        // Total cerrado año anterior
        $totalP = $this->db->query("
            SELECT COUNT(*) AS n, COALESCE(SUM(total_bruto),0) AS monto
            FROM oc_resumen
            WHERE c_cod_unidad='1057922' AND origen_compra='Compra Ágil'
              AND anio=:ay AND fecha_envio IS NOT NULL
        ", [':ay'=>$anioPasado])[0] ?? [];

        // Proyección lineal
        $nYTD      = (int)($ytdA['n'] ?? 0);
        $ritmo     = $mesActual > 0 ? round($nYTD / $mesActual, 1) : 0;
        $proyAnual = (int)round($ritmo * 12);

        // Variaciones
        $nP       = (int)($ytdP['n'] ?? 0);
        $varOcs   = $nP > 0 ? round(($nYTD - $nP) / $nP * 100) : ($nYTD > 0 ? 100 : 0);
        $mA       = (float)($ytdA['monto'] ?? 0);
        $mP       = (float)($ytdP['monto'] ?? 0);
        $varMonto = $mP > 0 ? round(($mA - $mP) / $mP * 100) : ($mA > 0 ? 100 : 0);

        // Promedio histórico mensual (2021–año anterior) para peak
        $promedios = $this->db->query("
            SELECT mes, ROUND(AVG(cnt),1) AS prom
            FROM (
                SELECT anio, CAST(strftime('%m',fecha_envio) AS INTEGER) AS mes, COUNT(*) AS cnt
                FROM oc_resumen
                WHERE c_cod_unidad='1057922' AND origen_compra='Compra Ágil'
                  AND anio BETWEEN 2021 AND :ay AND fecha_envio IS NOT NULL AND fecha_envio != ''
                GROUP BY anio, strftime('%m',fecha_envio)
            ) WHERE mes BETWEEN 1 AND 12
            GROUP BY mes ORDER BY prom DESC
        ", [':ay'=>$anioPasado]);

        // Próximo peak: mes futuro con mayor promedio histórico
        $peakMes = null; $peakProm = 0;
        foreach ($promedios as $p) {
            if ((int)$p['mes'] > $mesActual && (float)$p['prom'] > $peakProm) {
                $peakProm = (float)$p['prom'];
                $peakMes  = (int)$p['mes'];
            }
        }
        // Si ya pasamos todos los peaks del año, tomar el mayor global
        if (!$peakMes && !empty($promedios)) {
            $peakMes  = (int)$promedios[0]['mes'];
            $peakProm = (float)$promedios[0]['prom'];
        }

        // Período legible: "Ene–Mar" si estamos en marzo
        $mesInicio  = 'Ene';
        $mesFin     = $meses[$mesActual] ?? date('M');
        $periodoLabel = ($mesActual === 1) ? $mesInicio : "{$mesInicio}–{$mesFin}";

        return [
            'anioActual'    => $anioActual,
            'anioPasado'    => $anioPasado,
            'periodo'       => $periodoLabel,           // ej. "Ene–Mar"
            'proyAnual'     => $proyAnual,
            'ritmo'         => $ritmo,
            'refTotal'      => (int)($totalP['n'] ?? 0),
            'refMontoFmt'   => $this->fmt($totalP['monto'] ?? 0),
            'ytdActual'     => $nYTD,
            'ytdPasado'     => $nP,
            'ytdMontoFmt'   => $this->fmt($mA),
            'ytdMontoPFmt'  => $this->fmt($mP),
            'varOcs'        => $varOcs,
            'varMonto'      => $varMonto,
            'peakMes'       => $peakMes,
            'peakNombre'    => $meses[$peakMes] ?? '—',
            'peakProm'      => $peakProm,
        ];
    }

    /** Histograma anual: inversión por año y modalidad */
    public function histogramaAnualInv(): array
    {
        [$where, $params] = $this->whereInv();
        $rows = $this->db->query("
            SELECT anio, origen_compra AS origen, COUNT(*) AS n, SUM(total_bruto) AS monto
            FROM oc_resumen r WHERE {$where}
            GROUP BY anio, origen_compra ORDER BY anio, origen_compra
        ", $params);
        $result = [];
        foreach ($rows as $r) {
            $result[(string)$r['anio']][$r['origen']] = ['n'=>(int)$r['n'],'monto'=>(float)$r['monto']];
        }
        return $result;
    }

    /** Evolución Compra Ágil por año */
    public function compraAgilAnualInv(): array
    {
        return $this->db->query("
            SELECT anio, COUNT(*) AS n, SUM(total_bruto) AS monto
            FROM oc_resumen WHERE c_cod_unidad='1057922' AND origen_compra='Compra Ágil'
            GROUP BY anio ORDER BY anio
        ");
    }

    /** OCs Compra Ágil por mes para los 2 años más recientes */
    public function compraAgilMensualInv(): array
    {
        return $this->db->query("
            SELECT
                anio,
                CAST(strftime('%m', fecha_envio) AS INTEGER) AS mes,
                COUNT(*)            AS n,
                SUM(total_bruto)    AS monto
            FROM oc_resumen
            WHERE c_cod_unidad='1057922'
              AND origen_compra='Compra Ágil'
              AND fecha_envio IS NOT NULL AND fecha_envio != ''
              AND anio IN (
                  SELECT DISTINCT anio FROM oc_resumen
                  WHERE c_cod_unidad='1057922' AND origen_compra='Compra Ágil'
                  ORDER BY anio DESC LIMIT 2
              )
            GROUP BY anio, mes
            ORDER BY anio, mes
        ");
    }

    /** Top N proveedores por monto acumulado */
    public function topProveedoresInv(int $n = 10): array
    {
        return $this->db->query("
            SELECT p_nombre AS nombre, COUNT(*) AS ocs, SUM(total_bruto) AS monto
            FROM oc_resumen WHERE c_cod_unidad='1057922'
            GROUP BY p_nombre ORDER BY monto DESC LIMIT :n
        ", [':n' => $n]);
    }

    /** Índice de adjudicación: top N proveedores por cantidad de procesos */
    public function indiceAdjudicacionInv(int $n = 15): array
    {
        return $this->db->query("
            SELECT p_nombre AS nombre, COUNT(*) AS procesos, SUM(total_bruto) AS monto
            FROM oc_resumen WHERE c_cod_unidad='1057922'
            GROUP BY p_nombre ORDER BY procesos DESC LIMIT :n
        ", [':n' => $n]);
    }

    /** Cantidad de OCs adjudicadas y monto por año, desglosado por modalidad */
    public function licitacionesPorAnioInv(): array
    {
        return $this->db->query("
            SELECT anio, origen_compra, COUNT(*) AS n, SUM(total_bruto) AS monto
            FROM oc_resumen WHERE c_cod_unidad='1057922'
            GROUP BY anio, origen_compra ORDER BY anio, origen_compra
        ");
    }

    /** Top N productos más comprados con su principal proveedor */
    public function topProductosInv(int $n = 10): array
    {
        $items = $this->db->query("
            SELECT d.espec_comprador AS espec, COUNT(*) AS veces, SUM(d.total_linea) AS monto
            FROM oc_detalles d JOIN oc_resumen r ON r.codigo_oc = d.codigo_oc
            WHERE r.c_cod_unidad='1057922' AND d.espec_comprador != ''
            GROUP BY d.espec_comprador ORDER BY veces DESC LIMIT :n
        ", [':n' => $n]);

        foreach ($items as &$item) {
            $row = $this->db->query("
                SELECT r.p_nombre FROM oc_detalles d JOIN oc_resumen r ON r.codigo_oc=d.codigo_oc
                WHERE r.c_cod_unidad='1057922' AND d.espec_comprador=:es
                GROUP BY r.p_nombre ORDER BY COUNT(*) DESC LIMIT 1
            ", [':es' => $item['espec']])[0] ?? [];
            $item['proveedor'] = $row['p_nombre'] ?? '—';
        }
        return $items;
    }

    /** Tendencia histórica de un proveedor (monto + OCs por año) */
    public function tendenciaProveedorInv(string $prov): array
    {
        return $this->db->query("
            SELECT anio, SUM(total_bruto) AS monto, COUNT(*) AS ocs
            FROM oc_resumen WHERE c_cod_unidad='1057922' AND p_nombre=:pv
            GROUP BY anio ORDER BY anio
        ", [':pv' => $prov]);
    }

    /** Lista de proveedores disponibles para unidad 1057922 */
    public function proveedoresInv(): array
    {
        return array_column($this->db->query("
            SELECT DISTINCT p_nombre FROM oc_resumen
            WHERE c_cod_unidad='1057922' AND p_nombre!=''
            ORDER BY p_nombre
        "), 'p_nombre');
    }

    /**
     * Desglose de modalidad de compra para los top N proveedores
     * Permite analizar con qué modalidad gana procesos cada proveedor.
     */
    public function adjModalidadInv(int $n = 15): array
    {
        return $this->db->query("
            SELECT p_nombre AS nombre, origen_compra AS origen, COUNT(*) AS procesos
            FROM oc_resumen
            WHERE c_cod_unidad='1057922'
              AND p_nombre IN (
                  SELECT p_nombre FROM oc_resumen
                  WHERE c_cod_unidad='1057922' AND p_nombre != ''
                  GROUP BY p_nombre ORDER BY COUNT(*) DESC LIMIT :n
              )
            GROUP BY p_nombre, origen_compra
        ", [':n' => $n]);
    }

    /**
     * Patrones proveedor-producto: identifica si un proveedor
     * concentra la adjudicación de un ítem específico.
     * Retorna el proveedor dominante por producto, su % de concentración
     * y la modalidad predominante.
     */
    public function patronesProvProd(int $minVeces = 2, int $limit = 30): array
    {
        // Obtener combinaciones proveedor × producto (ya ordenadas por espec, count DESC)
        $rows = $this->db->query("
            SELECT d.espec_comprador AS espec,
                   r.p_nombre        AS proveedor,
                   r.origen_compra   AS origen,
                   COUNT(*)          AS n
            FROM oc_detalles d
            JOIN oc_resumen r ON r.codigo_oc = d.codigo_oc
            WHERE r.c_cod_unidad = '1057922'
              AND d.espec_comprador != ''
              AND r.p_nombre != ''
            GROUP BY d.espec_comprador, r.p_nombre
            ORDER BY d.espec_comprador, COUNT(*) DESC
        ");

        // Agrupar por producto y conservar el proveedor dominante
        $byProd = [];
        foreach ($rows as $r) {
            $e = $r['espec'];
            if (!isset($byProd[$e])) {
                $byProd[$e] = ['total' => 0, 'top' => null, 'n_provs' => 0];
            }
            $byProd[$e]['total'] += (int)$r['n'];
            $byProd[$e]['n_provs']++;
            if (!$byProd[$e]['top']) {   // El primer registro ya es el de mayor frecuencia
                $byProd[$e]['top'] = [
                    'proveedor' => $r['proveedor'],
                    'origen'    => $r['origen'],
                    'veces'     => (int)$r['n'],
                ];
            }
        }

        $result = [];
        foreach ($byProd as $espec => $data) {
            if ($data['total'] < $minVeces) continue;
            $pct = round(($data['top']['veces'] / $data['total']) * 100, 1);
            $result[] = [
                'espec'             => $espec,
                'proveedor'         => $data['top']['proveedor'],
                'origen'            => $data['top']['origen'],
                'veces_top'         => $data['top']['veces'],
                'total_veces'       => $data['total'],
                'n_proveedores'     => $data['n_provs'],
                'pct_concentracion' => $pct,
            ];
        }

        usort($result, fn($a, $b) => $b['pct_concentracion'] <=> $a['pct_concentracion']);
        return array_slice($result, 0, $limit);
    }

    // ══════════════════════════════════════════════════════════════════
    // MÓDULO COMPRA ÁGIL — datos desde ag_cotizaciones + oc_resumen
    // ══════════════════════════════════════════════════════════════════

    /** KPIs principales del módulo Compra Ágil */
    public function agKPIs(): array
    {
        $r = $this->db->query("
            SELECT
                COUNT(*)                                              AS total_cot,
                SUM(CASE WHEN estado='OC Emitida' THEN 1 ELSE 0 END) AS oc_emitidas,
                SUM(CASE WHEN estado='Desierta'   THEN 1 ELSE 0 END) AS desiertas,
                SUM(CASE WHEN estado='Cancelada'  THEN 1 ELSE 0 END) AS canceladas,
                SUM(monto_disponible)                                 AS monto_ppto,
                AVG(cotizaciones_recibidas)                           AS avg_cot_recibidas
            FROM ag_cotizaciones
        ")[0] ?? [];

        // Monto real ejecutado (OCs que matchean en oc_resumen)
        $real = $this->db->query("
            SELECT
                COUNT(*)             AS ocs_ejecutadas,
                SUM(r.total_bruto)   AS monto_real,
                SUM(r.total_neto)    AS monto_neto
            FROM ag_cotizaciones ag
            JOIN oc_resumen r ON ag.codigo_oc = r.codigo_oc
        ")[0] ?? [];

        $totalCot   = (int)($r['total_cot']     ?? 0);
        $ocEmitidas = (int)($r['oc_emitidas']   ?? 0);
        $mPpto      = (float)($r['monto_ppto']  ?? 0);
        $mReal      = (float)($real['monto_real'] ?? 0);
        $ahorro     = $mPpto > 0 && $mReal > 0 ? round((($mPpto - $mReal) / $mPpto) * 100, 1) : 0;

        return [
            'total_cot'        => $totalCot,
            'oc_emitidas'      => $ocEmitidas,
            'desiertas'        => (int)($r['desiertas']    ?? 0),
            'canceladas'       => (int)($r['canceladas']   ?? 0),
            'tasa_adj'         => $totalCot > 0 ? round(($ocEmitidas / $totalCot) * 100, 1) : 0,
            'monto_ppto'       => $mPpto,
            'monto_ppto_fmt'   => $this->fmt($mPpto),
            'monto_real'       => $mReal,
            'monto_real_fmt'   => $this->fmt($mReal),
            'ahorro_pct'       => $ahorro,
            'avg_cot_recibidas'=> round((float)($r['avg_cot_recibidas'] ?? 0), 1),
            'ocs_ejecutadas'   => (int)($real['ocs_ejecutadas'] ?? 0),
        ];
    }

    /** Evolución anual: cotizaciones, OC emitidas, desiertas, canceladas, monto */
    public function agEvolucionAnual(): array
    {
        return $this->db->query("
            SELECT
                anio,
                COUNT(*)                                              AS total,
                SUM(CASE WHEN estado='OC Emitida' THEN 1 ELSE 0 END) AS oc_emitidas,
                SUM(CASE WHEN estado='Desierta'   THEN 1 ELSE 0 END) AS desiertas,
                SUM(CASE WHEN estado='Cancelada'  THEN 1 ELSE 0 END) AS canceladas,
                SUM(monto_disponible)                                 AS monto_ppto,
                ROUND(100.0 * SUM(CASE WHEN estado='OC Emitida' THEN 1 ELSE 0 END) / COUNT(*), 1) AS tasa_adj,
                ROUND(AVG(cotizaciones_recibidas), 1)                 AS avg_competencia
            FROM ag_cotizaciones
            GROUP BY anio
            ORDER BY anio
        ");
    }

    /** Evolución mensual por año — comparativa mismos periodos */
    public function agEvolucionMensual(): array
    {
        return $this->db->query("
            SELECT
                anio,
                CAST(
                    CASE
                        -- Formato ISO: YYYY-MM-DD …
                        WHEN fecha_publicacion GLOB '[0-9][0-9][0-9][0-9]-[0-9]*'
                            THEN strftime('%m', substr(fecha_publicacion,1,10))
                        -- Formato DD/MM/YYYY …
                        WHEN fecha_publicacion GLOB '[0-9][0-9]/[0-9][0-9]/[0-9]*'
                            THEN substr(fecha_publicacion, 4, 2)
                        ELSE NULL
                    END
                AS INTEGER) AS mes,
                COUNT(*)                                                       AS total,
                SUM(CASE WHEN estado='OC Emitida' THEN 1 ELSE 0 END)         AS oc_emitidas,
                SUM(CASE WHEN estado='Desierta'   THEN 1 ELSE 0 END)         AS desiertas,
                SUM(monto_disponible)                                         AS monto_ppto,
                ROUND(100.0*SUM(CASE WHEN estado='OC Emitida' THEN 1 ELSE 0 END)/COUNT(*),1) AS tasa_adj
            FROM ag_cotizaciones
            WHERE fecha_publicacion != '' AND fecha_publicacion IS NOT NULL
            GROUP BY anio, mes
            HAVING mes BETWEEN 1 AND 12
            ORDER BY anio, mes
        ");
    }

    /** Distribución por estado (todos los años) */
    public function agDistEstado(): array
    {
        return $this->db->query("
            SELECT estado, COUNT(*) AS n, SUM(monto_disponible) AS monto
            FROM ag_cotizaciones
            GROUP BY estado
            ORDER BY n DESC
        ");
    }

    /** Primer llamado vs Segundo llamado por año */
    public function agConvocatoria(): array
    {
        return $this->db->query("
            SELECT
                anio,
                estado_convocatoria,
                COUNT(*)              AS n,
                SUM(monto_disponible) AS monto
            FROM ag_cotizaciones
            WHERE estado_convocatoria != ''
            GROUP BY anio, estado_convocatoria
            ORDER BY anio, estado_convocatoria
        ");
    }

    /** Monto presupuestado vs ejecutado (real) por año, vía JOIN con oc_resumen */
    public function agMontoAnual(): array
    {
        $ppto = $this->db->query("
            SELECT anio, SUM(monto_disponible) AS monto_ppto, COUNT(*) AS total_cot
            FROM ag_cotizaciones
            WHERE estado = 'OC Emitida'
            GROUP BY anio ORDER BY anio
        ");

        $real = $this->db->query("
            SELECT ag.anio, SUM(r.total_bruto) AS monto_real, COUNT(*) AS ocs
            FROM ag_cotizaciones ag
            JOIN oc_resumen r ON ag.codigo_oc = r.codigo_oc
            GROUP BY ag.anio ORDER BY ag.anio
        ");

        // Merge por anio
        $realMap = [];
        foreach ($real as $row) {
            $realMap[$row['anio']] = $row;
        }

        $out = [];
        foreach ($ppto as $row) {
            $a = $row['anio'];
            $p = (float)$row['monto_ppto'];
            $r = (float)($realMap[$a]['monto_real'] ?? 0);
            $out[] = [
                'anio'       => $a,
                'monto_ppto' => $p,
                'monto_real' => $r,
                'ahorro'     => $p > 0 && $r > 0 ? round((($p - $r) / $p) * 100, 1) : 0,
                'total_cot'  => (int)$row['total_cot'],
                'ocs'        => (int)($realMap[$a]['ocs'] ?? 0),
            ];
        }
        return $out;
    }

    /** Top N proveedores por monto en Compra Ágil (desde oc_resumen) */
    public function agTopProveedores(int $n = 10): array
    {
        return $this->db->query("
            SELECT
                r.p_nombre                         AS proveedor,
                COUNT(*)                           AS ocs,
                SUM(r.total_bruto)                 AS monto,
                ROUND(AVG(r.total_bruto), 0)       AS prom_oc
            FROM ag_cotizaciones ag
            JOIN oc_resumen r ON ag.codigo_oc = r.codigo_oc
            WHERE r.p_nombre != ''
            GROUP BY r.p_nombre
            ORDER BY monto DESC
            LIMIT $n
        ");
    }

    /** Top N ítems más comprados (extraído del campo nombre, con estado=OC Emitida) */
    public function agTopItems(int $n = 15): array
    {
        return $this->db->query("
            SELECT
                nombre                AS item,
                COUNT(*)              AS veces,
                SUM(monto_disponible) AS monto_ppto,
                anio
            FROM ag_cotizaciones
            WHERE estado = 'OC Emitida' AND nombre != ''
            GROUP BY nombre
            ORDER BY veces DESC, monto_ppto DESC
            LIMIT $n
        ");
    }

    /** Promedio de cotizaciones recibidas por año (nivel de competencia) */
    public function agCompetenciaAnual(): array
    {
        return $this->db->query("
            SELECT
                anio,
                ROUND(AVG(cotizaciones_recibidas), 2)                  AS avg_cotizaciones,
                MAX(cotizaciones_recibidas)                             AS max_cotizaciones,
                SUM(CASE WHEN cotizaciones_recibidas = 0 THEN 1 ELSE 0 END) AS sin_oferta,
                SUM(CASE WHEN cotizaciones_recibidas = 1 THEN 1 ELSE 0 END) AS una_oferta,
                SUM(CASE WHEN cotizaciones_recibidas >= 2 THEN 1 ELSE 0 END) AS multi_oferta,
                COUNT(*) AS total
            FROM ag_cotizaciones
            WHERE estado = 'OC Emitida'
            GROUP BY anio
            ORDER BY anio
        ");
    }

    /** Estado OC (recepción conforme, eliminada, etc.) por año */
    public function agEstadoOCAnual(): array
    {
        return $this->db->query("
            SELECT
                anio,
                estado_oc,
                COUNT(*) AS n
            FROM ag_cotizaciones
            WHERE estado_oc IS NOT NULL AND estado_oc != ''
            GROUP BY anio, estado_oc
            ORDER BY anio, n DESC
        ");
    }

    /** Lista de años con datos AG */
    public function agAnios(): array
    {
        return array_column($this->db->query("SELECT DISTINCT anio FROM ag_cotizaciones ORDER BY anio"), 'anio');
    }

    // ══════════════════════════════════════════════════════════════════
    // MÓDULO TRAZABILIDAD — datos desde bodega_recepciones + oc_resumen
    // ══════════════════════════════════════════════════════════════════

    /** KPIs principales de trazabilidad bodega */
    public function bodegaKPIs(): array
    {
        $r = $this->db->query("
            SELECT
                COUNT(*)                                                             AS total,
                SUM(CASE WHEN estado='Recepción Completa' THEN 1 ELSE 0 END)        AS completa,
                SUM(CASE WHEN estado='En Trámite'         THEN 1 ELSE 0 END)        AS en_tramite,
                SUM(CASE WHEN estado='Recepción Parcial'  THEN 1 ELSE 0 END)        AS parcial,
                SUM(CASE WHEN estado='Anulada'            THEN 1 ELSE 0 END)        AS anulada,
                SUM(CASE WHEN estado='Cerrada'            THEN 1 ELSE 0 END)        AS cerrada,
                SUM(precio_total)                                                    AS monto_total,
                SUM(CASE WHEN estado='Recepción Completa' THEN precio_total ELSE 0 END) AS monto_completa,
                SUM(CASE WHEN estado='En Trámite' THEN precio_total ELSE 0 END)     AS monto_tramite
            FROM bodega_recepciones
        ")[0] ?? [];

        $match = $this->db->query("
            SELECT COUNT(DISTINCT b.codigo_oc) AS n
            FROM bodega_recepciones b
            JOIN oc_resumen r ON b.codigo_oc = r.codigo_oc
            WHERE b.codigo_oc IS NOT NULL
        ")[0]['n'] ?? 0;

        $total = (int)($r['total'] ?? 0);
        return [
            'total'          => $total,
            'completa'       => (int)($r['completa']   ?? 0),
            'en_tramite'     => (int)($r['en_tramite'] ?? 0),
            'parcial'        => (int)($r['parcial']    ?? 0),
            'anulada'        => (int)($r['anulada']    ?? 0),
            'cerrada'        => (int)($r['cerrada']    ?? 0),
            'pct_completa'   => $total > 0 ? round(((int)($r['completa'] ?? 0) / $total) * 100, 1) : 0,
            'pct_tramite'    => $total > 0 ? round(((int)($r['en_tramite'] ?? 0) / $total) * 100, 1) : 0,
            'monto_total'    => (float)($r['monto_total']   ?? 0),
            'monto_total_fmt'=> $this->fmt($r['monto_total'] ?? 0),
            'monto_tramite'  => (float)($r['monto_tramite'] ?? 0),
            'monto_tramite_fmt' => $this->fmt($r['monto_tramite'] ?? 0),
            'ocs_trazables'  => (int)$match,
        ];
    }

    /** Distribución por estado */
    public function bodegaDistEstado(): array
    {
        return $this->db->query("
            SELECT estado, COUNT(*) AS n, SUM(precio_total) AS monto
            FROM bodega_recepciones
            GROUP BY estado ORDER BY n DESC
        ");
    }

    /** Distribución por tipo OC con desglose de estado */
    public function bodegaDistTipo(): array
    {
        return $this->db->query("
            SELECT
                tipo_oc,
                estado,
                COUNT(*)           AS n,
                SUM(precio_total)  AS monto
            FROM bodega_recepciones
            WHERE tipo_oc != ''
            GROUP BY tipo_oc, estado
            ORDER BY tipo_oc, n DESC
        ");
    }

    /** Distribución por bodega con estado */
    public function bodegaDistBodega(): array
    {
        return $this->db->query("
            SELECT
                TRIM(bodega)       AS bodega,
                estado,
                COUNT(*)           AS n,
                SUM(precio_total)  AS monto
            FROM bodega_recepciones
            WHERE bodega != ''
            GROUP BY TRIM(bodega), estado
            ORDER BY SUM(precio_total) DESC
        ");
    }

    /** Evolución mensual de ingresos a bodega */
    public function bodegaEvolucionMensual(): array
    {
        return $this->db->query("
            SELECT
                anio_consumo   AS anio,
                mes_consumo    AS mes,
                estado,
                COUNT(*)       AS n,
                SUM(precio_total) AS monto
            FROM bodega_recepciones
            WHERE anio_consumo IS NOT NULL AND mes_consumo IS NOT NULL
            GROUP BY anio_consumo, mes_consumo, estado
            ORDER BY anio_consumo, mes_consumo
        ");
    }

    /** Alertas: tipos de OC con mayor cantidad En Trámite */
    public function bodegaAlertasTipo(): array
    {
        return $this->db->query("
            SELECT
                tipo_oc,
                COUNT(*) AS total,
                SUM(CASE WHEN estado='Recepción Completa' THEN 1 ELSE 0 END) AS completa,
                SUM(CASE WHEN estado='En Trámite'         THEN 1 ELSE 0 END) AS en_tramite,
                SUM(CASE WHEN estado='Recepción Parcial'  THEN 1 ELSE 0 END) AS parcial,
                SUM(precio_total) AS monto,
                SUM(CASE WHEN estado='En Trámite' THEN precio_total ELSE 0 END) AS monto_tramite,
                ROUND(100.0 * SUM(CASE WHEN estado='Recepción Completa' THEN 1 ELSE 0 END) / COUNT(*), 1) AS pct_completa
            FROM bodega_recepciones
            WHERE tipo_oc != ''
            GROUP BY tipo_oc
            ORDER BY en_tramite DESC
        ");
    }

    /** Buscar OC por código (parcial) — cruza bodega + oc_resumen */
    public function bodegaBuscarOC(string $q, int $limit = 20): array
    {
        $q = trim($q);
        if (strlen($q) < 2) return [];

        $like  = '%' . $q . '%';

        // Resultados de bodega
        $bodega = $this->db->query("
            SELECT
                b.numero, b.proveedor, b.fecha_ingreso, b.estado,
                b.codigo_oc, b.cod_licitacion, b.precio_total,
                TRIM(b.bodega) AS bodega, b.tipo_oc,
                b.anio_consumo, b.mes_consumo
            FROM bodega_recepciones b
            WHERE b.codigo_oc LIKE ? OR b.proveedor LIKE ?
            ORDER BY b.estado, b.fecha_ingreso DESC
            LIMIT $limit
        ", [$like, $like]);

        // Para cada resultado, buscar en oc_resumen
        $ocIds = array_unique(array_filter(array_column($bodega, 'codigo_oc')));
        $ocMap = [];
        if ($ocIds) {
            $ph = implode(',', array_fill(0, count($ocIds), '?'));
            $rows = $this->db->query("
                SELECT codigo_oc, p_nombre, total_bruto, total_neto,
                       estado_oc, origen_compra, c_unidad, anio
                FROM oc_resumen
                WHERE codigo_oc IN ($ph)
            ", $ocIds);
            foreach ($rows as $row) {
                $ocMap[$row['codigo_oc']] = $row;
            }
        }

        // Merge
        foreach ($bodega as &$b) {
            $oc = $ocMap[$b['codigo_oc']] ?? null;
            $b['mp_proveedor']   = $oc['p_nombre']    ?? null;
            $b['mp_monto']       = $oc['total_bruto'] ?? null;
            $b['mp_monto_fmt']   = $oc ? $this->fmt($oc['total_bruto']) : null;
            $b['mp_estado']      = $oc['estado_oc']   ?? null;
            $b['mp_origen']      = $oc['origen_compra'] ?? null;
            $b['mp_unidad']      = $oc['c_unidad']    ?? null;
            $b['precio_total_fmt'] = $this->fmt($b['precio_total']);
            $b['en_mp']          = $oc !== null;
        }
        unset($b);

        return $bodega;
    }

    /** Detalle completo de una OC específica */
    public function bodegaDetalleOC(string $codigoOC): array
    {
        $bodega = $this->db->query("
            SELECT *, TRIM(bodega) AS bodega_clean
            FROM bodega_recepciones
            WHERE codigo_oc = ?
            ORDER BY fecha_ingreso
        ", [$codigoOC]);

        $mp = $this->db->query("
            SELECT r.*, d.producto, d.cantidad, d.total_linea, d.espec_comprador
            FROM oc_resumen r
            LEFT JOIN oc_detalles d ON r.codigo_oc = d.codigo_oc
            WHERE r.codigo_oc = ?
        ", [$codigoOC]);

        foreach ($bodega as &$b) {
            $b['precio_total_fmt'] = $this->fmt($b['precio_total']);
        }
        foreach ($mp as &$m) {
            $m['total_bruto_fmt'] = $this->fmt($m['total_bruto'] ?? 0);
            $m['total_linea_fmt'] = $this->fmt($m['total_linea'] ?? 0);
        }

        return ['bodega' => $bodega, 'mp' => $mp];
    }

    // ─────────────────────────────────────────────────────
    // LIBERACIÓN OC
    // OCs en estado pendiente que requieren seguimiento
    // ─────────────────────────────────────────────────────

    /**
     * KPIs globales de liberación:
     *  - conteo y monto por estado (Cancelada, Enviada a proveedor, En proceso)
     *  - monto total bloqueado
     */
    public function liberacionKPIs(): array
    {
        $estados = ['Cancelada', 'Enviada a proveedor', 'En proceso'];
        $rows = $this->db->query("
            SELECT estado_oc,
                   COUNT(*)                        AS n,
                   COALESCE(SUM(total_bruto), 0)   AS monto
            FROM oc_resumen
            WHERE estado_oc IN ('Cancelada','Enviada a proveedor','En proceso')
            GROUP BY estado_oc
        ");

        $byEstado = [];
        foreach ($rows as $r) {
            $byEstado[$r['estado_oc']] = [
                'n'     => (int)$r['n'],
                'monto' => (float)$r['monto'],
                'fmt'   => $this->fmt($r['monto']),
            ];
        }

        // Asegura que todos los estados aparezcan aunque no tengan datos
        foreach ($estados as $e) {
            if (!isset($byEstado[$e])) {
                $byEstado[$e] = ['n' => 0, 'monto' => 0.0, 'fmt' => $this->fmt(0)];
            }
        }

        $totalN     = array_sum(array_column($byEstado, 'n'));
        $totalMonto = array_sum(array_column($byEstado, 'monto'));

        return [
            'byEstado'   => $byEstado,
            'totalN'     => $totalN,
            'totalMonto' => $totalMonto,
            'totalFmt'   => $this->fmt($totalMonto),
        ];
    }

    /**
     * Listado detallado de OCs que requieren seguimiento,
     * con filtro opcional por estado y unidad.
     */
    public function liberacionDetalle(string $estadoFiltro = '', string $unidadFiltro = '', string $busqueda = ''): array
    {
        $where  = ["estado_oc IN ('Cancelada','Enviada a proveedor','En proceso')"];
        $params = [];

        if ($estadoFiltro !== '') {
            $where[]         = 'estado_oc = :est';
            $params[':est']  = $estadoFiltro;
        }
        if ($unidadFiltro !== '') {
            $where[]         = 'c_unidad = :un';
            $params[':un']   = $unidadFiltro;
        }
        if ($busqueda !== '') {
            $where[]         = "(nombre_oc LIKE :bq OR p_nombre LIKE :bq OR codigo_oc LIKE :bq)";
            $params[':bq']   = '%' . $busqueda . '%';
        }

        $sql = "
            SELECT
                codigo_oc, nombre_oc, p_nombre, c_unidad,
                origen_compra, estado_oc, fecha_envio, anio,
                total_bruto
            FROM oc_resumen
            WHERE " . implode(' AND ', $where) . "
            ORDER BY
                CASE estado_oc
                    WHEN 'En proceso'          THEN 1
                    WHEN 'Enviada a proveedor' THEN 2
                    WHEN 'Cancelada'           THEN 3
                    ELSE 4
                END,
                fecha_envio ASC
            LIMIT 500
        ";

        $rows = $this->db->query($sql, $params);

        foreach ($rows as &$r) {
            $r['total_fmt'] = $this->fmt($r['total_bruto'] ?? 0);
        }
        return $rows;
    }

    /**
     * Distribución por unidad para las OCs pendientes.
     */
    public function liberacionPorUnidad(): array
    {
        return $this->db->query("
            SELECT c_unidad AS unidad,
                   estado_oc,
                   COUNT(*)                      AS n,
                   COALESCE(SUM(total_bruto), 0) AS monto
            FROM oc_resumen
            WHERE estado_oc IN ('Cancelada','Enviada a proveedor','En proceso')
            GROUP BY c_unidad, estado_oc
            ORDER BY monto DESC
        ");
    }

    /**
     * Evolución por año de OCs en estados de alerta.
     */
    public function liberacionPorAnio(): array
    {
        return $this->db->query("
            SELECT anio, estado_oc,
                   COUNT(*)                      AS n,
                   COALESCE(SUM(total_bruto), 0) AS monto
            FROM oc_resumen
            WHERE estado_oc IN ('Cancelada','Enviada a proveedor','En proceso')
            GROUP BY anio, estado_oc
            ORDER BY anio, estado_oc
        ");
    }

    // ═════════════════════════════════════════════════════
    // OBRAS CIVILES — Unidades 1057976 (Propio) + 1180747 (GORE)
    // ═════════════════════════════════════════════════════

    /** KPIs principales de obras civiles */
    public function obrasKPIs(): array
    {
        $row = $this->db->query("
            SELECT
                COALESCE(SUM(total_bruto), 0) AS total_inversion,
                COUNT(CASE WHEN estado_oc = 'Aceptada'            THEN 1 END) AS obras_en_ejecucion,
                COALESCE(SUM(CASE WHEN estado_oc = 'Aceptada'     THEN total_bruto ELSE 0 END), 0) AS inversion_ejecucion,
                COUNT(CASE WHEN estado_oc = 'Recepción Conforme'  THEN 1 END) AS obras_terminadas,
                COALESCE(SUM(CASE WHEN c_cod_unidad = '1180747'   THEN total_bruto ELSE 0 END), 0) AS inversion_gore,
                COALESCE(SUM(CASE WHEN c_cod_unidad = '1057976'   THEN total_bruto ELSE 0 END), 0) AS inversion_propia,
                COALESCE(AVG(CASE WHEN total_bruto > 50000000     THEN total_bruto END), 0)         AS inversion_promedio
            FROM oc_resumen
            WHERE c_cod_unidad IN ('1057976','1180747')
        ")[0] ?? [];

        $top = $this->db->query("
            SELECT nombre_oc, total_bruto
            FROM oc_resumen
            WHERE c_cod_unidad IN ('1057976','1180747')
            ORDER BY total_bruto DESC
            LIMIT 1
        ")[0] ?? [];

        $terminadas   = (int)($row['obras_terminadas']    ?? 0);
        $ejecucion    = (int)($row['obras_en_ejecucion']  ?? 0);
        $denom        = $terminadas + $ejecucion;
        $tasa         = $denom > 0 ? round($terminadas / $denom * 100, 1) : 0.0;
        $total        = (float)($row['total_inversion']   ?? 0);
        $gore         = (float)($row['inversion_gore']    ?? 0);
        $pctGore      = $total > 0 ? round($gore / $total * 100, 1) : 0.0;

        return [
            'total_inversion'       => $total,
            'total_inversion_fmt'   => $this->fmt($total),
            'obras_en_ejecucion'    => $ejecucion,
            'inversion_ejecucion'   => $this->fmt((float)($row['inversion_ejecucion'] ?? 0)),
            'obras_terminadas'      => $terminadas,
            'tasa_finalizacion'     => $tasa,
            'inversion_gore'        => $this->fmt($gore),
            'inversion_propia'      => $this->fmt((float)($row['inversion_propia'] ?? 0)),
            'pct_gore'              => $pctGore,
            'inversion_promedio_fmt'=> $this->fmt((float)($row['inversion_promedio'] ?? 0)),
            'top_obra'              => $top['nombre_oc'] ?? '',
            'top_obra_monto_fmt'    => $this->fmt((float)($top['total_bruto'] ?? 0)),
        ];
    }

    /** Evolución anual de inversión por fuente (unidad) */
    public function obrasEvolucionAnual(): array
    {
        $rows = $this->db->query("
            SELECT
                anio,
                CASE c_cod_unidad
                    WHEN '1057976' THEN 'Obras (1057976)'
                    WHEN '1180747' THEN 'GORE (1180747)'
                    ELSE c_cod_unidad
                END AS unidad,
                COUNT(*)                      AS n,
                COALESCE(SUM(total_bruto), 0) AS monto
            FROM oc_resumen
            WHERE c_cod_unidad IN ('1057976','1180747')
            GROUP BY anio, c_cod_unidad
            ORDER BY anio, c_cod_unidad
        ");
        return $rows;
    }

    /** Top N proveedores de obras civiles */
    public function obrasTopProveedores(int $n = 10): array
    {
        $rows = $this->db->query("
            SELECT
                p_nombre,
                COUNT(*)                      AS n_ocs,
                COALESCE(SUM(total_bruto), 0) AS monto
            FROM oc_resumen
            WHERE c_cod_unidad IN ('1057976','1180747')
              AND p_nombre != ''
              AND p_nombre IS NOT NULL
            GROUP BY p_nombre
            ORDER BY monto DESC
            LIMIT :n
        ", [':n' => $n]);

        foreach ($rows as &$r) {
            $r['monto_fmt'] = $this->fmt((float)($r['monto'] ?? 0));
        }
        return $rows;
    }

    /** Distribución por categoría de obra usando espec_comprador + nombre_oc */
    /**
     * Distribución por nombre de obra real.
     * Agrupa todas las OCs (incluye NC, adendums) por su obra madre,
     * usando cod_licit_resuelto como clave de agrupación y
     * nombre_licitacion como etiqueta preferida.
     * Devuelve top 14 + "Otras obras" si hay más.
     */
    public function obrasDistNombre(): array
    {
        $rows = $this->db->query("
            SELECT
                r.nombre_oc,
                r.total_bruto,
                COALESCE(r.cod_licit_resuelto, r.cod_licitacion) AS licit_key,
                COALESCE(ol.nombre_licitacion, '')                AS nombre_licit,
                COALESCE(d.espec_comprador, '')                   AS espec
            FROM oc_resumen r
            LEFT JOIN obras_licitaciones ol
                ON ol.cod_licitacion = COALESCE(r.cod_licit_resuelto, r.cod_licitacion)
            LEFT JOIN oc_detalles d
                ON d.codigo_oc = r.codigo_oc AND d.correlativo = 1
            WHERE r.c_cod_unidad IN ('1057976','1180747')
        ");

        // Agrupar por obra usando clave licit_key o nombre normalizado
        $map = [];
        foreach ($rows as $r) {
            $key = $this->obraKey($r);
            if (!isset($map[$key])) {
                $map[$key] = [
                    'nombre' => $this->obraLabel($r),
                    'n'      => 0,
                    'monto'  => 0.0,
                ];
            }
            $map[$key]['n']++;
            $map[$key]['monto'] += (float)($r['total_bruto'] ?? 0);
        }

        // Ordenar por monto desc
        usort($map, fn($a, $b) => $b['monto'] <=> $a['monto']);

        // Top 14 + resto como "Otras obras"
        $top   = array_slice($map, 0, 14);
        $otras = array_slice($map, 14);
        if ($otras) {
            $top[] = [
                'nombre' => 'Otras obras',
                'n'      => (int)array_sum(array_column($otras, 'n')),
                'monto'  => array_sum(array_column($otras, 'monto')),
            ];
        }

        foreach ($top as &$r) {
            $r['monto_fmt'] = $this->fmt((float)$r['monto']);
        }
        return array_values($top);
    }

    /** Clave de agrupación por obra */
    private function obraKey(array $r): string
    {
        // Si tiene licit_key (cod_licitacion resuelto), úsalo como clave
        $lk = trim($r['licit_key'] ?? '');
        if ($lk) return $lk;

        // Sin clave: normalizar nombre_oc (primeros 48 chars en mayúsculas)
        return strtoupper(mb_substr(trim($r['nombre_oc'] ?? 'SIN_NOMBRE'), 0, 48));
    }

    /** Etiqueta legible para una obra */
    private function obraLabel(array $r): string
    {
        // 1. Nombre de la licitación desde la API (más limpio y confiable)
        $nl = trim($r['nombre_licit'] ?? '');
        if ($nl && mb_strlen($nl) > 8) {
            return $this->limpiarNombreObra($nl);
        }

        // 2. espec_comprador: limpiar prefijos y validar que quede un nombre útil
        $esp = trim($r['espec'] ?? '');
        if ($esp) {
            // Quitar prefijos de resolución
            $esp = preg_replace('/^(RESOLUCI[OÓ]N?\s+AFECTA\s+[^\.]+\.\s*|RES\.\s*AFECTA\s+[^"]*["]\s*|RES\.?\s+N[°º]?\s*\d+[^\.]+\.\s*)/iu', '', $esp);
            // Quitar prefijo "Trato Directo" si va seguido de comillas con el nombre
            $esp = preg_replace('/^TRATO\s+DIRECTO\s*["\'"]?\s*/iu', '', $esp);
            // Quitar comillas envolventes
            $esp = preg_replace('/^["\'"](.+)["\'"]$/u', '$1', trim($esp));
            $esp = trim($esp);
            // Descartar si empieza con "Código BIP", número de código, o es muy corto
            $esInvalido = (
                mb_strlen($esp) < 8 ||
                preg_match('/^(C[oó]digo\s+BIP|BIP\s*\d|\d{7}-\d+|F\d+-\d+)/iu', $esp)
            );
            if (!$esInvalido) return $this->limpiarNombreObra($esp);
        }

        // 3. nombre_oc como fallback
        return $this->limpiarNombreObra($r['nombre_oc'] ?? 'Sin nombre');
    }

    /** Limpia y trunca un nombre de obra para mostrarlo en el gráfico */
    private function limpiarNombreObra(string $s): string
    {
        // Quitar prefijos de NC / Adendum / ID de licitación / Folio interno
        $s = preg_replace('/^ID\s+\d{7}-\d+-\w+\s+/iu', '', $s);
        $s = preg_replace('/^ORDEN\s+DE\s+COMPRA\s+DESDE\s+\S+\s*/iu', '', $s);
        $s = preg_replace('/^(N\.?\s*C\.?\s*N[°º]?\s*[\d\s,yaY]+\s*[-,]?\s*|NC\s+N[°º]?\s*\d+[\s\-,]+|NOTA\s+DE\s+CAMBIO\s+\S+\s*|ADENDUM\s+\S+\s*)/iu', '', $s);
        $s = preg_replace('/^F\d+-\d+-[A-Z]+\s*[|\/]?\s*/u', '', $s);   // "F4-027-JFR | ..."
        $s = preg_replace('/^DPTO\.?\s+RRFF\s*[\/]?\s*/iu', '', $s);
        $s = preg_replace('/^TRATO\s+DIRECTO\s*/iu', '', $s);
        // Quitar patrón "RASS7/2024 TD " o "RES N°X/XXXX TD "
        $s = preg_replace('/^[A-Z]{2,6}\d*[\s\/]\d{4}\s+TD\s+/iu', '', $s);
        // Quitar comillas al inicio y al final (ASCII y Unicode)
        $s = preg_replace('/^[\s"\']+|[\s"\']+$/u', '', $s);
        $s = preg_replace('/^[\x{201C}\x{201D}\x{201E}\x{00AB}\x{00BB}]+|[\x{201C}\x{201D}\x{201E}\x{00AB}\x{00BB}]+$/u', '', $s);
        $s = trim($s);
        return mb_strlen($s) > 54 ? mb_substr($s, 0, 54) . '…' : $s;
    }

    /** @deprecated — conservar compatibilidad si algo aún llama distCategoria */
    public function obrasDistCategoria(): array { return $this->obrasDistNombre(); }

    /** OCs en ejecución (estado Aceptada) de ambas unidades de obras */
    public function obrasActivas(): array
    {
        $rows = $this->db->query("
            SELECT
                r.codigo_oc,
                CASE
                    WHEN d.espec_comprador IS NOT NULL AND TRIM(d.espec_comprador) != ''
                        THEN SUBSTR(TRIM(d.espec_comprador), 1, 90)
                    ELSE r.nombre_oc
                END AS nombre_proyecto,
                r.p_nombre,
                r.fecha_envio,
                r.fecha_aceptacion,
                r.total_bruto,
                r.cod_licitacion,
                COALESCE(r.cod_licit_resuelto, r.cod_licitacion) AS licit_ref,
                CAST(julianday('now') - julianday(
                    COALESCE(r.fecha_aceptacion, r.fecha_envio)
                ) AS INTEGER) AS dias_activa,
                ol.cod_bip,
                ol.tiempo_duracion,
                ol.unidad_tiempo,
                ol.fuente_financiamiento,
                d.espec_comprador
            FROM oc_resumen r
            LEFT JOIN oc_detalles d
                ON d.codigo_oc = r.codigo_oc AND d.correlativo = 1
            LEFT JOIN obras_licitaciones ol
                ON ol.cod_licitacion = COALESCE(r.cod_licit_resuelto, r.cod_licitacion)
            WHERE r.c_cod_unidad IN ('1057976','1180747')
              AND r.estado_oc = 'Aceptada'
            ORDER BY r.total_bruto DESC
        ");

        $today = new \DateTime('today');

        foreach ($rows as &$r) {
            $r['total_bruto_fmt'] = $this->fmt((float)($r['total_bruto'] ?? 0));

            // --- BIP: desde tabla o extraído de espec_comprador ---
            if (empty($r['cod_bip'])) {
                if (preg_match('/[Cc][Oo][Dd]\.?\s*BIP\s*[:\-]?\s*(\d[\d\-]+\d)/i',
                    $r['espec_comprador'] ?? '', $bm)) {
                    $r['cod_bip'] = $bm[1];
                }
            }
            unset($r['espec_comprador']); // no enviar campo pesado al frontend

            // --- Plazo: convertir a días según unidad_tiempo ---
            // unidad_tiempo: 2 = Días (confirmado por valores empíricos de API MP)
            //                3 = Meses, otros = desconocido
            $tiempoDB  = (int)($r['tiempo_duracion'] ?? 0);
            $unidad    = (int)($r['unidad_tiempo']   ?? 0);
            $plazoDias = 0;
            if ($tiempoDB > 0) {
                if ($unidad === 2)      $plazoDias = $tiempoDB;          // Días
                elseif ($unidad === 3)  $plazoDias = $tiempoDB * 30;     // Meses → días
                elseif ($unidad === 4)  $plazoDias = $tiempoDB * 365;    // Años  → días
                else                   $plazoDias = $tiempoDB;            // Fallback días
            }

            $r['plazo_dias'] = $plazoDias > 0 ? $plazoDias : null;

            // --- Cálculo de avance ---
            $inicio = $r['fecha_aceptacion'] ?? $r['fecha_envio'] ?? null;
            $r['fecha_inicio'] = $inicio;

            if ($plazoDias > 0 && $inicio) {
                $dtInicio = new \DateTime(substr($inicio, 0, 10));
                $diasTrans = (int)$today->diff($dtInicio)->days;
                // No invertir signo: si hoy > inicio, días positivos
                if ($today < $dtInicio) $diasTrans = 0;

                $pct = min(150, (int)round($diasTrans / $plazoDias * 100));

                // Fecha término estimada
                $termino = clone $dtInicio;
                $termino->modify("+{$plazoDias} days");

                $r['dias_transcurridos'] = $diasTrans;
                $r['pct_avance']         = $pct;
                $r['fecha_termino_est']  = $termino->format('Y-m-d');
                $r['dias_restantes']     = max(0, $plazoDias - $diasTrans);

                if ($pct <= 70)        $r['estado_plazo'] = 'en_plazo';
                elseif ($pct <= 95)    $r['estado_plazo'] = 'proximo';
                elseif ($pct <= 100)   $r['estado_plazo'] = 'al_limite';
                else                   $r['estado_plazo'] = 'atrasado';
            } else {
                $r['dias_transcurridos'] = $r['dias_activa'] ?? null;
                $r['pct_avance']         = null;
                $r['fecha_termino_est']  = null;
                $r['dias_restantes']     = null;
                $r['estado_plazo']       = 'sin_datos';
            }

            unset($r['tiempo_duracion'], $r['unidad_tiempo']); // limpiar campos intermedios
        }
        return $rows;
    }

    /** Distribución por estado de obras civiles */
    public function obrasDistEstado(): array
    {
        $rows = $this->db->query("
            SELECT
                estado_oc,
                COUNT(*)                      AS n,
                COALESCE(SUM(total_bruto), 0) AS monto
            FROM oc_resumen
            WHERE c_cod_unidad IN ('1057976','1180747')
            GROUP BY estado_oc
            ORDER BY monto DESC
        ");
        foreach ($rows as &$r) {
            $r['monto_fmt'] = $this->fmt((float)($r['monto'] ?? 0));
        }
        return $rows;
    }

    // ══════════════════════════════════════════════════════════════════
    // MÓDULO INF.EJEC — Indicadores de Ejecución y Riesgo
    // ══════════════════════════════════════════════════════════════════

    /** Concentración de proveedores: HHI, C1, C5, top10 */
    public function infEjecConcentracion(array $f): array
    {
        [$where, $params] = $this->where($f);
        $excl = "AND estado_oc NOT IN ('Cancelada','Anulada')";

        // HHI + C1 + total
        $row = $this->db->query("
            SELECT
                COALESCE(SUM((mp * 100.0 / NULLIF(t,0)) * (mp * 100.0 / NULLIF(t,0))), 0) AS hhi,
                COALESCE(MAX(mp * 100.0 / NULLIF(t,0)), 0) AS c1,
                COUNT(*) AS n_proveedores,
                t AS total_monto
            FROM (
                SELECT p_nombre, SUM(total_bruto) AS mp
                FROM oc_resumen r
                WHERE {$where} {$excl}
                GROUP BY p_nombre
            ) sub,
            (SELECT COALESCE(SUM(total_bruto),0) AS t
             FROM oc_resumen r WHERE {$where} {$excl}) tot
        ", $params)[0] ?? [];

        // Top 10 proveedores
        $top = $this->db->query("
            SELECT p_nombre AS nombre,
                   SUM(total_bruto) AS monto,
                   COUNT(*) AS ocs
            FROM oc_resumen r
            WHERE {$where} {$excl}
            GROUP BY p_nombre
            ORDER BY monto DESC
            LIMIT 10
        ", $params);

        $total = (float)($row['total_monto'] ?? 0);
        foreach ($top as &$t) {
            $t['pct'] = $total > 0 ? round((float)$t['monto'] * 100 / $total, 1) : 0;
            $t['monto_fmt'] = $this->fmt((float)$t['monto']);
        }

        // C5 = suma top5
        $c5 = array_sum(array_column(array_slice($top, 0, 5), 'pct'));

        // Nivel de riesgo HHI
        $hhi = round((float)($row['hhi'] ?? 0));
        $nivel = $hhi < 1500 ? 'verde' : ($hhi < 2500 ? 'amarillo' : 'rojo');

        return [
            'hhi'          => $hhi,
            'c1'           => round((float)($row['c1'] ?? 0), 1),
            'c5'           => round($c5, 1),
            'n_proveedores'=> (int)($row['n_proveedores'] ?? 0),
            'total_monto'  => $total,
            'total_fmt'    => $this->fmt($total),
            'nivel'        => $nivel,
            'top10'        => $top,
        ];
    }

    /** Trato Directo vs. licitación competitiva */
    public function infEjecTratoDirecto(array $f): array
    {
        [$where, $params] = $this->where($f);
        $excl = "AND estado_oc NOT IN ('Cancelada','Anulada')";

        // Totales por modalidad (vista oficial según origen_compra)
        $rows = $this->db->query("
            SELECT origen_compra,
                   COUNT(*) AS ocs,
                   COALESCE(SUM(total_bruto), 0) AS monto
            FROM oc_resumen r
            WHERE {$where} {$excl}
            GROUP BY origen_compra
            ORDER BY monto DESC
        ", $params);

        $totalMonto = array_sum(array_column($rows, 'monto'));
        $totalOcs   = array_sum(array_column($rows, 'ocs'));

        $tdMonto = 0; $tdOcs = 0;
        foreach ($rows as &$r) {
            $r['pct_monto'] = $totalMonto > 0 ? round($r['monto'] * 100 / $totalMonto, 1) : 0;
            $r['pct_ocs']   = $totalOcs   > 0 ? round($r['ocs']   * 100 / $totalOcs,   1) : 0;
            $r['monto_fmt'] = $this->fmt((float)$r['monto']);
            if ($r['origen_compra'] === 'Trato Directo') {
                $tdMonto = (float)$r['monto']; $tdOcs = (int)$r['ocs'];
            }
        }

        // ───────────────────────────────────────────────────────
        // TD AMPLIADO: Antes de 2025 no existía la FTD en Mercado
        // Público — los tratos directos se publicaban como SE
        // (Licitación / Directa). Detectamos esas OCs buscando
        // "trato directo" en espec_comprador de oc_detalles.
        // ───────────────────────────────────────────────────────

        // OCs bajo SE que mencionan "trato directo" en la especificación
        $tdEspec = $this->db->query("
            SELECT r.codigo_oc, r.anio, r.total_bruto
            FROM oc_resumen r
            WHERE {$where} {$excl}
              AND r.origen_compra = 'Licitación / Directa (SE)'
              AND EXISTS (
                  SELECT 1 FROM oc_detalles d
                  WHERE d.codigo_oc = r.codigo_oc
                    AND LOWER(d.espec_comprador) LIKE '%trato directo%'
              )
        ", $params);

        $tdEspecOcs   = count($tdEspec);
        $tdEspecMonto = array_sum(array_column($tdEspec, 'total_bruto'));

        // TD ampliado = TD formal (origen_compra) + TD detectado en espec (bajo SE)
        $tdAmpOcs   = $tdOcs   + $tdEspecOcs;
        $tdAmpMonto = $tdMonto + $tdEspecMonto;

        // Evolución anual: TD formal + TD detectado en espec
        $evol = $this->db->query("
            SELECT r.anio,
                   COALESCE(SUM(r.total_bruto), 0) AS total_monto,
                   COUNT(*) AS total_ocs,
                   -- TD formal (origen_compra = 'Trato Directo')
                   COUNT(CASE WHEN r.origen_compra = 'Trato Directo' THEN 1 END) AS td_formal_ocs,
                   COALESCE(SUM(CASE WHEN r.origen_compra = 'Trato Directo' THEN r.total_bruto ELSE 0 END), 0) AS td_formal_monto,
                   -- TD detectado en espec (bajo SE)
                   COUNT(CASE WHEN r.origen_compra = 'Licitación / Directa (SE)'
                              AND EXISTS (SELECT 1 FROM oc_detalles d
                                          WHERE d.codigo_oc = r.codigo_oc
                                            AND LOWER(d.espec_comprador) LIKE '%trato directo%')
                              THEN 1 END) AS td_espec_ocs,
                   COALESCE(SUM(CASE WHEN r.origen_compra = 'Licitación / Directa (SE)'
                              AND EXISTS (SELECT 1 FROM oc_detalles d
                                          WHERE d.codigo_oc = r.codigo_oc
                                            AND LOWER(d.espec_comprador) LIKE '%trato directo%')
                              THEN r.total_bruto ELSE 0 END), 0) AS td_espec_monto
            FROM oc_resumen r
            WHERE {$where} {$excl}
            GROUP BY r.anio ORDER BY r.anio
        ", $params);

        foreach ($evol as &$e) {
            // TD ampliado por año
            $e['td_ocs']   = (int)$e['td_formal_ocs']   + (int)$e['td_espec_ocs'];
            $e['td_monto'] = (float)$e['td_formal_monto'] + (float)$e['td_espec_monto'];
            $e['pct_td']   = (float)$e['total_monto'] > 0
                ? round($e['td_monto'] * 100 / (float)$e['total_monto'], 1)
                : 0;
        }

        // Nivel de riesgo sobre TD ampliado
        $pctTdMonto = $totalMonto > 0 ? round($tdAmpMonto * 100 / $totalMonto, 1) : 0;
        $pctTdOcs   = $totalOcs   > 0 ? round($tdAmpOcs   * 100 / $totalOcs,   1) : 0;
        $nivel = $pctTdMonto < 15 ? 'verde' : ($pctTdMonto < 30 ? 'amarillo' : 'rojo');

        return [
            'modalidades'   => $rows,
            // TD formal (solo origen_compra = 'Trato Directo')
            'td_monto'      => $tdMonto,
            'td_ocs'        => $tdOcs,
            // TD detectado en espec (SE con mención "trato directo")
            'td_espec_monto'=> $tdEspecMonto,
            'td_espec_ocs'  => $tdEspecOcs,
            // TD ampliado (formal + detectado)
            'td_amp_monto'  => $tdAmpMonto,
            'td_amp_ocs'    => $tdAmpOcs,
            'pct_td_monto'  => $pctTdMonto,
            'pct_td_ocs'    => $pctTdOcs,
            'total_monto'   => $totalMonto,
            'total_fmt'     => $this->fmt($totalMonto),
            'nivel'         => $nivel,
            'evolucion'     => $evol,
        ];
    }

    /** Variación de precio unitario interanual por ítem (top N por variación absoluta) */
    public function infEjecVariacionPrecios(array $f, int $top = 20): array
    {
        [$where, $params] = $this->where($f);
        $anioAct = (int)($f['anioHasta'] ?? date('Y'));
        $anioAnt = $anioAct - 1;
        $params[':ya'] = $anioAct;
        $params[':yb'] = $anioAnt;
        $params[':top'] = $top;

        $rows = $this->db->query("
            WITH precios AS (
                SELECT d.espec_comprador,
                       r.anio,
                       AVG(d.total_linea / NULLIF(d.cantidad, 0)) AS precio_unit,
                       COUNT(*) AS n
                FROM oc_detalles d
                JOIN oc_resumen r ON r.codigo_oc = d.codigo_oc
                WHERE {$where}
                  AND d.cantidad > 0 AND d.total_linea > 100
                  AND LENGTH(d.espec_comprador) > 5
                  AND r.anio IN (:ya, :yb)
                GROUP BY d.espec_comprador, r.anio
            )
            SELECT p1.espec_comprador AS espec,
                   p2.precio_unit     AS precio_ant,
                   p1.precio_unit     AS precio_act,
                   ROUND((p1.precio_unit - p2.precio_unit) * 100.0 / NULLIF(p2.precio_unit,0), 1) AS var_pct,
                   p1.n               AS n_act,
                   :ya                AS anio_act,
                   :yb                AS anio_ant
            FROM precios p1
            JOIN precios p2
              ON p1.espec_comprador = p2.espec_comprador
             AND p1.anio = :ya AND p2.anio = :yb
            WHERE p2.precio_unit > 0
            ORDER BY ABS((p1.precio_unit - p2.precio_unit) * 100.0 / NULLIF(p2.precio_unit,0)) DESC
            LIMIT :top
        ", $params);

        foreach ($rows as &$r) {
            $r['precio_ant_fmt'] = $this->fmt((float)$r['precio_ant']);
            $r['precio_act_fmt'] = $this->fmt((float)$r['precio_act']);
        }
        return $rows;
    }

    /** Ítems comprados históricamente pero sin compra en los últimos N meses */
    public function infEjecSinCompra(array $f, int $meses = 12): array
    {
        [$where, $params] = $this->where($f);
        $anioCorte = (int)date('Y') - (int)ceil($meses / 12);
        $params[':ac'] = $anioCorte;
        $params[':lim'] = 30;

        return $this->db->query("
            SELECT d.espec_comprador        AS espec,
                   MAX(r.anio)              AS ultimo_anio,
                   MAX(r.fecha_envio)       AS ultima_fecha,
                   COUNT(DISTINCT r.codigo_oc) AS n_ocs,
                   COALESCE(SUM(d.total_linea), 0) AS monto_hist
            FROM oc_detalles d
            JOIN oc_resumen r ON r.codigo_oc = d.codigo_oc
            WHERE {$where}
              AND d.espec_comprador != ''
              AND LENGTH(d.espec_comprador) > 8
            GROUP BY d.espec_comprador
            HAVING MAX(r.anio) <= :ac
               AND COUNT(DISTINCT r.codigo_oc) >= 2
            ORDER BY monto_hist DESC
            LIMIT :lim
        ", $params);
    }

    /** Semáforo ejecutivo consolidado */
    public function infEjecSemaforo(array $f): array
    {
        $conc = $this->infEjecConcentracion($f);
        $td   = $this->infEjecTratoDirecto($f);

        // Licitaciones desiertas/canceladas como % del total publicado
        [$where, $params] = $this->where($f);
        $totales = $this->db->query("
            SELECT
                COUNT(*) AS total,
                COUNT(CASE WHEN estado_oc IN ('Cancelada','Anulada') THEN 1 END) AS canceladas
            FROM oc_resumen r WHERE {$where}
        ", $params)[0] ?? [];

        $pctCancel = (int)($totales['total'] ?? 0) > 0
            ? round((int)($totales['canceladas'] ?? 0) * 100 / (int)$totales['total'], 1)
            : 0;
        $nivelCancel = $pctCancel < 5 ? 'verde' : ($pctCancel < 15 ? 'amarillo' : 'rojo');

        // Variación de precios: pct de ítems con >20% alza
        $precios = $this->infEjecVariacionPrecios($f, 50);
        $conAlza = count(array_filter($precios, fn($r) => (float)$r['var_pct'] > 20));
        $pctAlza = count($precios) > 0 ? round($conAlza * 100 / count($precios), 0) : 0;
        $nivelAlza = $pctAlza < 20 ? 'verde' : ($pctAlza < 40 ? 'amarillo' : 'rojo');

        return [
            'concentracion' => [
                'nivel' => $conc['nivel'],
                'valor' => $conc['hhi'],
                'label' => 'HHI ' . number_format($conc['hhi'], 0, ',', '.'),
                'desc'  => 'Concentración de proveedores',
            ],
            'trato_directo' => [
                'nivel' => $td['nivel'],
                'valor' => $td['pct_td_monto'],
                'label' => $td['pct_td_monto'] . '% del gasto',
                'desc'  => 'Trato Directo (incluye FTD + SE con mención TD)',
            ],
            'canceladas' => [
                'nivel' => $nivelCancel,
                'valor' => $pctCancel,
                'label' => $pctCancel . '% del total',
                'desc'  => 'OCs Canceladas / Anuladas',
            ],
            'variacion_precios' => [
                'nivel' => $nivelAlza,
                'valor' => $pctAlza,
                'label' => $pctAlza . '% de ítems',
                'desc'  => 'Ítems con alza >20%',
            ],
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // MÓDULO GARANTÍAS DE SERIEDAD DE LA OFERTA
    // ══════════════════════════════════════════════════════════════════

    /**
     * Busca en oc_resumen datos de una licitación para pre-poblar
     * el formulario de registro de garantía.
     */
    public function garantiaBuscarLicit(string $cod): array
    {
        // Primero busca en obras_licitaciones
        $obra = $this->db->query("
            SELECT cod_licitacion, nombre_licitacion, fecha_adjudicacion
            FROM obras_licitaciones
            WHERE cod_licitacion = :c
            LIMIT 1
        ", [':c' => $cod]);

        if (!empty($obra)) {
            $r = $obra[0];
            return [
                'cod_licitacion'   => $r['cod_licitacion'],
                'nombre_licitacion'=> $r['nombre_licitacion'],
                'fecha_adjudicacion'=> $r['fecha_adjudicacion'],
                'unidad_compra'    => '',
                'adjudicado_a'     => '',
                'rut_adjudicado'   => '',
            ];
        }

        // Luego busca en oc_resumen (primera OC de esa licitación = adjudicada)
        $oc = $this->db->query("
            SELECT cod_licitacion,
                   MAX(nombre_oc)       AS nombre_oc,
                   MAX(c_unidad)        AS unidad_compra,
                   MAX(p_nombre)        AS adjudicado_a,
                   MAX(rut_proveedor)   AS rut_adjudicado,
                   MIN(fecha_envio)     AS fecha_adjudicacion
            FROM oc_resumen
            WHERE cod_licitacion = :c
              AND estado_oc NOT IN ('Cancelada','Anulada')
            GROUP BY cod_licitacion
            LIMIT 1
        ", [':c' => $cod]);

        if (empty($oc)) return [];
        $r = $oc[0];
        return [
            'cod_licitacion'    => $r['cod_licitacion'],
            'nombre_licitacion' => $r['nombre_oc'] ?? '',
            'unidad_compra'     => $r['unidad_compra'] ?? '',
            'adjudicado_a'      => $r['adjudicado_a'] ?? '',
            'rut_adjudicado'    => $r['rut_adjudicado'] ?? '',
            'fecha_adjudicacion'=> $r['fecha_adjudicacion'] ?? '',
        ];
    }

    /**
     * Registra una nueva garantía de seriedad.
     */
    public function garantiaRegistrar(array $d): array
    {
        $campos = [
            'cod_licitacion','nombre_licitacion','unidad_compra',
            'rut_empresa','empresa','tipo_documento','n_documento',
            'banco','monto','fecha_vencimiento','fecha_adjudicacion',
            'observacion','registrado_por',
        ];
        $phs = implode(', ', array_map(fn($c) => ':'.$c, $campos));
        $ins = implode(', ', $campos);
        $this->db->query(
            "INSERT INTO garantias_seriedad ({$ins}) VALUES ({$phs})",
            array_combine(
                array_map(fn($c) => ':'.$c, $campos),
                array_map(fn($c) => $d[$c] ?? null, $campos)
            )
        );
        return ['ok' => true, 'msg' => 'Garantía registrada correctamente.'];
    }

    /**
     * Marca una garantía como devuelta.
     */
    public function garantiaDevolver(int $id, string $fechaDev, string $devueltaA, string $obs = ''): array
    {
        $this->db->query("
            UPDATE garantias_seriedad
            SET estado           = 'devuelta',
                fecha_devolucion = :fd,
                devuelta_a       = :da,
                observacion      = CASE WHEN :ob <> '' THEN :ob ELSE observacion END
            WHERE id = :id
        ", [':fd' => $fechaDev, ':da' => $devueltaA, ':ob' => $obs, ':id' => $id]);
        return ['ok' => true, 'msg' => 'Garantía marcada como devuelta.'];
    }

    /**
     * Lista garantías con semáforo calculado.
     * Semáforo:
     *  devuelta   → check
     *  dias_rest < 0           → VENCIDA (rojo intenso)
     *  dias_rest <= 5          → CRÍTICO (rojo)
     *  dias_rest <= 15         → ALERTA (amarillo)
     *  dias_rest > 15          → VIGENTE (verde)
     *  dias_desde_adj > 10 y pendiente → PLAZO LEGAL (amarillo-naranja)
     */
    public function garantiaListar(string $estado = '', string $unidad = ''): array
    {
        $where  = ['1=1'];
        $params = [];
        if ($estado && $estado !== 'todas') {
            $where[] = 'estado = :est'; $params[':est'] = $estado;
        }
        if ($unidad) {
            $where[] = 'unidad_compra = :uc'; $params[':uc'] = $unidad;
        }
        $w = implode(' AND ', $where);

        $rows = $this->db->query("
            SELECT *,
                   CAST(julianday(fecha_vencimiento) - julianday('now','localtime') AS INTEGER) AS dias_rest,
                   CASE WHEN fecha_adjudicacion IS NOT NULL AND fecha_adjudicacion <> ''
                        THEN CAST(julianday('now','localtime') - julianday(fecha_adjudicacion) AS INTEGER)
                        ELSE NULL
                   END AS dias_desde_adj
            FROM garantias_seriedad
            WHERE {$w}
            ORDER BY
                CASE estado WHEN 'pendiente' THEN 0 ELSE 1 END,
                fecha_vencimiento ASC
        ", $params);

        foreach ($rows as &$r) {
            $dr  = (int)($r['dias_rest']     ?? 999);
            $da  = $r['dias_desde_adj'] !== null ? (int)$r['dias_desde_adj'] : null;
            $est = $r['estado'];

            if ($est === 'devuelta') {
                $r['semaforo'] = 'devuelta';
                $r['sem_label'] = 'Devuelta';
            } elseif ($dr < 0) {
                $r['semaforo'] = 'vencida';
                $r['sem_label'] = 'VENCIDA';
            } elseif ($dr <= 5) {
                $r['semaforo'] = 'critico';
                $r['sem_label'] = 'CRÍTICO';
            } elseif ($dr <= 15) {
                $r['semaforo'] = 'alerta';
                $r['sem_label'] = 'ALERTA';
            } elseif ($da !== null && $da > 10 && $est === 'pendiente') {
                $r['semaforo'] = 'plazo_legal';
                $r['sem_label'] = 'PLAZO LEGAL';
            } else {
                $r['semaforo'] = 'vigente';
                $r['sem_label'] = 'Vigente';
            }

            $r['monto_fmt'] = $r['monto'] ? $this->fmt((float)$r['monto']) : '—';
        }

        return $rows;
    }

    /**
     * KPIs del dashboard de garantías.
     */
    public function garantiaKpis(): array
    {
        $row = $this->db->query("
            SELECT
                COUNT(*)                                                               AS total,
                COUNT(CASE WHEN estado = 'pendiente' THEN 1 END)                      AS pendientes,
                COUNT(CASE WHEN estado = 'devuelta'  THEN 1 END)                      AS devueltas,
                COUNT(CASE WHEN estado = 'pendiente'
                            AND CAST(julianday(fecha_vencimiento)-julianday('now','localtime') AS INTEGER) < 0
                            THEN 1 END)                                                AS vencidas,
                COUNT(CASE WHEN estado = 'pendiente'
                            AND CAST(julianday(fecha_vencimiento)-julianday('now','localtime') AS INTEGER) BETWEEN 0 AND 5
                            THEN 1 END)                                                AS criticas,
                COUNT(CASE WHEN estado = 'pendiente'
                            AND CAST(julianday(fecha_vencimiento)-julianday('now','localtime') AS INTEGER) BETWEEN 6 AND 15
                            THEN 1 END)                                                AS alertas,
                COALESCE(SUM(CASE WHEN estado='pendiente' THEN monto ELSE 0 END),0)   AS monto_pendiente
            FROM garantias_seriedad
        ", [])[0] ?? [];

        $row['monto_pendiente_fmt'] = $this->fmt((float)($row['monto_pendiente'] ?? 0));
        return $row;
    }

    // ══════════════════════════════════════════════════════════════════
    // MÓDULO COMPRA ÁGIL — CANCELADAS / DESIERTAS
    // ══════════════════════════════════════════════════════════════════

    /**
     * Lista cotizaciones canceladas/desiertas con:
     *  - motivo inferido automáticamente por patrón
     *  - motivo manual (si fue ingresado por el comprador)
     */
    public function agCanceladasDesiertas(int $anio = 0): array
    {
        $where  = "estado IN ('Cancelada','Desierta','Guardada')";
        $params = [];
        if ($anio > 0) {
            $where .= ' AND anio = :anio';
            $params[':anio'] = $anio;
        }

        $rows = $this->db->query("
            SELECT
                c.id_cotizacion,
                c.nombre,
                c.unidad_compra,
                c.estado,
                c.estado_convocatoria,
                c.cotizaciones_recibidas,
                c.monto_disponible,
                c.fecha_publicacion,
                c.fecha_cierre,
                c.anio,
                COALESCE(m.motivo, '') AS motivo_manual,
                COALESCE(m.registrado_por, '') AS registrado_por,
                COALESCE(m.actualizado_en, '') AS actualizado_en
            FROM ag_cotizaciones c
            LEFT JOIN ag_canceladas_motivo m ON m.id_cotizacion = c.id_cotizacion
            WHERE {$where}
            ORDER BY c.anio DESC, c.estado, c.fecha_publicacion DESC
        ", $params);

        // Clasificación de motivo inferido
        foreach ($rows as &$r) {
            $est   = $r['estado'];
            $conv  = $r['estado_convocatoria'];
            $nOfe  = (int)$r['cotizaciones_recibidas'];

            if ($est === 'Guardada') {
                $r['motivo_inferido']   = 'No publicada (borrador guardado)';
                $r['motivo_icono']      = '📝';
                $r['motivo_categoria']  = 'guardada';
            } elseif ($est === 'Desierta' && $nOfe === 0) {
                $r['motivo_inferido']   = 'Sin ofertas recibidas';
                $r['motivo_icono']      = '🚫';
                $r['motivo_categoria']  = 'sin_ofertas';
            } elseif ($est === 'Desierta' && $nOfe > 0) {
                $r['motivo_inferido']   = "Con {$nOfe} oferta(s) — ninguna cumplió requisitos o precios fuera de rango";
                $r['motivo_icono']      = '⚠️';
                $r['motivo_categoria']  = 'desierta_con_ofertas';
            } elseif ($est === 'Cancelada' && $conv === 'Segundo llamado') {
                $r['motivo_inferido']   = 'Cancelada tras segundo llamado sin adjudicación';
                $r['motivo_icono']      = '🔄';
                $r['motivo_categoria']  = 'cancelada_2do_llamado';
            } elseif ($est === 'Cancelada' && $nOfe === 0) {
                $r['motivo_inferido']   = 'Cancelada sin ninguna oferta recibida';
                $r['motivo_icono']      = '❌';
                $r['motivo_categoria']  = 'cancelada_sin_ofertas';
            } else {
                $r['motivo_inferido']   = "Cancelada con {$nOfe} oferta(s) — decisión interna";
                $r['motivo_icono']      = '🔴';
                $r['motivo_categoria']  = 'cancelada_con_ofertas';
            }

            $r['monto_fmt'] = $r['monto_disponible'] > 0
                ? $this->fmt((float)$r['monto_disponible'])
                : '—';
        }

        return $rows;
    }

    /**
     * KPIs resumen de canceladas/desiertas.
     */
    public function agCanceladasKpis(int $anio = 0): array
    {
        $where  = "estado IN ('Cancelada','Desierta','Guardada')";
        $params = [];
        if ($anio > 0) {
            $where .= ' AND anio = :anio';
            $params[':anio'] = $anio;
        }

        $row = $this->db->query("
            SELECT
                COUNT(*)                                                        AS total,
                COUNT(CASE WHEN estado = 'Desierta'  THEN 1 END)               AS desiertas,
                COUNT(CASE WHEN estado = 'Cancelada' THEN 1 END)               AS canceladas,
                COUNT(CASE WHEN estado = 'Guardada'  THEN 1 END)               AS guardadas,
                COUNT(CASE WHEN estado = 'Desierta' AND cotizaciones_recibidas = 0 THEN 1 END) AS sin_ofertas,
                COUNT(CASE WHEN estado = 'Cancelada' AND estado_convocatoria = 'Segundo llamado' THEN 1 END) AS reiterativas,
                COALESCE(SUM(CASE WHEN estado IN ('Cancelada','Desierta') THEN monto_disponible ELSE 0 END), 0) AS monto_no_ejecutado
            FROM ag_cotizaciones
            WHERE {$where}
        ", $params)[0] ?? [];

        $row['monto_fmt'] = $this->fmt((float)($row['monto_no_ejecutado'] ?? 0));
        return $row;
    }

    /**
     * Guarda o actualiza el motivo manual de una cotización.
     */
    public function agGuardarMotivo(string $idCot, string $motivo, string $registrador = ''): array
    {
        $this->db->query("
            INSERT INTO ag_canceladas_motivo (id_cotizacion, motivo, registrado_por, actualizado_en)
            VALUES (:id, :mot, :reg, datetime('now','localtime'))
            ON CONFLICT(id_cotizacion) DO UPDATE SET
                motivo         = excluded.motivo,
                registrado_por = excluded.registrado_por,
                actualizado_en = excluded.actualizado_en
        ", [':id' => $idCot, ':mot' => $motivo, ':reg' => $registrador]);
        return ['ok' => true];
    }
}
