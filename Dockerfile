# syntax=docker/dockerfile:1.7

# ============================================================================
# Build stage: Install Composer dependencies
# ============================================================================
FROM php:8.3-fpm-alpine AS builder

# Install build dependencies and compile all PHP extensions (including redis)
RUN apk add --no-cache \
    git unzip ${PHPIZE_DEPS} \
    libzip-dev icu-dev oniguruma-dev libxml2-dev \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql mysqli zip bcmath intl mbstring xml \
    && pecl install redis \
    && docker-php-ext-enable redis

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
    PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true \
    PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser

# Copy pre-compiled PHP extensions from builder (avoids recompiling — saves minutes)
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

# Install all runtime system packages in a single layer
RUN apk add --no-cache \
        # PHP runtime deps
        libzip icu-libs oniguruma libxml2 \
        # Web server & process manager
        nginx supervisor \
        # SSH server (optional)
        openssh-server \
        # System utilities
        bash curl ca-certificates \
        # Node.js for Puppeteer
        nodejs npm \
        # Chromium for PDF generation
        chromium nss freetype harfbuzz ttf-freefont

# Install Puppeteer (will use system Chromium)
ARG SKIP_PUPPETEER_INSTALL=false
RUN if [ "${SKIP_PUPPETEER_INSTALL}" = "false" ]; then \
        PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true npm install -g puppeteer@21 --unsafe-perm=true \
            --omit=dev \
            --omit=optional \
        && echo 'export PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true' >> /etc/profile.d/puppeteer.sh \
        && echo 'export PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser' >> /etc/profile.d/puppeteer.sh \
        && npm cache clean --force \
        && rm -rf /tmp/* /root/.npm; \
    fi

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

# Create all directories, set permissions, configure PHP-FPM and SSH in one layer
RUN cp ${APP_DIR}/.env.example ${APP_DIR}/.env \
    # App directories
    && mkdir -p \
        ${APP_DIR}/storage/app/public \
        ${APP_DIR}/storage/framework/cache \
        ${APP_DIR}/storage/framework/sessions \
        ${APP_DIR}/storage/framework/testing \
        ${APP_DIR}/storage/framework/views \
        ${APP_DIR}/storage/logs \
        ${APP_DIR}/bootstrap/cache \
    # Nginx and system directories
    && mkdir -p \
        /var/log/nginx /var/log/supervisor \
        /var/lib/nginx/body /var/lib/nginx/fastcgi /var/lib/nginx/proxy \
        /var/lib/nginx/scgi /var/lib/nginx/uwsgi \
        /run/php /var/run \
    # Permissions
    && chown -R www-data:www-data \
        ${APP_DIR}/storage ${APP_DIR}/bootstrap/cache \
        /var/log/nginx /var/lib/nginx \
    && chmod -R 755 ${APP_DIR}/storage ${APP_DIR}/bootstrap/cache \
    # Storage symlink
    && ln -s ${APP_DIR}/storage/app/public ${APP_DIR}/public/storage \
    # PHP-FPM configuration
    && sed -i 's#^;*listen = .*#listen = /run/php/php8.3-fpm.sock#' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's#^;*clear_env = .*#clear_env = no#' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/^user = .*/user = www-data/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/^group = .*/group = www-data/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/^;listen.owner = .*/listen.owner = www-data/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/^;listen.group = .*/listen.group = www-data/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/^;listen.mode = .*/listen.mode = 0660/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's#^listen = .*#listen = /run/php/php8.3-fpm.sock#' /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "env[LARAVEL_PDF_CHROME_PATH] = /usr/bin/chromium-browser" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "env[PUPPETEER_EXECUTABLE_PATH] = /usr/bin/chromium-browser" >> /usr/local/etc/php-fpm.d/www.conf \
    # Nginx: run worker processes as www-data (matches /var/lib/nginx ownership)
    && sed -i 's/^user nginx;/user www-data;/' /etc/nginx/nginx.conf \
    # SSH configuration
    && sed -i 's/^#*PasswordAuthentication .*/PasswordAuthentication no/' /etc/ssh/sshd_config \
    && sed -i 's/^#*PermitRootLogin .*/PermitRootLogin prohibit-password/' /etc/ssh/sshd_config \
    && sed -i 's/^#*PubkeyAuthentication .*/PubkeyAuthentication yes/' /etc/ssh/sshd_config

# Config files (separate COPY layers for cache efficiency)
COPY docker/nginx/wochenplan.conf /etc/nginx/http.d/wochenplan.conf
COPY docker/php/custom.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/supervisor/programs/*.conf /etc/supervisor/conf.d/
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/db_setup.sh /usr/local/bin/db_setup.sh

RUN rm -f /etc/nginx/http.d/default.conf \
    && mkdir -p /etc/supervisor/conf.d.orig && cp -a /etc/supervisor/conf.d/* /etc/supervisor/conf.d.orig/ \
    && chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/db_setup.sh

EXPOSE 80 22

# Default command
CMD ["/usr/local/bin/entrypoint.sh"]
