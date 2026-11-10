#!/usr/bin/env bash
set -u

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
HOST="${MAMBO_HOST:-127.0.0.1}"
PORT="${MAMBO_PORT:-8000}"
URL="http://${HOST}:${PORT}/index.html"
HEALTH_URL="http://${HOST}:${PORT}/api/index.php?action=health"
LOG_FILE="$ROOT_DIR/data/mambo-server.log"

pause_on_error() {
    read -r -p "Press Enter to close..." _ || true
}

if ! command -v php >/dev/null 2>&1; then
    echo "PHP is not installed. Install PHP 8.2 or newer, then try again." >&2
    pause_on_error
    exit 1
fi

if ! php -m | grep -q '^pdo_sqlite$' || ! php -m | grep -q '^sqlite3$'; then
    echo "PHP pdo_sqlite and sqlite3 extensions are required." >&2
    echo "Install the SQLite extension for your PHP version, then try again." >&2
    pause_on_error
    exit 1
fi

if ! curl -fsS "$HEALTH_URL" >/dev/null 2>&1; then
    mkdir -p "$ROOT_DIR/data"
    echo "Starting Mambo ya Tanga at ${URL}"
    if command -v setsid >/dev/null 2>&1; then
        setsid php -S "${HOST}:${PORT}" -t "$ROOT_DIR" >>"$LOG_FILE" 2>&1 < /dev/null &
    else
        php -S "${HOST}:${PORT}" -t "$ROOT_DIR" >>"$LOG_FILE" 2>&1 < /dev/null &
    fi

    ready=0
    for _ in {1..30}; do
        if curl -fsS "$HEALTH_URL" >/dev/null 2>&1; then
            ready=1
            break
        fi
        sleep 0.2
    done

    if [ "$ready" -ne 1 ]; then
        echo "The website could not start. Check ${LOG_FILE} for details." >&2
        pause_on_error
        exit 1
    fi
else
    echo "Mambo ya Tanga is already running."
fi

echo "Website:  ${URL}"
echo "Staff:    http://${HOST}:${PORT}/admin.html"
echo "Server log: ${LOG_FILE}"

if command -v xdg-open >/dev/null 2>&1; then
    xdg-open "$URL" >/dev/null 2>&1 &
elif command -v open >/dev/null 2>&1; then
    open "$URL" >/dev/null 2>&1 &
fi

exit 0
