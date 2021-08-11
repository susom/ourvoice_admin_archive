# Pre-configuration files on base image
FROM gcr.io/som-rit-ourvoice/ourvoice_base:latest


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
COPY app .

ADD entrypoint-dev.sh /usr/local/bin/entrypoint-dev.sh
RUN chmod +x /usr/local/bin/entrypoint-dev.sh