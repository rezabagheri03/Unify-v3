/**
 * API boundary types (TODO-038) — the shapes the Laravel backend actually
 * serializes. Screen components should type their state with these instead of
 * `any`; axios `data` stays `unknown` at the boundary and is narrowed via
 * these interfaces at the call site.
 *
 * NOTE: field names mirror the Laravel JSON exactly (snake_case).
 */

export type Role = 'student' | 'professor' | 'expert' | 'admin' | 'head_of_dept' | 'owner';

/** The PUBLIC projection of a user embedded in other resources (SEC-04 whitelist). */
export interface PublicUser {
  id: string;
  first_name: string;
  last_name: string;
  role: Role;
}

/** Self profile (own PII is fine here). */
export interface CurrentUser extends PublicUser {
  department_id: string | null;
  academic_status_declared?: string;
  is_banned?: boolean;
  must_change_password?: boolean;
  mobile?: string | null;
  email?: string | null;
}

export interface Paginated<T> {
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
}

export type ResourceStatus = 'pending' | 'approved' | 'rejected';

export interface ResourceItem {
  id: string;
  title: string;
  description?: string | null;
  status: ResourceStatus;
  version: number;
  family_id: string;
  previous_version_id?: string | null;
  is_superseded?: boolean;
  is_deleted_content?: boolean;
  file_mime?: string | null;
  file_size_bytes?: number | null;
  average_rating?: number | null;
  rating_count?: number;
  download_count?: number;
  badge_type?: 'professor' | 'golden' | null;
  course?: { id: string; name: string } | null;
  professor?: PublicUser | null;
  created_at_g?: string;
}

export interface Message {
  id: string;
  subject?: string | null;
  body: string;
  sender_id: string;
  recipient_id?: string | null;
  specification_id?: string | null;
  is_deleted?: boolean;
  is_edited?: boolean;
  sent_at?: string;
  sender?: PublicUser | null;
  specification?: { id: string; course?: { id: string; name: string } } | null;
  replies?: Message[];
}

export interface NotificationItem {
  id: string;
  type: string;
  title: string;
  body?: string | null;
  priority: 'low' | 'high' | 'critical';
  read: boolean;
  data?: Record<string, unknown> | null;
  created_at: string;
}

export interface TicketItem {
  id: string;
  subject: string;
  department: string;
  status: 'open' | 'in_progress' | 'closed' | string;
  is_escalated: boolean;
  escalation_level: number;
  created_at?: string;
}

/** Standard error body (docs/13_ERROR_HANDLING). */
export interface ApiErrorBody {
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
  retry_after?: number;
}

/** GET /owner/stats aggregate (PERF-15) + /monitoring/storage overlay. */
export interface OwnerStats {
  users_total: number;
  users_by_role: Record<string, number>;
  users_banned: number;
  users_pending_password: number;
  resources_approved: number;
  resources_pending: number;
  tickets_open: number;
  tickets_escalated: number;
  storage_used_bytes?: number;
  current_semester?: string | null;
  storage?: {
    used_gb?: number;
    limit_gb?: number;
    percentage?: number;
  } | null;
}
