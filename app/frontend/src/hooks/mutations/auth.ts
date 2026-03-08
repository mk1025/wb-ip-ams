import { useMutation, type UseMutationResult } from "@tanstack/react-query";
import type { APIResponse, AuthResource } from "@wb-ip-ams/shared-types";
import { AUTH_MUTATION_KEYS } from "../keys/auth";
import type z from "zod";
import type { LoginRequest, RegisterRequest } from "@/validation/auth";
import api from "@/lib/axios";
import { useAuthStore } from "@/stores/auth-store";
import { toast } from "sonner";
import { getApiErrorMessage } from "@/lib/api-error";

export function useRegisterMutation(): UseMutationResult<
  AuthResource,
  unknown,
  z.infer<typeof RegisterRequest>
> {
  const { setAuth } = useAuthStore();

  const mutationKey = [AUTH_MUTATION_KEYS.REGISTER.join(".")];
  const toastId = mutationKey.join(".");

  return useMutation({
    mutationKey,
    mutationFn: async (payload) => {
      const response = await api.post<APIResponse<AuthResource>>(
        "/auth/register",
        payload,
      );

      if (!response.data.success || !response.data.data) {
        throw new Error(response.data.message || "Registration failed");
      }

      return response.data.data;
    },
    onMutate: () => {
      toast.loading("Registering...", {
        id: toastId,
        description: undefined,
      });
    },
    onSuccess: (data) => {
      localStorage.setItem("refresh_token", data.tokens.refresh_token);

      setAuth(data.user, data.tokens.access_token);

      toast.success("Registration successful!", {
        id: toastId,
        description: undefined,
      });
    },
    onError: (error) => {
      toast.error("Registration failed", {
        id: toastId,
        description: getApiErrorMessage(error),
      });
    },
  });
}

export function useLoginMutation(): UseMutationResult<
  AuthResource,
  unknown,
  z.infer<typeof LoginRequest>
> {
  const { setAuth } = useAuthStore();

  const mutationKey = [AUTH_MUTATION_KEYS.LOGIN.join(".")];
  const toastId = mutationKey.join(".");

  return useMutation({
    mutationKey,
    mutationFn: async (payload) => {
      const response = await api.post<APIResponse<AuthResource>>(
        "/auth/login",
        payload,
      );

      if (!response.data.success || !response.data.data) {
        throw new Error(response.data.message || "Login failed");
      }

      return response.data.data;
    },
    onMutate: () => {
      toast.loading("Logging in...", {
        id: toastId,
        description: undefined,
      });
    },
    onSuccess: (data) => {
      localStorage.setItem("refresh_token", data.tokens.refresh_token);

      setAuth(data.user, data.tokens.access_token);

      toast.success("Login successful!", {
        id: toastId,
        description: undefined,
      });
    },
    onError: (error) => {
      toast.error("Login failed", {
        id: toastId,
        description: getApiErrorMessage(error),
      });
    },
  });
}

export function useLogoutMutation(): UseMutationResult<
  APIResponse<null>,
  unknown,
  void
> {
  const { clearAuth } = useAuthStore();

  const mutationKey = [AUTH_MUTATION_KEYS.LOGOUT.join(".")];
  const toastId = mutationKey.join(".");

  return useMutation({
    mutationKey,
    mutationFn: async () => {
      const response = await api.post<APIResponse<null>>("/auth/logout");

      if (!response.data.success) {
        throw new Error(response.data.message || "Logout failed");
      }

      return response.data;
    },
    onMutate: () => {
      toast.loading("Logging out...", {
        id: toastId,
        description: undefined,
      });
    },
    onSuccess: (data) => {
      localStorage.removeItem("refresh_token");
      clearAuth();

      const message = data.message || "Logout successful!";

      toast.success(message, {
        id: toastId,
        description: undefined,
      });
    },
    onError: (error) => {
      toast.error("Logout failed", {
        id: toastId,
        description: getApiErrorMessage(error),
      });
    },
  });
}
