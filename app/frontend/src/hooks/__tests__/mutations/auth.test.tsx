import { renderHook, waitFor } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import type { ReactNode } from "react";
import { describe, it, expect, vi, beforeEach, type Mock } from "vitest";
import api from "@/lib/axios";
import { useAuthStore } from "@/stores/auth-store";
import {
  useLoginMutation,
  useLogoutMutation,
  useRegisterMutation,
} from "@/hooks/mutations/auth";

vi.mock("@/lib/axios", () => ({ default: { post: vi.fn() } }));
vi.mock("sonner", () => ({
  toast: { loading: vi.fn(), success: vi.fn(), error: vi.fn() },
}));

const mockPost = api.post as Mock;

const mockAuthResponse = {
  data: {
    success: true,
    data: {
      user: {
        id: 1,
        email: "test@example.com",
        role: "user",
        created_at: "",
        updated_at: "",
      },
      tokens: {
        access_token: "access-123",
        refresh_token: "refresh-456",
        token_type: "bearer",
        expires_in: 3600,
      },
    },
  },
};

function makeWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: { mutations: { retry: false } },
  });
  return ({ children }: { children: ReactNode }) => (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  );
}

describe("useRegisterMutation", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.clear();
    useAuthStore.setState({
      user: null,
      accessToken: null,
      isAuthenticated: false,
    });
    mockPost.mockResolvedValue(mockAuthResponse);
  });

  it("on success: stores tokens in localStorage and updates auth store", async () => {
    const { result } = renderHook(() => useRegisterMutation(), {
      wrapper: makeWrapper(),
    });

    result.current.mutate({
      email: "test@example.com",
      password: "password123",
      password_confirmation: "password123",
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(localStorage.getItem("access_token")).toBe("access-123");
    expect(localStorage.getItem("refresh_token")).toBe("refresh-456");
    expect(useAuthStore.getState().isAuthenticated).toBe(true);
    expect(useAuthStore.getState().accessToken).toBe("access-123");
  });

  it("on error: does not store tokens or authenticate", async () => {
    mockPost.mockRejectedValue(new Error("Email already taken"));

    const { result } = renderHook(() => useRegisterMutation(), {
      wrapper: makeWrapper(),
    });

    result.current.mutate({
      email: "taken@example.com",
      password: "password123",
      password_confirmation: "password123",
    });

    await waitFor(() => expect(result.current.isError).toBe(true));

    expect(localStorage.getItem("access_token")).toBeNull();
    expect(useAuthStore.getState().isAuthenticated).toBe(false);
  });
});

describe("useLoginMutation", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.clear();
    useAuthStore.setState({
      user: null,
      accessToken: null,
      isAuthenticated: false,
    });
    mockPost.mockResolvedValue(mockAuthResponse);
  });

  it("on success: stores tokens in localStorage and marks authenticated", async () => {
    const { result } = renderHook(() => useLoginMutation(), {
      wrapper: makeWrapper(),
    });

    result.current.mutate({
      email: "test@example.com",
      password: "password123",
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(localStorage.getItem("access_token")).toBe("access-123");
    expect(localStorage.getItem("refresh_token")).toBe("refresh-456");
    expect(useAuthStore.getState().isAuthenticated).toBe(true);
  });

  it("on error: does not store tokens or authenticate", async () => {
    mockPost.mockRejectedValue(new Error("Invalid credentials"));

    const { result } = renderHook(() => useLoginMutation(), {
      wrapper: makeWrapper(),
    });

    result.current.mutate({ email: "test@example.com", password: "wrong" });

    await waitFor(() => expect(result.current.isError).toBe(true));

    expect(localStorage.getItem("access_token")).toBeNull();
    expect(useAuthStore.getState().isAuthenticated).toBe(false);
  });
});

describe("useLogoutMutation", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.setItem("access_token", "existing-token");
    localStorage.setItem("refresh_token", "existing-refresh");
    useAuthStore.setState({
      user: {
        id: 1,
        email: "test@example.com",
        role: "user",
        created_at: "",
        updated_at: "",
      },
      accessToken: "existing-token",
      isAuthenticated: true,
    });
    mockPost.mockResolvedValue({
      data: { success: true, message: "Logged out" },
    });
  });

  it("on success: removes tokens from localStorage and clears auth store", async () => {
    const { result } = renderHook(() => useLogoutMutation(), {
      wrapper: makeWrapper(),
    });

    result.current.mutate();

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(localStorage.getItem("access_token")).toBeNull();
    expect(localStorage.getItem("refresh_token")).toBeNull();
    expect(useAuthStore.getState().isAuthenticated).toBe(false);
    expect(useAuthStore.getState().user).toBeNull();
  });
});
