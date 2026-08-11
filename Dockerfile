FROM php:8.3-apache

# Install MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# dompdf (used to generate real PDF receipts) requires mbstring, dom,
# and xml. These are compiled into most official php images already,
# but docker-php-ext-install is safe to re-run either way. libxml2-dev
# provides the headers needed to build dom/xml if they aren't already
# present. (GD/freetype/libjpeg were dropped — this receipt is text
# only, no embedded images, so they're not needed and were the most
# likely cause of the previous apt-get build failure.)
RUN apt-get update && apt-get install -y libxml2-dev \
    && docker-php-ext-install mbstring dom xml \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install Composer (needed for dompdf, used to generate real PDF
# receipts instead of relying on the browser's print-to-PDF dialog)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . /var/www/html/

WORKDIR /var/www/html

# Install PHP dependencies (dompdf). --no-dev keeps the image lean;
# --no-interaction avoids the build hanging waiting for prompts.
RUN composer install --no-dev --no-interaction --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# The base php:apache image sets AllowOverride None by default, which
# means .htaccess rules (blocking direct access to .env, env.php,
# db_config.php, etc.) are silently ignored. Explicitly allow overrides
# so those protections actually take effect, same as they did on
# InfinityFree (which honors .htaccess automatically).
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

EXPOSE 80