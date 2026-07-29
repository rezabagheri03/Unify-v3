#!/bin/bash
set -e

echo "=== Unify V9 Codespace Auto Setup ==="

# Backend setup - SQLite easiest for codespace
cd /home/user/Unify-v3/unify-backend || cd unify-backend

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

echo "Installing composer deps..."
composer install --no-interaction --prefer-dist

echo "Generating key..."
php artisan key:generate --force

echo "Migrating and seeding (this creates test users)..."
php artisan migrate --seed --force || php artisan migrate --force && php artisan db:seed --force

echo "Linking storage..."
php artisan storage:link || true
php artisan config:clear
php artisan cache:clear

cd ../frontend || cd /home/user/Unify-v3/frontend

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
echo "Terminal 1: cd unify-backend && php artisan serve --host=0.0.0.0 --port=8000"
echo "Terminal 2: cd frontend && npm run dev -- --host 0.0.0.0 --port=5173"
echo ""
echo "Test users: 990000001 / TempOwner!2026 (Owner), 100000001 / TempStudent!2026 (Student) etc."
echo "Ports: Check PORTS tab - forward 8000 and 5173 as Public"
