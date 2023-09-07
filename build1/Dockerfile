FROM php:8.0-apache-buster

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
    echo "opcache.enable = 0"; \
    echo "opcache.validate_timestamps = 0"; \
    echo "; Configure Opcache Memory (Application-specific)"; \
    echo "opcache.memory_consumption = 32"; \
  } > "$PHP_INI_DIR/conf.d/cloud-run.ini"

RUN set -eux; \
  apt-get update; \
  apt-get install -y --no-install-recommends \
     wget \
     ca-certificates \
     libpng-dev \
     libjpeg62-turbo-dev \
     libzip-dev \
     zip \
     vim \
# INSTALL OPENIDC
     libcjose0 libhiredis0.14 \
  && wget https://github.com/zmartzone/mod_auth_openidc/releases/download/v2.4.6/libapache2-mod-auth-openidc_2.4.6-1.buster+1_amd64.deb \
  && dpkg -i libapache2-mod-auth-openidc_2.4.6-1.buster+1_amd64.deb \
  && a2enmod auth_openidc \
  && a2enmod headers \
# ZipArchive
  && docker-php-ext-configure zip \
  && docker-php-ext-install zip \
# GD extention 
  && docker-php-ext-configure gd --with-jpeg \
  && docker-php-ext-install gd \
# CLEANUP
  && rm -rf /var/log/dpkg.log /var/log/alternatives.log /var/log/apt \
  && rm libapache2-mod-auth-openidc_2.4.6-1.buster+1_amd64.deb

# Install grpc extension for PHP
RUN pecl install grpc; \
  docker-php-ext-enable grpc


