# syntax=docker/dockerfile:1.7

# ============================================================================
# Build stage: Install Composer dependencies
# ============================================================================
FROM php:8.3-fpm AS builder

ENV DEBIAN_FRONTEND=noninteractive

# Install build dependencies and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        zip \
        bcmath \
        intl \
        mbstring \
        xml \
    && rm -rf /var/lib/apt/lists/*

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
FROM php:8.3-fpm

ENV DEBIAN_FRONTEND=noninteractive \
    APP_DIR=/var/www/html \
    PUPPETEER_CACHE_DIR=/usr/local/share/puppeteer

# Install build dependencies, build PHP extensions, then keep only runtime dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    nginx \
    supervisor \
    openssh-server \
    curl ca-certificates gnupg \
    wget \
    fonts-liberation \
    fonts-noto-color-emoji \
    libnss3 \
    libxss1 \
    libasound2t64 \
    libatk-bridge2.0-0 \
    libatk1.0-0 \
    libcups2 \
    libdrm2 \
    libgbm1 \
    libgtk-3-0 \
    libnspr4 \
    libx11-xcb1 \
    libxcomposite1 \
    libxdamage1 \
    libxrandr2 \
    git \
    && mkdir -p /etc/apt/keyrings \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_20.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends nodejs \
    && docker-php-ext-install -j$(nproc) pdo_mysql mysqli zip bcmath intl mbstring xml \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove \
        libzip-dev \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
    && apt-get install -y --no-install-recommends \
        libzip5 \
        libicu76 \
        libonig5 \
        libxml2 \
    && rm -rf /var/lib/apt/lists/*

# Install Puppeteer globally with Chromium
RUN mkdir -p ${PUPPETEER_CACHE_DIR} \
    && PUPPETEER_CACHE_DIR=${PUPPETEER_CACHE_DIR} npm install -g puppeteer --unsafe-perm=true --allow-root \
    && chmod -R 755 ${PUPPETEER_CACHE_DIR} \
    && npm cache clean --force

# Create directories
RUN mkdir -p /var/run/sshd /run/php ${APP_DIR} \
    && chown -R www-data:www-data ${APP_DIR}

WORKDIR ${APP_DIR}

# Copy application from builder
COPY --from=builder --chown=www-data:www-data /build ${APP_DIR}

# Git safe directory configuration
RUN git config --global --add safe.directory ${APP_DIR}

# Nginx configuration
COPY docker/nginx/wochenplan.conf /etc/nginx/sites-available/wochenplan.conf
RUN rm -f /etc/nginx/sites-enabled/default \
    && ln -s /etc/nginx/sites-available/wochenplan.conf /etc/nginx/sites-enabled/wochenplan.conf

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
    && sed -i 's#^listen = .*#listen = /run/php/php8.3-fpm.sock#' /usr/local/etc/php-fpm.d/zz-docker.conf

# SSH configuration
RUN sed -i 's/^PasswordAuthentication .*/PasswordAuthentication no/' /etc/ssh/sshd_config \
    && sed -i 's/^PermitRootLogin .*/PermitRootLogin prohibit-password/' /etc/ssh/sshd_config \
    && sed -i 's/^#PubkeyAuthentication .*/PubkeyAuthentication yes/' /etc/ssh/sshd_config

# Supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/supervisor/programs/*.conf /etc/supervisor/conf.d/

# Entrypoint and db_setup scripts
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/db_setup.sh /usr/local/bin/db_setup.sh
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/db_setup.sh

EXPOSE 80 22

# Default command
CMD ["/usr/local/bin/entrypoint.sh"]
