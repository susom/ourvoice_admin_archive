#!/bin/sh

set -e
if [ ! -z "$LOCAL_DEV" ] ; then
	if [ "$LOCAL_DEV" -eq 1 ] ; then
		echo "running in development mode"
		echo "enabling ssl"
		a2enmod ssl
		# When running locally, switches PHP to dev mode
		cp -r "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

		# Update the mapped CA certificates from docker-compose.yml for localhost ssl
		# The file should be placed in /usr/local/share/ca-certificates/ and must have a .crt suffix
		
		update-ca-certificates
	fi 
fi 


if [  ! -z "$XDEBUG_ENABLED" ] ; then
  if [ "$XDEBUG_ENABLED" -eq 1 ] ; then
		echo "Enabling XDEBUG - see README.md for setup instructions"
		pear config-set php_ini "$PHP_INI_DIR/php.ini"
    if ! OUTPUT=$(grep -c -q xdebug /usr/local/etc/php/php.ini); then
      echo "Installing XDEBUG"
      # pear config-set php_ini "$PHP_INI_DIR/php.ini"
      pecl install xdebug-3.0.4
    fi
		echo "xdebug.mode=develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
		echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
		rm "/usr/local/etc/php/conf.d/docker-php-ext-opcache.ini"
  fi
fi

if [  ! -z "$PORT" ] ; then
	# Use the PORT environment variable in Apache configuration files.
	# https://cloud.google.com/run/docs/reference/container-contract#port
	echo "updating port to $PORT"
	sed -i "s/80/$PORT/g" /etc/apache2/sites-enabled/vhost.conf /etc/apache2/ports.conf
fi

# execute default entrypoint
echo "Executing docker-php-entrypoint with: $@"
docker-php-entrypoint $@
echo "Main Done"





