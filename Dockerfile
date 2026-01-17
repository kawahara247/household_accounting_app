FROM php:8.5-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    apache2-utils \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libsqlite3-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite mbstring exif pcntl bcmath gd

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# 全部コピー
COPY . .

# Laravel依存パッケージ
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# SQLiteファイルがなければ作る
RUN if [ ! -f database-data/database.sqlite ]; then mkdir -p database-data && touch database-data/database.sqlite; fi

# 権限
RUN chown -R www-data:www-data storage bootstrap/cache database-data

# Copy Apache configuration
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
