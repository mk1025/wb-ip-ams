import { useQuery, type UseQueryResult } from "@tanstack/react-query";
import {
  type APIResponse,
  type IpAddressResource,
} from "@wb-ip-ams/shared-types";
import { IP_ADDRESS_QUERY_KEYS } from "../keys/ip-address";
import api from "@/lib/axios";

export function useGetIpAddresses(): UseQueryResult<
  IpAddressResource[],
  Error
> {
  return useQuery({
    queryKey: [...IP_ADDRESS_QUERY_KEYS.ALL],
    queryFn: async () => {
      const response =
        await api.get<APIResponse<IpAddressResource[]>>("/ip-addresses");

      if (!response.data.success || !response.data.data) {
        throw new Error(
          response.data.message || "Failed to fetch IP addresses",
        );
      }

      return response.data.data;
    },
    staleTime: 30 * 1000, // 30 seconds
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
