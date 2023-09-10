FROM brettt89/silverstripe-web:7.4-apache

WORKDIR /var/www/html

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY --chown=www-data:www-data . .

#  Install dependencies
RUN composer install

# Install Node.js and npm
RUN curl -sL https://deb.nodesource.com/setup_14.x | bash -
RUN apt-get install -y nodejs

# Install Vue.js dependencies
RUN npm install

# Build Vue.js project
RUN npm run prod

# Copy Vue.js dist
RUN mkdir -p public/_resources/app/client/dist
RUN cp -R app/client/dist/* /var/www/html/public/_resources/app/client/dist
