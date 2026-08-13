import '@testing-library/jest-dom';
import 'fake-indexeddb/auto';
import { TextEncoder, TextDecoder } from 'util';

// react-router v7 reads TextEncoder/TextDecoder at module load; jsdom does not
// provide them (Node's `util` does).
if (typeof globalThis.TextEncoder === 'undefined') {
  (globalThis as any).TextEncoder = TextEncoder;
  (globalThis as any).TextDecoder = TextDecoder;
}

// jsdom lacks matchMedia — required by MUI responsive components
if (typeof window !== 'undefined' && !window.matchMedia) {
  (window as any).matchMedia = (query: string) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: () => {},
    removeListener: () => {},
    addEventListener: () => {},
    removeEventListener: () => {},
    dispatchEvent: () => false,
  });
}
