<?php
/**
 * ╔══════════════════════════════════════════════════════════╗
 * ║   MARK 1 — Configuración de ejemplo                     ║
 * ║   Copia este archivo como config.php y completa         ║
 * ║   los valores antes de ejecutar el proyecto.            ║
 * ╚══════════════════════════════════════════════════════════╝
 *
 * INSTRUCCIONES:
 *   1. Copia este archivo: cp config.example.php config.php
 *   2. Edita config.php con tus credenciales reales
 *   3. NUNCA subas config.php a GitHub
 */

// ── API MERCADO PÚBLICO ────────────────────────────────────
// Obtén tu ticket en: https://www.mercadopublico.cl/Modules/Compras/PopUpLoginFicha.aspx
define('MP_TICKET',    'TU-TICKET-AQUI');
define('MP_ORGANISMO', 'CODIGO-ORGANISMO');
define('MP_BASE_URL', 'https://api.mercadopublico.cl/servicios/v1/publico/ordenesdecompra.json');

// ── UNIDADES DE COMPRA ─────────────────────────────────────
define('MP_UNIDADES', [
    '0000000',   // Unidad 1 — reemplaza con tus códigos
    '0000001',   // Unidad 2
]);

// ── BASE DE DATOS SQLITE ───────────────────────────────────
define('DB_PATH',  __DIR__ . '/db/mark1.sqlite');
define('DB_DIR',   __DIR__ . '/db');

// ── RANGO HISTÓRICO ETL ────────────────────────────────────
define('ETL_ANIO_DESDE', 2020);
define('ETL_ANIO_HASTA', (int) date('Y'));

// ── ETL: CONTROL DE EJECUCIÓN ─────────────────────────────
define('ETL_BATCH_SIZE',  50);
define('ETL_DELAY_MS',    300);
define('ETL_LOG_PATH',    __DIR__ . '/db/etl.log');

// ── CACHÉ HTTP ─────────────────────────────────────────────
define('CACHE_DIR', __DIR__ . '/db/cache');
define('CACHE_TTL', 3600 * 24);

// ── PALABRAS CLAVE ACTIVO FIJO ─────────────────────────────
define('AF_KEYWORDS', [
    'activo fijo', 'equipamiento', 'equipo médico',
]);

// ── ACCESO / LOGIN ─────────────────────────────────────────
// Cambia estas credenciales antes de exponer a internet
define('AUTH_USERS', [
    'admin'  => 'cambia-esta-clave',
    'visor'  => 'cambia-esta-clave',
]);
define('SESSION_TIMEOUT', 8 * 3600);

// ── MAPEO TIPO OC → ORIGEN ─────────────────────────────────
define('TIPO_ORIGEN_MAP', [
    'SE' => 'Licitación / Directa (SE)',
    'LE' => 'Licitación / Directa (SE)',
    'LP' => 'Licitación / Directa (SE)',
    'AG' => 'Compra Ágil',
    'CM' => 'Convenio Marco',
    'TD' => 'Trato Directo',
]);
