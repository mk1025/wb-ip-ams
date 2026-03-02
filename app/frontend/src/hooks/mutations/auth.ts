import { useMutation, type UseMutationResult } from "@tanstack/react-query";
import type {
  APIResponse,
  AuthResource,
  TokenResource,
} from "@wb-ip-ams/shared-types";
import { AUTH_MUTATION_KEYS } from "../keys/auth";
import type z from "zod";
import type {
  LoginRequest,
  RefreshTokenRequest,
  RegisterRequest,
} from "@/validation/auth";
import api from "@/lib/axios";
import { toast } from "sonner";
import { useAuthStore } from "@/stores/auth-store";

export function useRegisterMutation(): UseMutationResult<
  AuthResource,
  Error,
  z.infer<typeof RegisterRequest>
> {
  const { setAuth } = useAuthStore();

  return useMutation({
    mutationKey: [AUTH_MUTATION_KEYS.REGISTER.join(".")],
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
        id: AUTH_MUTATION_KEYS.REGISTER.join("."),
        description: undefined,
      });
    },
    onSuccess: (data) => {
      localStorage.setItem("access_token", data.tokens.access_token);
      localStorage.setItem("refresh_token", data.tokens.refresh_token);

      setAuth(data.user, data.tokens.access_token);

      toast.success("Registration successful!", {
        id: AUTH_MUTATION_KEYS.REGISTER.join("."),
      });
    },
    onError: (error) => {
      toast.error("Registration failed", {
        id: AUTH_MUTATION_KEYS.REGISTER.join("."),
        description: error.message,
      });
    },
  });
}

export function useLoginMutation(): UseMutationResult<
  AuthResource,
  Error,
  z.infer<typeof LoginRequest>
> {
  const { setAuth } = useAuthStore();

  return useMutation({
    mutationKey: [AUTH_MUTATION_KEYS.LOGIN.join(".")],
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
        id: AUTH_MUTATION_KEYS.LOGIN.join("."),
        description: undefined,
      });
    },
    onSuccess: (data) => {
      localStorage.setItem("access_token", data.tokens.access_token);
      localStorage.setItem("refresh_token", data.tokens.refresh_token);

      setAuth(data.user, data.tokens.access_token);

      toast.success("Login successful!", {
        id: AUTH_MUTATION_KEYS.LOGIN.join("."),
      });
    },
    onError: (error) => {
      toast.error("Login failed", {
        id: AUTH_MUTATION_KEYS.LOGIN.join("."),
        description: error.message,
      });
    },
  });
}

export function useLogoutMutation(): UseMutationResult<
  APIResponse<null>,
  Error,
  void
> {
  const { clearAuth } = useAuthStore();

  return useMutation({
    mutationKey: [AUTH_MUTATION_KEYS.LOGOUT.join(".")],
    mutationFn: async () => {
      const response = await api.post<APIResponse<null>>("/auth/logout");

      if (!response.data.success) {
        throw new Error(response.data.message || "Logout failed");
      }

      return response.data;
    },
    onMutate: () => {
      toast.loading("Logging out...", {
        id: AUTH_MUTATION_KEYS.LOGOUT.join("."),
        description: undefined,
      });
    },
    onSuccess: (data) => {
      localStorage.removeItem("access_token");
      localStorage.removeItem("refresh_token");
      clearAuth();

      const message = data.message || "Logout successful!";

      toast.success(message, {
        id: AUTH_MUTATION_KEYS.LOGOUT.join("."),
        description: undefined,
      });
    },
    onError: (error) => {
      toast.error("Logout failed", {
        id: AUTH_MUTATION_KEYS.LOGOUT.join("."),
        description: error.message,
      });
    },
  });
}

export function useRefreshTokenMutation(): UseMutationResult<
  TokenResource,
  Error,
  z.infer<typeof RefreshTokenRequest>
> {
  const { updateToken, clearAuth } = useAuthStore();

  return useMutation({
    mutationKey: [AUTH_MUTATION_KEYS.REFRESH.join(".")],
    mutationFn: async (payload) => {
      const response = await api.post<APIResponse<TokenResource>>(
        "/auth/refresh",
        payload,
      );

      if (!response.data.success || !response.data.data) {
        throw new Error(response.data.message || "Token refresh failed");
      }

      return response.data.data;
    },
    onSuccess: (data) => {
      localStorage.setItem("access_token", data.access_token);
      updateToken(data.access_token);
    },
    onError: (error) => {
      localStorage.removeItem("access_token");
      localStorage.removeItem("refresh_token");

      clearAuth();

      toast.error("Session expired. Please log in again.", {
        id: AUTH_MUTATION_KEYS.REFRESH.join("."),
        description: error.message,
      });
    },
  });
}
