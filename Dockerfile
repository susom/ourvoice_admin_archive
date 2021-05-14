FROM php:8.0.6-apache-buster

# Configure PHP for Cloud Run.
# Precompile PHP code with opcache.
RUN docker-php-ext-install -j "$(nproc)" opcache
RUN set -ex; \
  { \
    echo "; Cloud Run enforces memory & timeouts"; \
    echo "memory_limit = -1"; \
    echo "max_execution_time = 0"; \
    echo "; File upload at Cloud Run network limit"; \
    echo "upload_max_filesize = 32M"; \
    echo "post_max_size = 32M"; \
    echo "; Configure Opcache for Containers"; \
    echo "opcache.enable = On"; \
    echo "opcache.validate_timestamps = Off"; \
    echo "; Configure Opcache Memory (Application-specific)"; \
    echo "opcache.memory_consumption = 32"; \
  } > "$PHP_INI_DIR/conf.d/cloud-run.ini"

RUN set -eux; \
  apt-get update; \
  apt-get install -y --no-install-recommends \
     wget \
     ca-certificates \
     libpng-dev \
     libzip-dev \
     zip \
# INSTALL OPENIDC
     libcjose0 libhiredis0.14 \
  && wget https://github.com/zmartzone/mod_auth_openidc/releases/download/v2.4.6/libapache2-mod-auth-openidc_2.4.6-1.buster+1_amd64.deb \
  && dpkg -i libapache2-mod-auth-openidc_2.4.6-1.buster+1_amd64.deb \
  && a2enmod auth_openidc \
  && a2enmod headers \
# CLEANUP
  && rm -rf /var/log/dpkg.log /var/log/alternatives.log /var/log/apt \
  && rm libapache2-mod-auth-openidc_2.4.6-1.buster+1_amd64.deb

# REPLACE DEFAULT SITE
ADD 000-default.conf /etc/apache2/sites-available/000-default.conf

# Use the PORT environment variable in Apache configuration files.
# https://cloud.google.com/run/docs/reference/container-contract#port
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Configure PHP for development.
# Switch to the production php.ini for production operations.
# RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
# https://github.com/docker-library/docs/blob/master/php/README.md#configuration
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
## ADD A PHP.INI FILE
#ADD php.ini /usr/local/etc/php/php.ini

# ADD BUILD WEBROOT TO CONTAINER
# Copy in custom code from the host machine.
WORKDIR /var/www/html
COPY . ./
