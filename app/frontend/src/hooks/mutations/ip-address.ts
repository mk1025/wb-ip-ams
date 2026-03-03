import {
  useMutation,
  useQueryClient,
  type UseMutationResult,
} from "@tanstack/react-query";
import type { APIResponse, IpAddressResource } from "@wb-ip-ams/shared-types";
import type z from "zod";
import type {
  StoreIpAddressRequest,
  UpdateIpAddressRequest,
} from "@/validation/ip-address";
import api from "@/lib/axios";
import { toast } from "sonner";
import { getApiErrorMessage } from "@/lib/api-error";
import {
  IP_ADDRESS_MUTATION_KEYS,
  IP_ADDRESS_QUERY_KEYS,
} from "../keys/ip-address";

export function useCreateIpAddressMutation(): UseMutationResult<
  IpAddressResource,
  unknown,
  z.infer<typeof StoreIpAddressRequest>
> {
  const queryClient = useQueryClient();

  const mutationKey = [...IP_ADDRESS_MUTATION_KEYS.CREATE];
  const toastId = mutationKey.join(".");

  return useMutation({
    mutationKey,
    mutationFn: async (payload) => {
      const response = await api.post<APIResponse<IpAddressResource>>(
        "/ip-addresses",
        payload,
      );

      if (!response.data.success || !response.data.data) {
        throw new Error(response.data.message || "Failed to create IP address");
      }

      return response.data.data;
    },
    onMutate: () => {
      toast.loading("Creating IP address...", {
        id: toastId,
        description: undefined,
      });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({
        queryKey: [...IP_ADDRESS_QUERY_KEYS.ALL],
      });
      toast.success("IP address created!", {
        id: toastId,
        description: undefined,
      });
    },
    onError: (error) => {
      toast.error("Failed to create IP address", {
        id: toastId,
        description: getApiErrorMessage(error),
      });
    },
  });
}

export function useUpdateIpAddressMutation(
  id: number,
): UseMutationResult<
  IpAddressResource,
  unknown,
  z.infer<typeof UpdateIpAddressRequest>
> {
  const queryClient = useQueryClient();

  const mutationKey = [...IP_ADDRESS_MUTATION_KEYS.UPDATE];
  const toastId = mutationKey.join(".");

  return useMutation({
    mutationKey,
    mutationFn: async (payload) => {
      const response = await api.put<APIResponse<IpAddressResource>>(
        `/ip-addresses/${id}`,
        payload,
      );

      if (!response.data.success || !response.data.data) {
        throw new Error(response.data.message || "Failed to update IP address");
      }

      return response.data.data;
    },
    onMutate: () => {
      toast.loading("Updating IP address...", {
        id: toastId,
        description: undefined,
      });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({
        queryKey: [...IP_ADDRESS_QUERY_KEYS.ALL],
      });
      toast.success("IP address updated!", {
        id: toastId,
        description: undefined,
      });
    },
    onError: (error) => {
      toast.error("Failed to update IP address", {
        id: toastId,
        description: getApiErrorMessage(error),
      });
    },
  });
}

export function useDeleteIpAddressMutation(): UseMutationResult<
  void,
  unknown,
  number
> {
  const queryClient = useQueryClient();

  const mutationKey = [...IP_ADDRESS_MUTATION_KEYS.DELETE];
  const toastId = mutationKey.join(".");

  return useMutation({
    mutationKey,
    mutationFn: async (id) => {
      const response = await api.delete<APIResponse<null>>(
        `/ip-addresses/${id}`,
      );

      if (!response.data.success) {
        throw new Error(response.data.message || "Failed to delete IP address");
      }
    },
    onMutate: () => {
      toast.loading("Deleting IP address...", {
        id: toastId,
        description: undefined,
      });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({
        queryKey: [...IP_ADDRESS_QUERY_KEYS.ALL],
      });
      toast.success("IP address deleted!", {
        id: toastId,
        description: undefined,
      });
    },
    onError: (error) => {
      toast.error("Failed to delete IP address", {
        id: toastId,
        description: getApiErrorMessage(error),
      });
    },
  });
}
