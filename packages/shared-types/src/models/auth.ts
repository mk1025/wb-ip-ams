import type { PaginatedResponse } from "../common.types";

// ROLE

export const Role = {
  User: "user",
  SuperAdmin: "super-admin",
} as const;

export type Role = (typeof Role)[keyof typeof Role];

// USER

export interface TableUser {
  id: number;
  email: string;
  role: Role;
  created_at: string;
  updated_at: string;
}

// SESSIONS

export interface TableSessions {
  id: number;
  user_id: number | null;
  ip_address: string | null;
  user_agent: string | null;
  payload: string;
  last_activity: number;
}

// AUDIT LOGS

export interface TableAuditLogs {
  id: number;
  user_id: number | null;
  action: "login" | "logout" | "register" | "token_refresh";
  ip_address: string | null;
  user_agent: string | null;
  session_id: string | null;
  created_at: string;
}

// RESOURCES

export interface UserResource extends Omit<TableUser, "password"> {}

export interface AuthResource {
  user: UserResource;
  tokens: {
    access_token: string;
    token_type: string;
    expires_in: number;
  };
}

export interface TokenResource {
  access_token: string;
  token_type: "bearer";
  expires_in: number;
}

export interface AuthAuditLogResource extends TableAuditLogs {
  user_email: string | null;
}

// AUDIT FILTER OPTIONS

export interface AuditUserOption {
  id: number;
  email: string;
  count: number;
}

export interface AuditActionOption {
  value: string;
  count: number;
}

// AUDIT LOG RESPONSE

export interface AuthAuditLogsResponse {
  logs: PaginatedResponse<AuthAuditLogResource>;
  filter_options: {
    users: AuditUserOption[];
    actions: AuditActionOption[];
  };
}
