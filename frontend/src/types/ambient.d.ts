/**
 * Ambient declarations for packages that ship no TypeScript types.
 * Keeps `strict: true` green WITHOUT pulling in @types/node globally
 * (which would change setInterval/crypto inference app-wide).
 */

// stylis v4 dropped its bundled .d.ts; emotion's StylisPlugin type is the
// canonical shape and @emotion/cache (a direct dep) ships it.
declare module 'stylis' {
  import type { StylisPlugin } from '@emotion/cache';
  export const prefixer: StylisPlugin;
}

// Jest-only: jsdom lacks TextEncoder/TextDecoder; setupTests.ts borrows
// Node's `util` implementations. Declared here so src/ stays node-types-free.
declare module 'util' {
  export const TextEncoder: new () => { encode(input?: string): Uint8Array };
  export const TextDecoder: new (label?: string, options?: unknown) => {
    decode(input?: ArrayBufferView | ArrayBuffer | null): string;
  };
}
