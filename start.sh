#!/usr/bin/env bash
cd "$(dirname "$0")"
(cd backend && php spark serve) &
BACK=$!
trap 'kill $BACK 2>/dev/null' EXIT
cd frontend && npm run dev
