import { create } from "zustand";
import { persist } from "zustand/middleware";
import type { UserResource } from "@wb-ip-ams/shared-types";

interface AuthState {
  user: UserResource | null;
  accessToken: string | null;
  isAuthenticated: boolean;
  setAuth: (user: UserResource, accessToken: string) => void;
  clearAuth: () => void;
  updateToken: (accessToken: string) => void;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      user: null,
      accessToken: null,
      isAuthenticated: false,

      setAuth: (user, accessToken) => {
        set({ user, accessToken, isAuthenticated: true });
      },

      clearAuth: () =>
        set({ user: null, accessToken: null, isAuthenticated: false }),

      updateToken: (accessToken) => set({ accessToken }),
    }),
    {
      name: "auth-storage",
      partialize: (state) => ({
        user: state.user,
        accessToken: state.accessToken,
        isAuthenticated: state.isAuthenticated,
      }),
    },
  ),
);
