#!/usr/bin/env bash
# Install WordPress + PHPUnit test library from GitHub (no SVN required).
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP_VERSION="${WP_VERSION:-6.7.2}"
WP_TESTS_DIR="${WP_TESTS_DIR:-${ROOT}/tmp/wordpress-tests-lib}"
WP_CORE_DIR="${WP_CORE_DIR:-${ROOT}/tmp/wordpress}"

DB_NAME="${WP_TEST_DB_NAME:-wordpress_test}"
DB_USER="${WP_TEST_DB_USER:-wp}"
DB_PASS="${WP_TEST_DB_PASS:-wp}"
DB_HOST="${WP_TEST_DB_HOST:-127.0.0.1}"

mkdir -p "${ROOT}/tmp"

if [ ! -f "${WP_CORE_DIR}/wp-settings.php" ]; then
	echo "Downloading WordPress ${WP_VERSION}..."
	curl -sSL "https://wordpress.org/wordpress-${WP_VERSION}.tar.gz" -o "${ROOT}/tmp/wordpress.tar.gz"
	rm -rf "${WP_CORE_DIR}" "${ROOT}/tmp/wordpress"
	tar -xzf "${ROOT}/tmp/wordpress.tar.gz" -C "${ROOT}/tmp"
	mv "${ROOT}/tmp/wordpress" "${WP_CORE_DIR}"
fi

if [ ! -f "${WP_TESTS_DIR}/includes/functions.php" ]; then
	echo "Downloading WordPress develop test library..."
	curl -sSL "https://github.com/WordPress/wordpress-develop/archive/refs/tags/${WP_VERSION}.tar.gz" \
		-o "${ROOT}/tmp/wordpress-develop.tar.gz"
	rm -rf "${ROOT}/tmp/wordpress-develop-${WP_VERSION}" "${WP_TESTS_DIR}"
	tar -xzf "${ROOT}/tmp/wordpress-develop.tar.gz" -C "${ROOT}/tmp"
	mkdir -p "${WP_TESTS_DIR}"
	cp -R "${ROOT}/tmp/wordpress-develop-${WP_VERSION}/tests/phpunit/includes" "${WP_TESTS_DIR}/includes"
	cp -R "${ROOT}/tmp/wordpress-develop-${WP_VERSION}/tests/phpunit/data" "${WP_TESTS_DIR}/data"
fi

cat > "${ROOT}/wp-tests-config.php" <<EOF
<?php
define( 'ABSPATH', '${WP_CORE_DIR}/' );
define( 'DB_NAME', '${DB_NAME}' );
define( 'DB_USER', '${DB_USER}' );
define( 'DB_PASSWORD', '${DB_PASS}' );
define( 'DB_HOST', '${DB_HOST}' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );
\$table_prefix = 'wptests_';
define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
EOF

echo "WP tests ready:"
echo "  core  ${WP_CORE_DIR}"
echo "  tests ${WP_TESTS_DIR}"
echo "  config ${ROOT}/wp-tests-config.php"
