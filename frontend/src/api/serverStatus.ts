import { useEffect, useState } from 'react';
import api from './client';

export type ServerStatus = 'checking' | 'online' | 'offline';

/**
 * Backend reachability probe — ONE shared /health poller for the entire app.
 *
 * TODO-046 evidence fix: this hook used to start a fresh 15s interval per
 * consumer (Layout + ServerBanner = the same endpoint double-polled on every
 * screen). Now a module-level singleton polls once and fans the status out to
 * subscribers; the interval stops when the last consumer unmounts.
 * The hook's public signature is unchanged.
 */

let current: ServerStatus = 'checking';
// React setState functions are referentially stable, so subscribers' setters
// can be added/removed directly.
const listeners = new Set<(s: ServerStatus) => void>();
let timer: ReturnType<typeof setTimeout> | null = null;
let probing = false;
let intervalMs = 15000;

function emit(status: ServerStatus): void {
  if (current === status) return;
  current = status;
  listeners.forEach((listener) => listener(status));
}

async function probe(): Promise<void> {
  if (probing) return; // never stack overlapping slow probes
  probing = true;
  try {
    // Short timeout so an unreachable backend is detected fast.
    await api.get('/health', { timeout: 4000 });
    emit('online');
  } catch {
    emit('offline');
  } finally {
    probing = false;
  }
}

function tick(): void {
  void probe();
  timer = setTimeout(tick, intervalMs);
}

function start(ms: number): void {
  intervalMs = ms;
  void probe(); // immediate first check when the first subscriber mounts
  timer = setTimeout(tick, intervalMs);
}

function stop(): void {
  if (timer !== null) {
    clearTimeout(timer);
    timer = null;
  }
}

export function useServerStatus(ms = 15000): ServerStatus {
  const [status, setStatus] = useState<ServerStatus>(current);

  useEffect(() => {
    listeners.add(setStatus);
    setStatus(current);
    if (timer === null) start(ms);
    return () => {
      listeners.delete(setStatus);
      if (listeners.size === 0) stop();
    };
  }, [ms]);

  return status;
}
