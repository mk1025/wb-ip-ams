import { useQuery, type UseQueryResult } from "@tanstack/react-query";
import { type APIResponse, type UserResource } from "@wb-ip-ams/shared-types";
import { AUTH_QUERY_KEYS } from "../keys/auth";
import api from "@/lib/axios";

export function useGetCurrentUser(
  enabled = true,
): UseQueryResult<UserResource, Error> {
  return useQuery({
    queryKey: AUTH_QUERY_KEYS.ME,
    queryFn: async () => {
      const response = await api.get<APIResponse<UserResource>>("/auth/me");

      if (!response.data.success || !response.data.data) {
        throw new Error(response.data.message || "Failed to fetch user");
      }

      return response.data.data;
    },
    enabled,
    staleTime: 5 * 60 * 1000, // 5 minutes
    gcTime: 10 * 60 * 1000, // 10 minutes
    retry: false,
    refetchOnWindowFocus: true,
    refetchOnMount: true,
  });
}
