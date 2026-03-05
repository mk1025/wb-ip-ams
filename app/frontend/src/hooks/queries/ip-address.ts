import {
  keepPreviousData,
  useQuery,
  type UseQueryResult,
} from "@tanstack/react-query";
import {
  type APIResponse,
  type IpAddressResource,
  type IpStatsResource,
  type PaginatedResponse,
} from "@wb-ip-ams/shared-types";
import { IP_ADDRESS_QUERY_KEYS } from "../keys/ip-address";
import api from "@/lib/axios";

export function useGetIpAddresses({
  page = 1,
  search = "",
}): UseQueryResult<PaginatedResponse<IpAddressResource>, Error> {
  return useQuery({
    queryKey: [...IP_ADDRESS_QUERY_KEYS.ALL, { page, search }],
    queryFn: async () => {
      const response = await api.get<
        APIResponse<PaginatedResponse<IpAddressResource>>
      >(`/ip-addresses`, {
        params: { page, ...(search ? { search } : {}) },
      });

      if (!response.data.success || !response.data.data) {
        throw new Error(
          response.data.message || "Failed to fetch IP addresses",
        );
      }

      return response.data.data;
    },
    staleTime: 30 * 1000, // 30 seconds
    placeholderData: keepPreviousData,
  });
}

export function useGetIpAddress(
  id: number,
): UseQueryResult<IpAddressResource, Error> {
  return useQuery({
    queryKey: [...IP_ADDRESS_QUERY_KEYS.DETAIL(id)],
    queryFn: async () => {
      const response = await api.get<APIResponse<IpAddressResource>>(
        `/ip-addresses/${id}`,
      );

      if (!response.data.success || !response.data.data) {
        throw new Error(response.data.message || "Failed to fetch IP address");
      }

      return response.data.data;
    },
    enabled: !!id,
    staleTime: 30 * 1000,
  });
}

export function useGetIpStats(): UseQueryResult<IpStatsResource, Error> {
  return useQuery({
    queryKey: [...IP_ADDRESS_QUERY_KEYS.ALL, "stats"],
    queryFn: async () => {
      const response = await api.get<APIResponse<IpStatsResource>>(
        "/ip-addresses/stats",
      );

      if (!response.data.success || !response.data.data) {
        throw new Error(response.data.message || "Failed to fetch IP stats");
      }

      return response.data.data;
    },
    staleTime: 30 * 1000,
  });
}
