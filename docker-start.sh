#!/usr/bin/env bash
cd "$(dirname "$0")"
docker compose up -d --build || exit 1
echo "Menunggu NEXA siap (pertama kali bisa beberapa menit). Log: docker compose logs -f"
until curl -s -o /dev/null http://localhost:5173/; do sleep 5; done
echo "NEXA berjalan di http://localhost:5173 (hentikan: docker compose down)"
(command -v open >/dev/null && open http://localhost:5173) || (command -v xdg-open >/dev/null && xdg-open http://localhost:5173) || true
