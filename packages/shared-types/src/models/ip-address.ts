import type { PaginatedResponse } from "../common.types";

// IP-ADDRESS

export interface TableIpAddress {
  id: number;
  ip_address: string;
  label: string;
  comment: string | null;
  owner_id: number;
  owner_email: string | null;
  created_at: string;
  updated_at: string;
}

export interface TableIpAuditLog {
  id: number;
  user_id: number | null;
  user_email: string | null;
  action: "create" | "update" | "delete";
  entity_id: number;
  old_value: Record<string, unknown>; // json
  new_value: Record<string, unknown>; // json
  ip_address: string | null;
  user_agent: string | null;
  session_id: string | null;
  created_at: string;
}

// RESOURCES

export interface IpAddressResource extends TableIpAddress {}

export interface IpAuditLogResource extends TableIpAuditLog {}

// AUDIT FILTER OPTIONS

export interface IpAuditUserOption {
  id: number;
  email: string | null;
  count: number;
}

export interface IpAuditActionOption {
  value: string;
  count: number;
}

// AUDIT LOG RESPONSE

export interface IpAuditLogsResponse {
  logs: PaginatedResponse<IpAuditLogResource>;
  filter_options: {
    users: IpAuditUserOption[];
    actions: IpAuditActionOption[];
  };
}

export interface IpStatsResource {
  total: number;
  mine: number;
  others: number;
}

export interface IpOwnerOption {
  id: number;
  email: string | null;
  count: number;
}

export interface IpAddressesResponse {
  items: PaginatedResponse<IpAddressResource>;
  filter_options: {
    owners: IpOwnerOption[];
  };
}
