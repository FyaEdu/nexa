#!/usr/bin/env bash
cd "$(dirname "$0")"
(cd backend && php -S localhost:8080 server.php) &
BACK=$!
trap 'kill $BACK 2>/dev/null' EXIT
cd frontend && npm run dev
