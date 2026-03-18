FROM php:8.2-apache

# Instalar extensión SQLite3
RUN docker-php-ext-install pdo pdo_sqlite

# Habilitar mod_rewrite y headers
RUN a2enmod rewrite headers

# Permitir .htaccess en /var/www/html
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copiar proyecto
COPY . /var/www/html/

# Crear directorio de BD con permisos
RUN mkdir -p /var/www/html/db/cache && \
    chmod -R 777 /var/www/html/db && \
    chown -R www-data:www-data /var/www/html

# Crear config.php desde variables de entorno al iniciar
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Puerto dinámico (Render lo asigna vía var PORT)
EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
