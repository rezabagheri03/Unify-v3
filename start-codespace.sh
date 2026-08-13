#!/bin/bash
# Unify V9 - One command Codespace runner
# Usage: bash start-codespace.sh
# Does setup + starts both servers

set -e

# Detect repo root (works in /workspaces/Unify-v3, /home/user/Unify-v3, or any subdir)
if [ -f "unify-backend/artisan" ]; then
  ROOT=$(pwd)
elif [ -f "../unify-backend/artisan" ]; then
  ROOT=$(cd .. && pwd)
else
  # find git root
  ROOT=$(git rev-parse --show-toplevel 2>/dev/null || pwd)
fi

echo "Root: $ROOT"
cd "$ROOT"

echo "=== STEP 1: Setup (if not done) ==="
bash .devcontainer/setup.sh

echo ""
echo "=== STEP 2: Starting servers ==="
# Kill old servers if any
pkill -f "artisan serve" || true
pkill -f "vite" || true
sleep 1

# Start backend in background
cd "$ROOT/unify-backend"
echo "Starting Laravel on 0.0.0.0:8000..."
nohup php artisan serve --host=0.0.0.0 --port=8000 > /tmp/backend.log 2>&1 &
BACKEND_PID=$!
echo "Backend PID $BACKEND_PID - log: /tmp/backend.log"

# Start frontend in background
cd "$ROOT/frontend"
echo "Starting Vite on 0.0.0.0:5173..."
nohup npm run dev -- --host 0.0.0.0 --port=5173 > /tmp/frontend.log 2>&1 &
FRONTEND_PID=$!
echo "Frontend PID $FRONTEND_PID - log: /tmp/frontend.log"

sleep 3
echo ""
echo "=== CHECK LOGS ==="
echo "--- Backend (last 30 lines) ---"
tail -n 30 /tmp/backend.log || cat /tmp/backend.log
echo ""
echo "--- Frontend (last 30 lines) ---"
tail -n 30 /tmp/frontend.log || cat /tmp/frontend.log

echo ""
echo "=== READY ==="
echo "Backend:  http://127.0.0.1:8000 - Health: /api/health"
echo "Frontend: http://127.0.0.1:5173"
echo ""
echo "In GitHub Codespaces PORTS tab:"
echo "- Forward 8000 as Public (Backend API)"
echo "- Forward 5173 as Public (Frontend) - click Open in Browser"
echo ""
echo "Login: 990000001 / TempOwner!2026"
echo ""
echo "Logs: tail -f /tmp/backend.log  &  tail -f /tmp/frontend.log"
echo "Stop: pkill -f 'artisan serve'; pkill -f vite"
