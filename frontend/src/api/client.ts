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