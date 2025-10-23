# syntax=docker/dockerfile:1.7

FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive \
    APP_USER=app \
    APP_DIR=/var/www/html

# System dependencies and PHP 8.3 (Ondrej PPA), Nginx, SSH, Supervisor, Git, unzip, curl
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       ca-certificates \
       apt-transport-https \
       software-properties-common \
       curl \
       gnupg \
       lsb-release \
       unzip \
       git \
       supervisor \
       nginx \
       openssh-server \
    && add-apt-repository ppa:ondrej/php -y \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
       php8.3-cli php8.3-fpm php8.3-mbstring php8.3-xml php8.3-curl \
       php8.3-zip php8.3-bcmath php8.3-mysql php8.3-sqlite3 php8.3-intl \
       php8.3-redis \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js 20 and npm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# Install Chromium for Browsershot/PDF generation
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       chromium-browser \
       fonts-liberation \
    && rm -rf /var/lib/apt/lists/*

# Install Puppeteer globally and set environment variable to use system Chromium
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true \
    PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser
RUN npm install -g puppeteer

# Install Composer
RUN curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php \
    && php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm /tmp/composer-setup.php

# Create app user and prepare directories
RUN useradd -m -d /home/${APP_USER} -s /bin/bash ${APP_USER} \
    && mkdir -p /var/run/sshd /run/php ${APP_DIR} \
    && chown -R www-data:www-data ${APP_DIR}

WORKDIR ${APP_DIR}

# Copy application (use .dockerignore to exclude node_modules/vendor if desired)
COPY --chown=www-data:www-data . ${APP_DIR}

# Git add safe directory
RUN git config --global --add safe.directory ${APP_DIR}

# Run composer install
RUN cd /var/www/html && composer install --no-interaction --prefer-dist --optimize-autoloader

# Nginx configuration
COPY docker/nginx/wochenplan.conf /etc/nginx/sites-available/wochenplan.conf
RUN rm -f /etc/nginx/sites-enabled/default \
    && ln -s /etc/nginx/sites-available/wochenplan.conf /etc/nginx/sites-enabled/wochenplan.conf

# PHP-FPM configuration tweaks (ensure correct socket path exists)
RUN sed -i 's#^;*listen = .*#listen = /run/php/php8.3-fpm.sock#' /etc/php/8.3/fpm/pool.d/www.conf \
    && sed -i 's#^;*clear_env = .*#clear_env = no#' /etc/php/8.3/fpm/pool.d/www.conf

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
