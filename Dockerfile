FROM php:8.3-apache

# Install MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache modules: rewrite (used by .htaccess RewriteRules) and
# headers (required for .htaccess "Header always set ..." directives —
# without this, security headers like CSP/HSTS/X-Frame-Options are
# silently ignored even if .htaccess is otherwise correct)
RUN a2enmod rewrite headers

# Copy project files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# The base php:apache image sets AllowOverride None by default, which
# means .htaccess rules (blocking direct access to .env, env.php,
# db_config.php, etc.) are silently ignored. Explicitly allow overrides
# so those protections actually take effect, same as they did on
# InfinityFree (which honors .htaccess automatically).
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

EXPOSE 80