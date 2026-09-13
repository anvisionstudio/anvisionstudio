FROM wordpress:php8.3-apache

ARG UID=1000
ARG GID=1000

RUN apt-get update \
	&& apt-get install -y --no-install-recommends \
		curl \
		git \
		less \
		libicu-dev \
		libzip-dev \
		mariadb-client \
		unzip \
	&& docker-php-ext-install -j"$(nproc)" intl zip \
	&& pecl install xdebug redis \
	&& docker-php-ext-enable xdebug redis \
	&& rm -rf /var/lib/apt/lists/*

# Align www-data with the host user so bind-mounted wp-content stays writable.
RUN groupmod -o -g "${GID}" www-data \
	&& usermod -o -u "${UID}" -g www-data www-data \
	&& mkdir -p /var/www/html \
	&& chown -R www-data:www-data /var/www/html

COPY --from=composer:2.8 /usr/bin/composer /usr/local/bin/composer

COPY php.ini /usr/local/etc/php/conf.d/zz-anvision.ini
COPY xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
