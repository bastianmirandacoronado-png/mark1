#!/bin/bash
set -e

# Generar config.php desde variables de entorno
cat > /var/www/html/config.php << EOF
<?php
define('MP_TICKET',    '${MP_TICKET:-}');
define('MP_ORGANISMO', '${MP_ORGANISMO:-7296}');
define('MP_BASE_URL',  'https://api.mercadopublico.cl/servicios/v1/publico/ordenesdecompra.json');
define('MP_UNIDADES',  ['1057922','1057532','1077241']);
define('DB_PATH',  __DIR__ . '/db/mark1.sqlite');
define('DB_DIR',   __DIR__ . '/db');
define('ETL_ANIO_DESDE', 2020);
define('ETL_ANIO_HASTA', (int) date('Y'));
define('ETL_BATCH_SIZE',  50);
define('ETL_DELAY_MS',    300);
define('ETL_LOG_PATH',    __DIR__ . '/db/etl.log');
define('CACHE_DIR', __DIR__ . '/db/cache');
define('CACHE_TTL', 3600 * 24);
define('AF_KEYWORDS', ['activo fijo','equipamiento','equipo médico']);
define('AUTH_USERS', [
    '${AUTH_USER1:-sso}'     => '${AUTH_PASS1:-mark1.2024}',
    '${AUTH_USER2:-bastian}' => '${AUTH_PASS2:-ssoDSS0!}',
    '${AUTH_USER3:-visor}'   => '${AUTH_PASS3:-solo.lectura}',
]);
define('SESSION_TIMEOUT', 8 * 3600);
define('TIPO_ORIGEN_MAP', [
    'SE' => 'Licitación / Directa (SE)',
    'LE' => 'Licitación / Directa (SE)',
    'LP' => 'Licitación / Directa (SE)',
    'LQ' => 'Licitación / Directa (SE)',
    'LR' => 'Licitación / Directa (SE)',
    'L1' => 'Licitación / Directa (SE)',
    'AG' => 'Compra Ágil',
    'CM' => 'Convenio Marco',
    'TD' => 'Trato Directo',
]);
EOF

echo "config.php generado correctamente"

# Crear directorios necesarios
mkdir -p /var/www/html/db/cache
chmod -R 777 /var/www/html/db

# Configurar Apache para escuchar en el puerto que Render asigna (var PORT)
LISTEN_PORT="${PORT:-80}"
echo "Configurando Apache en puerto ${LISTEN_PORT}..."

# Reemplazar puerto en ports.conf
sed -i "s/Listen 80/Listen ${LISTEN_PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${LISTEN_PORT}>/g" /etc/apache2/sites-enabled/*.conf 2>/dev/null || true

exec "$@"
