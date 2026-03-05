import { useQuery, type UseQueryResult } from "@tanstack/react-query";
import type {
  APIResponse,
  AuthAuditLogResource,
  IpAuditLogResource,
  PaginatedResponse,
} from "@wb-ip-ams/shared-types";
import { AUDIT_LOG_QUERY_KEYS } from "../keys/audit-log";
import api from "@/lib/axios";

export function useGetAuthAuditLogs(
  page = 1,
): UseQueryResult<PaginatedResponse<AuthAuditLogResource>, Error> {
  const queryKey = [...AUDIT_LOG_QUERY_KEYS.AUTH_LIST, page];

  return useQuery({
    queryKey,
    queryFn: async () => {
      const response = await api.get<
        APIResponse<PaginatedResponse<AuthAuditLogResource>>
      >("/audit/auth", { params: { page } });

      if (!response.data.success || !response.data.data) {
        throw new Error(
          response.data.message || "Failed to fetch auth audit logs",
        );
      }

      return response.data.data;
    },
    staleTime: 30 * 1000,
  });
}

export function useGetIpAuditLogs(
  page = 1,
): UseQueryResult<PaginatedResponse<IpAuditLogResource>, Error> {
  const queryKey = [...AUDIT_LOG_QUERY_KEYS.IP_LIST, page];

  return useQuery({
    queryKey,
    queryFn: async () => {
      const response = await api.get<
        APIResponse<PaginatedResponse<IpAuditLogResource>>
      >("/audit/ip", { params: { page } });

      if (!response.data.success || !response.data.data) {
        throw new Error(
          response.data.message || "Failed to fetch IP audit logs",
        );
      }

      return response.data.data;
    },
    staleTime: 30 * 1000,
  });
}
