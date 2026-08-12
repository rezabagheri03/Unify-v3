import { get as idbGet, set as idbSet, del as idbDel } from 'idb-keyval';

/**
 * Safe storage that works everywhere — including sandboxed iframes with an
 * opaque origin (e.g. the in-app preview) and private-browsing contexts where
 * IndexedDB / localStorage throw SecurityError.
 *
 * Semantics are RAW STRING (like localStorage): values are stored as-is and
 * returned as-is. Callers that need objects JSON-encode/decode themselves
 * (see db/idb.ts).
 *
 * Tiers (each falls back on failure):
 *   1. IndexedDB (full persistence in normal browsers)
 *   2. localStorage (survives reloads in same-origin contexts / webviews)
 *   3. in-memory Map (session-only, works literally everywhere)
 */

const memory = new Map<string, unknown>();
let idbBroken = false;
let lsBroken = false;
const lsMemory = new Map<string, string>();

function lsTry(): boolean {
  if (lsBroken) return false;
  try {
    if (typeof window === 'undefined' || typeof window.localStorage === 'undefined') return false;
    const k = '__unify_probe__';
    window.localStorage.setItem(k, '1');
    window.localStorage.removeItem(k);
    return true;
  } catch {
    lsBroken = true;
    return false;
  }
}

function lsRawGet(key: string): string | null {
  if (lsTry()) {
    try {
      const v = window.localStorage.getItem(key);
      if (v !== null) return v;
    } catch {
      lsBroken = true;
    }
  }
  return lsMemory.get(key) ?? null;
}

function lsRawSet(key: string, value: string): void {
  if (lsTry()) {
    try {
      window.localStorage.setItem(key, value);
      return;
    } catch {
      lsBroken = true;
    }
  }
  lsMemory.set(key, value);
}

function lsRawDel(key: string): void {
  if (lsTry()) {
    try {
      window.localStorage.removeItem(key);
      return;
    } catch {
      lsBroken = true;
    }
  }
  lsMemory.delete(key);
}

async function idbUsable(): Promise<boolean> {
  if (idbBroken) return false;
  try {
    if (typeof indexedDB === 'undefined') {
      idbBroken = true;
      return false;
    }
    await idbGet('__unify_probe__');
    return true;
  } catch {
    idbBroken = true;
    return false;
  }
}

export async function storageGet(key: string): Promise<string | null> {
  if (await idbUsable()) {
    try {
      const v = await idbGet<string>(key);
      if (v !== undefined && v !== null) return String(v);
    } catch {
      idbBroken = true;
    }
  }
  const raw = lsRawGet(key);
  if (raw !== null) return raw;
  const m = memory.get(key);
  return m === undefined || m === null ? null : String(m);
}

export async function storageSet(key: string, value: string): Promise<void> {
  if (await idbUsable()) {
    try {
      await idbSet(key, value);
      return;
    } catch {
      idbBroken = true;
    }
  }
  lsRawSet(key, value);
  memory.set(key, value);
}

export async function storageDel(key: string): Promise<void> {
  if (await idbUsable()) {
    try {
      await idbDel(key);
    } catch {
      idbBroken = true;
    }
  }
  lsRawDel(key);
  memory.delete(key);
}

/** localStorage with in-memory fallback (for polling timestamps etc.). */
export function lsGet(key: string): string | null {
  return lsRawGet(key);
}

export function lsSet(key: string, value: string): void {
  lsRawSet(key, value);
}
