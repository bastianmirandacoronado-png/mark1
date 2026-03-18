<?php
/**
 * init_obras_licit.php
 * Crea la tabla obras_licitaciones en la BD SQLite si no existe.
 * Uso: php etl/init_obras_licit.php
 */

define('BASE', dirname(__DIR__));
require_once BASE . '/config.php';
require_once BASE . '/api/Mark1Database.php';

$db  = new Mark1Database();
$pdo = $db->getPDO();

$sql = "
CREATE TABLE IF NOT EXISTS obras_licitaciones (
    cod_licitacion       TEXT PRIMARY KEY,
    nombre_licitacion    TEXT,
    tiempo_duracion      INTEGER,
    unidad_tiempo        TEXT,
    fecha_adjudicacion   TEXT,
    cod_bip              TEXT,
    fuente_financiamiento TEXT,
    monto_estimado       REAL,
    fecha_creacion       TEXT,
    fecha_cierre         TEXT,
    cargado_en           TEXT DEFAULT (datetime('now'))
)
";

$pdo->exec($sql);
echo "Tabla obras_licitaciones creada (o ya existia).\n";
