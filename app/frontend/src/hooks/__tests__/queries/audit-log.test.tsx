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
  logs: { data: [], current_page: 1, total: 0 },
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
});
