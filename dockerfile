# PHP 8.2 con Apache
FROM php:8.2-apache

# Paquetes y extensiones necesarias
RUN apt-get update && apt-get install -y git unzip libzip-dev libpng-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Carpeta de trabajo
WORKDIR /var/www/html

# Copiar el proyecto
COPY . .

# Instalar dependencias de Laravel
RUN composer install --no-dev --optimize-autoloader

# Habilitar mod_rewrite y apuntar docroot a /public
RUN a2enmod rewrite \
 && sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
 && printf "<Directory /var/www/html/public>\n\tAllowOverride All\n</Directory>\n" >> /etc/apache2/apache2.conf

# Ajustar el puerto de Apache al que provee Railway
CMD bash -lc "sed -i 's/^Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf && apache2-foreground"
