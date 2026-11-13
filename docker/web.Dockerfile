# Local dev image: code is bind-mounted, not copied.
FROM brettt89/silverstripe-web:7.4-apache

# Debian 11 (bullseye) apt repos moved to archive.debian.org (same fix as ../Dockerfile)
RUN printf 'deb http://archive.debian.org/debian bullseye main\ndeb http://archive.debian.org/debian bullseye-updates main\n' > /etc/apt/sources.list

# app/_config/mysite.yml sets the image driver to imagick; the base image only has gd
RUN install-php-extensions imagick

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html
