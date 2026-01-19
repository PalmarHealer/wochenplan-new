# syntax=docker/dockerfile:1.7

# ============================================================================
# Build stage: Install Composer dependencies
# ============================================================================
FROM php:8.3-fpm-alpine AS builder

# Install build dependencies and PHP extensions
RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    libzip \
    icu-dev \
    icu-libs \
    oniguruma-dev \
    oniguruma \
    libxml2-dev \
    libxml2 \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        zip \
        bcmath \
        intl \
        mbstring \
        xml

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /build

# Copy only dependency files first (for better caching)
COPY composer.json composer.lock ./

# Install Composer dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Copy the rest of the application
COPY . .

# Run post-autoload scripts
RUN composer dump-autoload --optimize

# ============================================================================
# Runtime stage: Final optimized image
# ============================================================================
FROM php:8.3-fpm-alpine

ENV APP_DIR=/var/www/html \
    PUPPETEER_CACHE_DIR=/usr/local/share/puppeteer

# Install PHP runtime dependencies
RUN apk add --no-cache \
        libzip \
        icu-libs \
        oniguruma \
        libxml2

# Install web server and process manager
RUN apk add --no-cache \
        nginx \
        supervisor

# Install SSH server (optional component)
RUN apk add --no-cache \
        openssh-server

# Install basic system utilities
RUN apk add --no-cache \
        bash \
        curl \
        ca-certificates

# Install Node.js runtime for Puppeteer
RUN apk add --no-cache \
        nodejs \
        npm

# Install Chromium and dependencies for PDF generation
RUN apk add --no-cache \
        chromium \
        nss \
        freetype \
        harfbuzz \
        ttf-freefont

# Build and install PHP extensions
RUN apk add --no-cache --virtual .build-deps \
        ${PHPIZE_DEPS} \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        libxml2-dev \
    && docker-php-ext-install -j$(nproc) pdo_mysql mysqli zip bcmath intl mbstring xml \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --no-cache .build-deps

# Install Puppeteer (will use system Chromium)
RUN npm install -g puppeteer@21 --unsafe-perm=true \
        --omit=dev \
        --omit=optional \
    && echo 'export PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true' >> /etc/profile.d/puppeteer.sh \
    && echo 'export PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser' >> /etc/profile.d/puppeteer.sh \
    && npm cache clean --force \
    && rm -rf /tmp/* /root/.npm

# Create directories
RUN mkdir -p \
        /var/run/sshd \
        /run/php \
        ${APP_DIR} \
    && addgroup -g 82 www-data 2>/dev/null || true \
    && adduser -u 82 -D -S -G www-data www-data 2>/dev/null || true

WORKDIR ${APP_DIR}

# Copy application from builder (read-only, owned by root)
COPY --from=builder --chown=root:root /build ${APP_DIR}

# Copy .env.example to .env as default config (can be overridden by env vars)
RUN cp ${APP_DIR}/.env.example ${APP_DIR}/.env

# Create application directories that will be used in read-only mode
# These will be mounted as tmpfs in docker-compose for runtime writes
RUN mkdir -p \
    ${APP_DIR}/storage/app/public \
    ${APP_DIR}/storage/framework/cache \
    ${APP_DIR}/storage/framework/sessions \
    ${APP_DIR}/storage/framework/testing \
    ${APP_DIR}/storage/framework/views \
    ${APP_DIR}/storage/logs \
    ${APP_DIR}/bootstrap/cache

# Create nginx and system directories
RUN mkdir -p \
    /var/log/nginx \
    /var/log/supervisor \
    /var/lib/nginx/body \
    /var/lib/nginx/fastcgi \
    /var/lib/nginx/proxy \
    /var/lib/nginx/scgi \
    /var/lib/nginx/uwsgi \
    /tmp \
    /run \
    /run/php \
    /var/run

# Set ownership and permissions for application directories
RUN chown -R www-data:www-data \
        ${APP_DIR}/storage \
        ${APP_DIR}/bootstrap/cache \
    && chmod -R 755 ${APP_DIR}/storage ${APP_DIR}/bootstrap/cache

# Set ownership for nginx directories
RUN chown -R www-data:www-data \
        /var/log/nginx \
        /var/lib/nginx

# Create storage symlink at build time (required for read-only filesystem)
RUN ln -s ${APP_DIR}/storage/app/public ${APP_DIR}/public/storage

# Nginx configuration
COPY docker/nginx/wochenplan.conf /etc/nginx/http.d/wochenplan.conf
RUN rm -f /etc/nginx/http.d/default.conf

# Custom PHP configuration
COPY docker/php/custom.ini /usr/local/etc/php/conf.d/custom.ini

# PHP-FPM configuration tweaks
RUN sed -i 's#^;*listen = .*#listen = /run/php/php8.3-fpm.sock#' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's#^;*clear_env = .*#clear_env = no#' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/^user = .*/user = www-data/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/^group = .*/group = www-data/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/^;listen.owner = .*/listen.owner = www-data/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/^;listen.group = .*/listen.group = www-data/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/^;listen.mode = .*/listen.mode = 0660/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's#^listen = .*#listen = /run/php/php8.3-fpm.sock#' /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "env[LARAVEL_PDF_CHROME_PATH] = /usr/bin/chromium-browser" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "env[PUPPETEER_EXECUTABLE_PATH] = /usr/bin/chromium-browser" >> /usr/local/etc/php-fpm.d/www.conf

# SSH configuration
RUN sed -i 's/^#*PasswordAuthentication .*/PasswordAuthentication no/' /etc/ssh/sshd_config \
    && sed -i 's/^#*PermitRootLogin .*/PermitRootLogin prohibit-password/' /etc/ssh/sshd_config \
    && sed -i 's/^#*PubkeyAuthentication .*/PubkeyAuthentication yes/' /etc/ssh/sshd_config

# Supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/supervisor/programs/*.conf /etc/supervisor/conf.d/
# Backup supervisor configs (since /etc/supervisor/conf.d will be tmpfs at runtime)
RUN mkdir -p /etc/supervisor/conf.d.orig && cp -a /etc/supervisor/conf.d/* /etc/supervisor/conf.d.orig/

# Entrypoint and db_setup scripts
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/db_setup.sh /usr/local/bin/db_setup.sh
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/db_setup.sh

EXPOSE 80 22

# Default command
CMD ["/usr/local/bin/entrypoint.sh"]
