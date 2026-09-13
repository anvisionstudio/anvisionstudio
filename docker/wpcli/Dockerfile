FROM wordpress:cli-php8.3

USER root
RUN apk add --no-cache less mariadb-client \
	&& mkdir -p /var/www/html \
	&& chown -R www-data:www-data /var/www/html
USER www-data

WORKDIR /var/www/html
