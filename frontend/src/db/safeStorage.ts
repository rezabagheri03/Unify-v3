import { get as idbGet, set as idbSet, del as idbDel } from 'idb-keyval';

/**
 * Safe storage that works everywhere — including sandboxed iframes with an
 * opaque origin (e.g. the in-app preview, `sandbox="allow-scripts"` without
 * `allow-same-origin`) and private-browsing contexts where IndexedDB and
 * localStorage throw SecurityError.
 *
 * Strategy: try real IndexedDB first; on the first failure, permanently fall
 * back to an in-memory Map. In-memory means state lives for the session only
 * (reload loses it) — acceptable for previews, and normal browsers still get
 * full persistence.
 */

const memory = new Map<string, unknown>();
let idbBroken = false;

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

export async function storageGet<T>(key: string): Promise<T | undefined> {
  if (await idbUsable()) {
    try {
      return (await idbGet<T>(key)) ?? undefined;
    } catch {
      idbBroken = true;
    }
  }
  return memory.get(key) as T | undefined;
}

export async function storageSet(key: string, value: unknown): Promise<void> {
  if (await idbUsable()) {
    try {
      await idbSet(key, value);
      return;
    } catch {
      idbBroken = true;
    }
  }
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
  memory.delete(key);
}

/** localStorage with in-memory fallback (for polling timestamps etc.). */
const lsMemory = new Map<string, string>();
let lsBroken = false;

export function lsGet(key: string): string | null {
  if (!lsBroken) {
    try {
      return window.localStorage.getItem(key);
    } catch {
      lsBroken = true;
    }
  }
  return lsMemory.get(key) ?? null;
}

export function lsSet(key: string, value: string): void {
  if (!lsBroken) {
    try {
      window.localStorage.setItem(key, value);
      return;
    } catch {
      lsBroken = true;
    }
  }
  lsMemory.set(key, value);
}
