import { describe, it, expect, beforeEach } from "vitest";
import type { UserResource } from "@wb-ip-ams/shared-types";
import { useAuthStore } from "@/stores/auth-store";

const mockUser: UserResource = {
  id: 1,
  email: "test@example.com",
  role: "user",
  created_at: "2026-01-01T00:00:00Z",
  updated_at: "2026-01-01T00:00:00Z",
};

describe("useAuthStore", () => {
  beforeEach(() => {
    useAuthStore.setState({
      user: null,
      accessToken: null,
      isAuthenticated: false,
    });
  });

  it("has correct initial state", () => {
    const state = useAuthStore.getState();
    expect(state.user).toBeNull();
    expect(state.accessToken).toBeNull();
    expect(state.isAuthenticated).toBe(false);
  });

  it("setAuth populates user, token and marks isAuthenticated true", () => {
    useAuthStore.getState().setAuth(mockUser, "token-abc");

    const state = useAuthStore.getState();
    expect(state.user).toEqual(mockUser);
    expect(state.accessToken).toBe("token-abc");
    expect(state.isAuthenticated).toBe(true);
  });

  it("clearAuth resets all fields to defaults", () => {
    useAuthStore.getState().setAuth(mockUser, "token-abc");
    useAuthStore.getState().clearAuth();

    const state = useAuthStore.getState();
    expect(state.user).toBeNull();
    expect(state.accessToken).toBeNull();
    expect(state.isAuthenticated).toBe(false);
  });

  it("updateToken only updates accessToken, leaving user and isAuthenticated intact", () => {
    useAuthStore.getState().setAuth(mockUser, "old-token");
    useAuthStore.getState().updateToken("new-token");

    const state = useAuthStore.getState();
    expect(state.accessToken).toBe("new-token");
    expect(state.user).toEqual(mockUser);
    expect(state.isAuthenticated).toBe(true);
  });
});
