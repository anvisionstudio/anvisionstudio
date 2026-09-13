#!/usr/bin/env bash
# First-time (or reset) WordPress install via WP-CLI.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT}"

if [[ ! -f .env ]]; then
	echo "Missing .env — run: cp .env.example .env" >&2
	exit 1
fi

# Load KEY=VALUE from .env without executing unquoted words.
# Values with spaces must be quoted in .env (see .env.example).
set -a
# shellcheck disable=SC1091
# shellcheck source=/dev/null
source .env
set +a
if [[ "${WP_TITLE:-}" == "" ]]; then
	echo "WP_TITLE is empty after reading .env. Quote values that contain spaces." >&2
	exit 1
fi

placeholder_error() {
	echo "Refusing to install: ${1} is still a placeholder. Edit .env." >&2
	exit 1
}

case "${MYSQL_PASSWORD:-}" in
	""|replace-with-a-strong-local-password) placeholder_error "MYSQL_PASSWORD" ;;
esac
case "${MYSQL_ROOT_PASSWORD:-}" in
	""|replace-with-a-strong-root-password) placeholder_error "MYSQL_ROOT_PASSWORD" ;;
esac
case "${WP_ADMIN_USER:-}" in
	""|replace-with-admin-username) placeholder_error "WP_ADMIN_USER" ;;
esac
case "${WP_ADMIN_PASSWORD:-}" in
	""|replace-with-a-strong-admin-password) placeholder_error "WP_ADMIN_PASSWORD" ;;
esac

COMPOSE="${COMPOSE:-docker compose}"

echo "Waiting for WordPress + WP-CLI..."
for _ in $(seq 1 60); do
	if ${COMPOSE} exec -T wpcli wp core version >/dev/null 2>&1; then
		break
	fi
	sleep 2
done

if ! ${COMPOSE} exec -T wpcli wp core version >/dev/null 2>&1; then
	echo "WP-CLI is not ready. Check: docker compose ps && docker compose logs wordpress" >&2
	exit 1
fi

if ${COMPOSE} exec -T wpcli wp core is-installed >/dev/null 2>&1; then
	echo "WordPress is already installed."
else
	echo "Installing WordPress at ${WP_URL}..."
	${COMPOSE} exec -T wpcli wp core install \
		--url="${WP_URL}" \
		--title="${WP_TITLE}" \
		--admin_user="${WP_ADMIN_USER}" \
		--admin_password="${WP_ADMIN_PASSWORD}" \
		--admin_email="${WP_ADMIN_EMAIL}" \
		--skip-email
fi

if [[ -n "${WP_LOCALE:-}" && "${WP_LOCALE}" != "en_US" ]]; then
	${COMPOSE} exec -T wpcli wp language core install "${WP_LOCALE}" --activate || true
fi

${COMPOSE} exec -T wpcli wp theme activate anvisionstudio
${COMPOSE} exec -T wpcli wp plugin activate anvisionstudio-core
${COMPOSE} exec -T wpcli wp plugin activate image-compressor
${COMPOSE} exec -T wpcli wp rewrite structure '/%postname%/' --hard
${COMPOSE} exec -T wpcli wp option update blogdescription 'Anvision Studio'
${COMPOSE} exec -T wpcli wp option update timezone_string 'Asia/Taipei' || true

echo
echo "Ready:"
echo "  Site      ${WP_URL}"
echo "  Admin     ${WP_URL}/wp-admin/"
echo "  Adminer   http://localhost:${ADMINER_PORT:-8081}"
echo "  Mailpit   http://localhost:${MAILPIT_UI_PORT:-8025}"
echo
