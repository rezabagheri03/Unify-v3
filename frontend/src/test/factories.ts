/**
 * Shared test factories (TODO-048). Shapes mirror the API payloads the
 * components actually read — keep them minimal on purpose: an extra field
 * here can hide a missing one in the component contract.
 */

let seq = 0;
const nextId = () => `id-${++seq}-${Date.now()}`;

export function makeUser(overrides: Record<string, unknown> = {}) {
  return {
    id: '400100001',
    first_name: 'سارا',
    last_name: 'احمدی',
    role: 'student',
    must_change_password: false,
    ...overrides,
  };
}

export function makeNotification(overrides: Record<string, unknown> = {}) {
  return {
    id: nextId(),
    type: 'general',
    title: 'اطلاعیه',
    body: '',
    created_at: new Date().toISOString(),
    ...overrides,
  };
}

export function makeSpec(overrides: Record<string, unknown> = {}) {
  return {
    id: nextId(),
    course_id: 'CS101',
    course: { name: 'ریاضی ۱' },
    professor: { id: 'P1001', first_name: 'احمد', last_name: 'رضایی' },
    day_of_week: 'شنبه',
    time_start: '08:00',
    time_end: '10:00',
    ...overrides,
  };
}

export function makeSuggestion(overrides: Record<string, unknown> = {}) {
  return {
    score: 92,
    credits: 14,
    explanation: 'کمترین فاصله خالی بین کلاس‌ها',
    specs: [makeSpec(), makeSpec({ day_of_week: 'یکشنبه', time_start: '10:00', time_end: '12:00' })],
    ...overrides,
  };
}
