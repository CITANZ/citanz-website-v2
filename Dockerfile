FROM brettt89/silverstripe-web:7.4-apache

WORKDIR /var/www/html

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Node.js 14 (matches .nvmrc). NodeSource no longer serves setup_14.x
# (Node 14 is EOL), so install from the official tarball instead.
ARG NODE_VERSION=14.21.3
RUN ARCH=$(uname -m | sed 's/x86_64/x64/;s/aarch64/arm64/') \
    && curl -fsSL "https://nodejs.org/dist/v${NODE_VERSION}/node-v${NODE_VERSION}-linux-${ARCH}.tar.xz" \
       | tar -xJ -C /usr/local --strip-components=1 --no-same-owner

# The base image is Debian 11 (bullseye), whose apt repositories have moved to
# archive.debian.org (and bullseye-security no longer exists as a separate
# suite); the image's default sources.list now 404s on most packages.
RUN printf 'deb http://archive.debian.org/debian bullseye main\ndeb http://archive.debian.org/debian bullseye-updates main\n' > /etc/apt/sources.list

# node-sass@4 ships no prebuilt binary for linux-arm64 (Apple Silicon hosts), so
# npm has to compile it with the node-gyp bundled in node-sass, which only
# understands Python 2. g++/make are already in the base image.
RUN apt-get update && apt-get install -y --no-install-recommends python2 \
    && ln -sf /usr/bin/python2 /usr/local/bin/python \
    && rm -rf /var/lib/apt/lists/*

COPY --chown=www-data:www-data . .

# Install dependencies
RUN composer install

# Install Vue.js dependencies
RUN npm install

# Build Vue.js project
RUN npm run prod

# `composer install` above already ran vendor-expose (as root). Re-running it as
# www-data fails because it cannot delete root-owned files in public/_resources,
# so instead hand the build products to the web server user.
RUN chown -R www-data:www-data /var/www/html/public /var/www/html/app/client/dist

# Specify a entrypoint.sh script
ENTRYPOINT ["sh","entrypoint.sh"]
