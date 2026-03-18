FROM php:8.2-apache

# Instalar extensión SQLite3
RUN docker-php-ext-install pdo pdo_sqlite

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar proyecto
COPY . /var/www/html/

# Crear directorio de BD con permisos
RUN mkdir -p /var/www/html/db && \
    chmod -R 777 /var/www/html/db

# Crear config.php desde variables de entorno al iniciar
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
