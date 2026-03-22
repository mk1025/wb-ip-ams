import axios from "axios";
import { useAuthStore } from "@/stores/auth-store";

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

function handleAuthError() {
  useAuthStore.getState().clearAuth();

  const currentPath = globalThis.location.pathname;

  if (currentPath !== "/login" && currentPath !== "/register") {
    globalThis.location.href = "/login";
  }
}

const api = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true,
  headers: {
    "Content-Type": "application/json",
  },
});

let refreshPromise: Promise<string> | null = null;

function doRefresh(): Promise<string> {
  if (refreshPromise) return refreshPromise;

  refreshPromise = axios
    .post(`${API_BASE_URL}/auth/refresh`, {}, { withCredentials: true })
    .then(({ data }) => {
      const token: string = data?.data?.access_token;

      if (!token) throw new Error("Refresh failed");

      useAuthStore.getState().updateToken(token);

      return token;
    })
    .finally(() => {
      refreshPromise = null;
    });

  return refreshPromise;
}

api.interceptors.request.use(
  (config) => {
    const token = useAuthStore.getState().accessToken;
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error),
);

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    const isAuthEndpoint =
      originalRequest.url?.includes("/auth/login") ||
      originalRequest.url?.includes("/auth/register") ||
      originalRequest.url?.includes("/auth/refresh");

    if (
      error.response?.status === 401 &&
      !originalRequest._retry &&
      !isAuthEndpoint
    ) {
      originalRequest._retry = true;

      try {
        const token = await doRefresh();

        originalRequest.headers.Authorization = `Bearer ${token}`;

        return api(originalRequest);
      } catch (refreshError) {
        handleAuthError();
        throw refreshError;
      }
    }

    throw error;
  },
);

export default api;
