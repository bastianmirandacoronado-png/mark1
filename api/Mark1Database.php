<?php
/**
 * Mark1Database.php
 * ─────────────────────────────────────────────────────────
 * Gestión de la base de datos SQLite.
 * Crea las tablas si no existen y expone métodos CRUD.
 */

require_once dirname(__DIR__) . '/config.php';

class Mark1Database
{
    private PDO $pdo;

    public function __construct()
    {
        if (!is_dir(DB_DIR)) mkdir(DB_DIR, 0755, true);
        if (!is_dir(CACHE_DIR)) mkdir(CACHE_DIR, 0755, true);

        $this->pdo = new PDO('sqlite:' . DB_PATH, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo->exec('PRAGMA journal_mode = WAL');
        $this->pdo->exec('PRAGMA synchronous  = NORMAL');
        $this->pdo->exec('PRAGMA foreign_keys = ON');

        $this->crearEsquema();
    }

    // ─────────────────────────────────────────────────────
    // ESQUEMA
    // ─────────────────────────────────────────────────────
    private function crearEsquema(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS oc_resumen (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                codigo_oc        TEXT UNIQUE NOT NULL,
                nombre_oc        TEXT,
                estado_oc        TEXT,
                codigo_estado    TEXT,
                tipo_oc          TEXT,
                origen_compra    TEXT,
                seguimiento      TEXT,
                fecha_envio      TEXT,
                fecha_creacion   TEXT,
                fecha_aceptacion TEXT,
                anio             INTEGER,
                total_bruto      REAL DEFAULT 0,
                total_neto       REAL DEFAULT 0,
                impuestos        REAL DEFAULT 0,
                pct_iva          REAL DEFAULT 0,
                c_cod_unidad     TEXT,
                c_unidad         TEXT,
                c_rut_unidad     TEXT,
                c_comuna         TEXT,
                c_region         TEXT,
                c_contacto       TEXT,
                p_nombre         TEXT,
                rut_proveedor    TEXT,
                p_comuna         TEXT,
                p_region         TEXT,
                descripcion      TEXT,
                cod_licitacion   TEXT,
                forma_pago       TEXT,
                financiamiento   TEXT,
                cargado_en       TEXT DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS oc_detalles (
                id              INTEGER PRIMARY KEY AUTOINCREMENT,
                codigo_oc       TEXT NOT NULL REFERENCES oc_resumen(codigo_oc),
                correlativo     TEXT,
                cod_categoria   TEXT,
                categoria       TEXT,
                cod_producto    TEXT,
                producto        TEXT,
                espec_comprador TEXT,
                espec_proveedor TEXT,
                cantidad        REAL DEFAULT 0,
                unidad          TEXT,
                precio_neto     REAL DEFAULT 0,
                total_impuestos REAL DEFAULT 0,
                total_linea     REAL DEFAULT 0
            );

            CREATE TABLE IF NOT EXISTS etl_control (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                anio        INTEGER NOT NULL,
                mes         INTEGER NOT NULL,
                dia         INTEGER NOT NULL,
                ocs_found   INTEGER DEFAULT 0,
                procesado   INTEGER DEFAULT 0,
                fecha_run   TEXT    DEFAULT (datetime('now')),
                UNIQUE(anio, mes, dia)
            );

            CREATE INDEX IF NOT EXISTS idx_oc_anio     ON oc_resumen(anio);
            CREATE INDEX IF NOT EXISTS idx_oc_prov     ON oc_resumen(p_nombre);
            CREATE INDEX IF NOT EXISTS idx_oc_origen   ON oc_resumen(origen_compra);
            CREATE INDEX IF NOT EXISTS idx_oc_unidad   ON oc_resumen(c_unidad);
            CREATE INDEX IF NOT EXISTS idx_det_codigo  ON oc_detalles(codigo_oc);
        ");
    }

    // ─────────────────────────────────────────────────────
    // INSERT OC RESUMEN (upsert)
    // ─────────────────────────────────────────────────────
    public function upsertOC(array $oc): void
    {
        $campos = [
            'codigo_oc','nombre_oc','estado_oc','codigo_estado','tipo_oc','origen_compra',
            'seguimiento','fecha_envio','fecha_creacion','fecha_aceptacion','anio',
            'total_bruto','total_neto','impuestos','pct_iva',
            'c_cod_unidad','c_unidad','c_rut_unidad','c_comuna','c_region','c_contacto',
            'p_nombre','rut_proveedor','p_comuna','p_region',
            'descripcion','cod_licitacion','forma_pago','financiamiento'
        ];
        $ins = implode(', ', $campos);
        $phs = implode(', ', array_map(fn($c) => ':'.$c, $campos));
        $upd = implode(', ', array_map(fn($c) => "{$c} = excluded.{$c}", array_filter($campos, fn($c) => $c !== 'codigo_oc')));
        $sql = "INSERT INTO oc_resumen ({$ins}) VALUES ({$phs}) ON CONFLICT(codigo_oc) DO UPDATE SET {$upd}, cargado_en = datetime('now')";
        $params = [];
        foreach ($campos as $c) { $params[':'.$c] = $oc[$c] ?? (in_array($c,['anio','total_bruto','total_neto','impuestos','pct_iva','cantidad','precio_neto','total_impuestos','total_linea']) ? 0 : ''); }
        $this->pdo->prepare($sql)->execute($params);
    }

    // ─────────────────────────────────────────────────────
    // INSERT DETALLES (reemplaza los existentes)
    // ─────────────────────────────────────────────────────
    public function upsertDetalles(string $codigoOC, array $items): void
    {
        $this->pdo->prepare("DELETE FROM oc_detalles WHERE codigo_oc = ?")->execute([$codigoOC]);
        $stmt = $this->pdo->prepare("
            INSERT INTO oc_detalles
                (codigo_oc, correlativo, cod_categoria, categoria, cod_producto,
                 producto, espec_comprador, espec_proveedor,
                 cantidad, unidad, precio_neto, total_impuestos, total_linea)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        foreach ($items as $item) {
            $stmt->execute([
                $codigoOC,
                $item['correlativo']     ?? '',
                $item['cod_categoria']   ?? '',
                $item['categoria']       ?? '',
                $item['cod_producto']    ?? '',
                $item['producto']        ?? '',
                $item['espec_comprador'] ?? '',
                $item['espec_proveedor'] ?? '',
                $item['cantidad']        ?? 0,
                $item['unidad']          ?? '',
                $item['precio_neto']     ?? 0,
                $item['total_impuestos'] ?? 0,
                $item['total_linea']     ?? 0,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────
    // ETL CONTROL
    // ─────────────────────────────────────────────────────
    public function diaYaProcesado(int $anio, int $mes, int $dia): bool
    {
        $row = $this->pdo->prepare("
            SELECT procesado FROM etl_control WHERE anio=? AND mes=? AND dia=?
        ");
        $row->execute([$anio, $mes, $dia]);
        $result = $row->fetch();
        return $result && $result['procesado'] == 1;
    }

    public function marcarDiaProcesado(int $anio, int $mes, int $dia, int $ocsFound): void
    {
        $this->pdo->prepare("
            INSERT INTO etl_control (anio, mes, dia, ocs_found, procesado)
            VALUES (?, ?, ?, ?, 1)
            ON CONFLICT(anio, mes, dia) DO UPDATE SET
                ocs_found = excluded.ocs_found,
                procesado = 1,
                fecha_run = datetime('now')
        ")->execute([$anio, $mes, $dia, $ocsFound]);
    }

    public function estadoETL(): array
    {
        $total   = $this->pdo->query("SELECT COUNT(*) as n FROM oc_resumen")->fetch()['n'];
        $detalles= $this->pdo->query("SELECT COUNT(*) as n FROM oc_detalles")->fetch()['n'];
        $dias    = $this->pdo->query("SELECT COUNT(*) as n FROM etl_control WHERE procesado=1")->fetch()['n'];
        $ultimo  = $this->pdo->query("SELECT MAX(fecha_run) as f FROM etl_control")->fetch()['f'];
        $anios   = $this->pdo->query("SELECT DISTINCT anio FROM oc_resumen ORDER BY anio")->fetchAll(PDO::FETCH_COLUMN);
        return compact('total', 'detalles', 'dias', 'ultimo', 'anios');
    }

    // ─────────────────────────────────────────────────────
    // QUERIES PARA EL DASHBOARD
    // ─────────────────────────────────────────────────────
    public function getPDO(): PDO { return $this->pdo; }

    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function scalar(string $sql, array $params = []): mixed
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    // ─────────────────────────────────────────────────────
    // TRANSACCIONES
    // ─────────────────────────────────────────────────────
    public function beginTransaction(): void  { $this->pdo->beginTransaction(); }
    public function commit(): void            { $this->pdo->commit(); }
    public function rollback(): void          { $this->pdo->rollBack(); }
}
