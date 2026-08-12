/**
 * Role-aware home paths (P00 sitemap). After login/onboarding the app must
 * route each role to its own home, not hardcode the student dashboard.
 */
export function homePathFor(role?: string | null): string {
  switch (role) {
    case 'professor':
      return '/professor/dashboard';
    case 'expert':
      return '/expert/dashboard';
    case 'head_of_dept':
      return '/head/approvals';
    case 'admin':
      return '/admin/dashboard';
    case 'owner':
      return '/owner/dashboard';
    case 'student':
    default:
      return '/dashboard';
  }
}
