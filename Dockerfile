FROM brettt89/silverstripe-web:7.4-apache

WORKDIR /var/www/html

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Node.js and npm
RUN curl -sL https://deb.nodesource.com/setup_14.x | bash -
RUN apt-get install -y nodejs

COPY --chown=www-data:www-data . .

# Install dependencies
RUN composer install

# Install Vue.js dependencies
RUN npm install

# Build Vue.js project
RUN npm run prod

# Set user
USER www-data

# Expose vendor
RUN composer vendor-expose

# Set user
USER root

# Specify a entrypoint.sh script
ENTRYPOINT ["sh","entrypoint.sh"]
