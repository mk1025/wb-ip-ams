import {
  keepPreviousData,
  useQuery,
  type UseQueryResult,
} from "@tanstack/react-query";
import type {
  APIResponse,
  AuthAuditLogsResponse,
  IpAuditLogsResponse,
} from "@wb-ip-ams/shared-types";
import { AUDIT_LOG_QUERY_KEYS } from "../keys/audit-log";
import api from "@/lib/axios";

export type AuthAuditAction = "login" | "logout" | "token_refresh" | "register";
export type IpAuditAction = "create" | "update" | "delete";

export interface AuthAuditLogParams {
  page?: number;
  user_id?: string;
  action?: AuthAuditAction;
  ip_address?: string;
  session_id?: string;
  date_from?: string;
  date_to?: string;
  sortBy?: "action" | "user_id" | "ip_address" | "created_at";
  sortDir?: "asc" | "desc";
}

export interface IpAuditLogParams {
  page?: number;
  user_id?: string;
  entity_id?: number;
  action?: IpAuditAction;
  ip_address?: string;
  session_id?: string;
  date_from?: string;
  date_to?: string;
  sortBy?: "action" | "user_id" | "entity_id" | "created_at";
  sortDir?: "asc" | "desc";
}

export function useGetAuthAuditLogs(
  params: AuthAuditLogParams = {},
): UseQueryResult<AuthAuditLogsResponse, Error> {
  const queryKey = [...AUDIT_LOG_QUERY_KEYS.AUTH_LIST, params];

  return useQuery({
    queryKey,
    queryFn: async () => {
      const response = await api.get<APIResponse<AuthAuditLogsResponse>>(
        "/audit/auth",
        { params },
      );

      if (!response.data.success || !response.data.data) {
        throw new Error(
          response.data.message || "Failed to fetch auth audit logs",
        );
      }

      return response.data.data;
    },
    staleTime: 30 * 1000,
    placeholderData: keepPreviousData,
  });
}

export function useGetIpAuditLogs(
  params: IpAuditLogParams = {},
): UseQueryResult<IpAuditLogsResponse, Error> {
  const queryKey = [...AUDIT_LOG_QUERY_KEYS.IP_LIST, params];

  return useQuery({
    queryKey,
    queryFn: async () => {
      const response = await api.get<APIResponse<IpAuditLogsResponse>>(
        "/audit/ip",
        { params },
      );

      if (!response.data.success || !response.data.data) {
        throw new Error(
          response.data.message || "Failed to fetch IP audit logs",
        );
      }

      return response.data.data;
    },
    staleTime: 30 * 1000,
    placeholderData: keepPreviousData,
  });
}
