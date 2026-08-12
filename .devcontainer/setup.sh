#!/bin/bash
set -e

echo "=== Unify V9 Codespace Auto Setup ==="

# Fix yarn GPG issue that breaks universal image
if [ -f /etc/apt/sources.list.d/yarn.list ]; then
  echo "Removing broken yarn apt source..."
  sudo rm -f /etc/apt/sources.list.d/yarn.list || true
fi

# Detect repo root (works in /workspaces/Unify-v3, /home/user/Unify-v3, or any subdir)
if [ -d "/workspaces/Unify-v3/unify-backend" ]; then
  cd /workspaces/Unify-v3/unify-backend
elif [ -d "/home/user/Unify-v3/unify-backend" ]; then
  cd /home/user/Unify-v3/unify-backend
elif [ -d "unify-backend" ]; then
  cd unify-backend
elif [ -d "../unify-backend" ]; then
  cd ../unify-backend
else
  ROOT=$(git rev-parse --show-toplevel 2>/dev/null || pwd)
  cd "$ROOT/unify-backend"
fi

echo "Backend path: $(pwd)"

if [ ! -f .env ]; then
  cp .env.example .env
  echo ".env created from .env.example"
fi

# Force SQLite for Codespaces
echo "Configuring SQLite for Codespace..."
mkdir -p database
touch database/database.sqlite
# Update .env
if grep -q "DB_CONNECTION=mysql" .env; then
  sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env
fi
# Ensure APP_URL and CORS for codespace
sed -i 's|APP_URL=.*|APP_URL=http://127.0.0.1:8000|' .env
sed -i 's|FRONTEND_URL=.*|FRONTEND_URL=http://localhost:5173|' .env
sed -i 's|API_URL=.*|API_URL=http://127.0.0.1:8000/api|' .env
# Allow any github.dev domain for Sanctum
sed -i 's|SANCTUM_STATEFUL_DOMAINS=.*|SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173,*.app.github.dev|' .env

# Check composer
if ! command -v composer &> /dev/null; then
  echo "Composer not found, installing..."
  curl -sS https://getcomposer.org/installer | php
  sudo mv composer.phar /usr/local/bin/composer
fi

echo "Installing composer deps..."
composer install --no-interaction --prefer-dist

echo "Generating key..."
php artisan key:generate --force

echo "Migrating and seeding (this creates test users)..."
php artisan migrate --seed --force || (php artisan migrate --force && php artisan db:seed --force)

echo "Linking storage..."
php artisan storage:link || true
php artisan config:clear
php artisan cache:clear

# Frontend
if [ -d "../frontend" ]; then
  cd ../frontend
elif [ -d "frontend" ]; then
  cd frontend
elif [ -d "/workspaces/Unify-v3/frontend" ]; then
  cd /workspaces/Unify-v3/frontend
elif [ -d "/home/user/Unify-v3/frontend" ]; then
  cd /home/user/Unify-v3/frontend
else
  ROOT=$(git rev-parse --show-toplevel 2>/dev/null || pwd)
  cd "$ROOT/frontend"
fi

echo "Frontend path: $(pwd)"

echo "Installing npm deps..."
npm install

# Fix API client for Codespace proxy
echo "Creating frontend/.env.local with VITE_API_URL=/api (uses Vite proxy)"
echo "VITE_API_URL=/api" > .env.local

# Patch client.ts if it still has hardcoded 127.0.0.1 - make backup and replace
if grep -q "127.0.0.1:8000/api" src/api/client.ts; then
  echo "Patching src/api/client.ts to use /api proxy..."
  cat > src/api/client.ts << 'EOF'
import axios from 'axios';
import { get } from 'idb-keyval';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  }
});

api.interceptors.request.use(async (config) => {
  const token = await get('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  if (['post', 'put', 'patch', 'delete'].includes(config.method || '')) {
    config.headers['Idempotency-Key'] = crypto.randomUUID();
  }
  return config;
});

export default api;
EOF
fi

echo ""
echo "=== Setup Done ==="
echo "To run:"
echo "  bash start-codespace.sh"
echo "  OR:"
echo "  Terminal 1: cd unify-backend && php artisan serve --host=0.0.0.0 --port=8000"
echo "  Terminal 2: cd frontend && npm run dev -- --host 0.0.0.0 --port=5173"
echo ""
echo "Test users: 990000001 / TempOwner!2026 (Owner), 100000001 / TempStudent!2026 (Student) etc."
echo "Ports: Check PORTS tab - forward 8000 and 5173 as Public"
