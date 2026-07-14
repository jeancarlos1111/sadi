FROM php:8.5-fpm-alpine

# Install postgresql-dev for pdo_pgsql extension
RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo_pgsql pgsql

# Habilitar y configurar OPcache con JIT
RUN { \
        echo '; Habilitar OPcache en entorno web y CLI (necesario para JIT y AMPHP)'; \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=1'; \
        echo ''; \
        echo '; Configuración de JIT (Just-In-Time Compiler)'; \
        echo '; Asigna 128MB de memoria RAM exclusiva para el código compilado a máquina'; \
        echo 'opcache.jit_buffer_size=128M'; \
        echo '; Modo tracing: rastrea y compila al vuelo los fragmentos de código más ejecutados'; \
        echo 'opcache.jit=tracing'; \
        echo ''; \
        echo '; Configuraciones generales de OPcache para máximo rendimiento'; \
        echo '; Memoria RAM total asignada a OPcache en MB'; \
        echo 'opcache.memory_consumption=256'; \
        echo '; Memoria reservada para strings cacheadas (evita duplicar strings en memoria)'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo '; Número máximo de archivos PHP que se mantendrán cacheados'; \
        echo 'opcache.max_accelerated_files=10000'; \
    } > /usr/local/etc/php/conf.d/docker-php-ext-opcache-jit.ini

WORKDIR /var/www/html
