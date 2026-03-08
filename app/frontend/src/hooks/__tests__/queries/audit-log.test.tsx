import { renderHook, waitFor } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import type { ReactNode } from "react";
import { describe, it, expect, vi, beforeEach, type Mock } from "vitest";
import api from "@/lib/axios";
import {
  useGetAuthAuditLogs,
  useGetIpAuditLogs,
  type AuthAuditLogParams,
  type IpAuditLogParams,
} from "@/hooks/queries/audit-log";

vi.mock("@/lib/axios", () => ({
  default: { get: vi.fn() },
}));

function makeWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  });
  return ({ children }: { children: ReactNode }) => (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  );
}

const mockGet = api.get as Mock;

const auditMockData = {
  logs: {
    data: [
      {
        id: 1,
        user_id: 1,
        user_email: "admin@example.com",
        action: "login",
        ip_address: "127.0.0.1",
        user_agent: null,
        session_id: "550e8400-e29b-41d4-a716-446655440000",
        created_at: "2026-03-09T00:00:00.000Z",
      },
    ],
    current_page: 1,
    total: 1,
  },
  filter_options: { users: [], actions: [] },
};

describe("useGetAuthAuditLogs", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockGet.mockResolvedValue({
      data: { success: true, data: auditMockData },
    });
  });

  it("sends correct query params", async () => {
    const params: AuthAuditLogParams = {
      page: 1,
      action: "login",
      user_id: "42",
      date_from: "2025-01-01",
      date_to: "2025-12-31",
    };

    const { result } = renderHook(() => useGetAuthAuditLogs(params), {
      wrapper: makeWrapper(),
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(mockGet).toHaveBeenCalledWith("/audit/auth", { params });
  });

  it("forwards session_id filter param", async () => {
    const params: AuthAuditLogParams = {
      session_id: "550e8400-e29b-41d4-a716-446655440000",
    };

    const { result } = renderHook(() => useGetAuthAuditLogs(params), {
      wrapper: makeWrapper(),
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(mockGet).toHaveBeenCalledWith("/audit/auth", { params });
  });

  it("response data includes session_id on log entries", async () => {
    const { result } = renderHook(() => useGetAuthAuditLogs(), {
      wrapper: makeWrapper(),
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(result.current.data?.logs.data[0].session_id).toBe(
      "550e8400-e29b-41d4-a716-446655440000",
    );
  });
});

describe("useGetIpAuditLogs", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockGet.mockResolvedValue({
      data: { success: true, data: auditMockData },
    });
  });

  it("sends correct query params", async () => {
    const params: IpAuditLogParams = {
      page: 1,
      action: "create",
      user_id: "7",
      entity_id: 99,
    };

    const { result } = renderHook(() => useGetIpAuditLogs(params), {
      wrapper: makeWrapper(),
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(mockGet).toHaveBeenCalledWith("/audit/ip", { params });
  });

  it("forwards session_id filter param", async () => {
    const params: IpAuditLogParams = {
      session_id: "550e8400-e29b-41d4-a716-446655440000",
    };

    const { result } = renderHook(() => useGetIpAuditLogs(params), {
      wrapper: makeWrapper(),
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(mockGet).toHaveBeenCalledWith("/audit/ip", { params });
  });

  it("response data includes session_id on log entries", async () => {
    const { result } = renderHook(() => useGetIpAuditLogs(), {
      wrapper: makeWrapper(),
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(result.current.data?.logs.data[0].session_id).toBe(
      "550e8400-e29b-41d4-a716-446655440000",
    );
  });
});
