#!/bin/sh

set -e

#echo "Initiating entrypoint-dev with args: $@"
if [  ! -z "$XDEBUG_ENABLED" ] ; then
  if [ "$XDEBUG_ENABLED" -eq 1 ] ; then
		echo "Enabling XDEBUG - see README.md for setup instructions"
		pear config-set php_ini "$PHP_INI_DIR/php.ini"
		pecl install xdebug-3.0.4
		#echo "enabling xdebug"
		#docker-php-ext-enable xdebug
		#echo "done xdebug"
		echo "xdebug.mode=develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
	  echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
	  rm "/usr/local/etc/php/conf.d/docker-php-ext-opcache.ini"
	fi
fi

# Update the mapped CA certificates from docker-compose.yml for localhost ssl
# The file should be placed in /usr/local/share/ca-certificates/ and must have a .crt suffix
update-ca-certificates

# execute default entrypoint
echo "Executing docker-php-entrypoint with: $@"
docker-php-entrypoint $@
echo "Main Done"
