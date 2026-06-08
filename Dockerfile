FROM        php:8.3-apache

LABEL       author="Kiarash Bashokian" \
            maintainer="kiararsh@yorku.ca"

ARG         MOODLE_VERSION=v4.5.2

#           SYSTEM DEPENDENCIES & PHP EXTENSIONS

RUN         apt-get update \
            && apt-get install -y --no-install-recommends \
               git \
               curl \
               libpng-dev \
               libjpeg62-turbo-dev \
               libfreetype6-dev \
               libzip-dev \
               libicu-dev \
               libxml2-dev \
               libxslt1-dev \
               zlib1g-dev \
               libonig-dev \
            && docker-php-ext-configure gd --with-freetype --with-jpeg \
            && docker-php-ext-install \
               gd \
               intl \
               mbstring \
               mysqli \
               opcache \
               pdo \
               pdo_mysql \
               soap \
               xsl \
               zip \
            && pecl install redis \
            && docker-php-ext-enable redis \
            && apt-get clean \
            && rm -rf /var/lib/apt/lists/*

#           PHP CONFIGURATION

RUN         { \
               echo "max_input_vars = 5000"; \
               echo "memory_limit = 512M"; \
               echo "post_max_size = 100M"; \
               echo "upload_max_filesize = 100M"; \
               echo "opcache.enable = 1"; \
               echo "opcache.revalidate_freq = 60"; \
            } > /usr/local/etc/php/conf.d/moodle.ini

#           APACHE CONFIGURATION

RUN         a2enmod rewrite ssl headers

RUN         printf '<Directory /var/www/html>\n\tOptions Indexes FollowSymLinks\n\tAllowOverride All\n\tRequire all granted\n</Directory>\n' \
               > /etc/apache2/conf-available/moodle.conf \
            && a2enconf moodle

#           MOODLE INSTALL

RUN         git clone --depth 1 --branch ${MOODLE_VERSION} \
               https://github.com/moodle/moodle /var/www/html \
            && find /var/www/html -type d -exec chmod 755 {} \; \
            && find /var/www/html -type f -exec chmod 644 {} \; \
            && chown -R www-data:www-data /var/www/html

#           CRIA PLUGIN

COPY        --chown=www-data:www-data . /var/www/html/local/cria/

#           MOODLEDATA DIRECTORY

RUN         mkdir -p /var/www/moodledata \
            && chown www-data:www-data /var/www/moodledata \
            && chmod 755 /var/www/moodledata

VOLUME      ["/var/www/moodledata"]

EXPOSE      80
