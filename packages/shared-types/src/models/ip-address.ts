// IP-ADDRESS

export interface TableIpAddress {
  id: number;
  ip_address: string;
  label: string;
  comment: string | null;
  owner_id: number;
  created_at: string;
  updated_at: string;
}

export interface TableIpAuditLog {
  id: number;
  user_id: number | null;
  action: "create" | "update" | "delete";
  entity_type: string;
  entity_id: number;
  old_value: Record<any, any>; // json
  new_value: Record<any, any>; // json
  ip_address: string | null;
  user_agent: string | null;
  session_id: string | null;
  created_at: string;
}

// RESOURCES

export interface IpAddressResource extends TableIpAddress {}

export interface IpAuditLogResource extends TableIpAuditLog {}

export interface IpStatsResource {
  total: number;
  mine: number;
  others: number;
}
