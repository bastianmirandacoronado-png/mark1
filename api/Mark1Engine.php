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
}
