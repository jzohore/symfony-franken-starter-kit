FROM dunglas/frankenphp AS frankenphp_base

# Install system dependencies including ca-certificates
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    nano \
    vim \
    ca-certificates \
    curl \
    build-essential \
    make \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN install-php-extensions \
    pdo_pgsql \
    zip \
    opcache \
    redis \
    amqp \
    intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 🔥 Install Node.js 20 LTS & npm via NodeSource
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Verify installations
RUN node --version && npm --version && make --version

# Create Caddy data directories
RUN mkdir -p /data/caddy /config/caddy && \
    chown -R www-data:www-data /data/caddy /config/caddy

# Set working directory
WORKDIR /srv/app

# Fix Git ownership issue
RUN git config --system --add safe.directory '*'

# ========================================
# Production target
# ========================================
FROM frankenphp_base AS frankenphp_prod

ENV APP_ENV=prod
ENV FRANKENPHP_CONFIG="worker ./public/index.php"

COPY . /srv/app

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction && \
    composer dump-autoload --optimize --classmap-authoritative

# 🔥 Install & build npm dependencies
RUN npm ci --only=production && \
    npm run build && \
    npm cache clean --force

# Fix permissions
RUN chown -R www-data:www-data /srv/app

EXPOSE 80 443

# ========================================
# Dev target
# ========================================
FROM frankenphp_base AS frankenphp_dev

ENV APP_ENV=dev

EXPOSE 80 443
