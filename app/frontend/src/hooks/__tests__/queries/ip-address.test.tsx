import { renderHook, waitFor } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import type { ReactNode } from "react";
import { describe, it, expect, vi, beforeEach, type Mock } from "vitest";
import api from "@/lib/axios";
import {
  useGetIpAddresses,
  useGetIpAddress,
  useGetIpStats,
} from "@/hooks/queries/ip-address";

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

describe("useGetIpAddresses", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockGet.mockResolvedValue({
      data: {
        success: true,
        data: {
          items: { data: [], current_page: 1, total: 0 },
          filter_options: { owners: [] },
        },
      },
    });
  });

  it("sends correct query params", async () => {
    const { result } = renderHook(
      () =>
        useGetIpAddresses({
          page: 2,
          search: "test",
          ownership: "mine",
          sortBy: "label",
          sortDir: "asc",
          date_from: "2025-01-01",
          date_to: "2025-12-31",
        }),
      { wrapper: makeWrapper() },
    );

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(mockGet).toHaveBeenCalledWith(
      "/ip-addresses",
      expect.objectContaining({
        params: expect.objectContaining({
          page: 2,
          search: "test",
          ownership: "mine",
          sort_by: "label",
          sort_dir: "asc",
          date_from: "2025-01-01",
          date_to: "2025-12-31",
        }),
      }),
    );
  });

  it("omits undefined params from request", async () => {
    const { result } = renderHook(() => useGetIpAddresses({}), {
      wrapper: makeWrapper(),
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    const [, config] = mockGet.mock.calls[0] as [
      string,
      { params: Record<string, unknown> },
    ];
    expect(config.params).not.toHaveProperty("search");
    expect(config.params).not.toHaveProperty("owner_id");
    expect(config.params).not.toHaveProperty("ownership");
    expect(config.params).not.toHaveProperty("date_from");
    expect(config.params).not.toHaveProperty("date_to");
  });
});

describe("useGetIpAddress", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockGet.mockResolvedValue({
      data: {
        success: true,
        data: {
          id: 5,
          ip_address: "10.0.0.5",
          label: "Test",
          comment: null,
          owner_id: 1,
          owner_email: "t@t.com",
          created_at: "",
          updated_at: "",
        },
      },
    });
  });

  it("fetches the correct URL for a given id", async () => {
    const { result } = renderHook(() => useGetIpAddress(5), {
      wrapper: makeWrapper(),
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(mockGet).toHaveBeenCalledWith("/ip-addresses/5");
  });

  it("does not fetch when id is 0 (falsy)", async () => {
    renderHook(() => useGetIpAddress(0), { wrapper: makeWrapper() });

    await new Promise((r) => setTimeout(r, 50));

    expect(mockGet).not.toHaveBeenCalled();
  });
});

describe("useGetIpStats", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockGet.mockResolvedValue({
      data: {
        success: true,
        data: { total: 10, mine: 4, others: 6 },
      },
    });
  });

  it("fetches /ip-addresses/stats and returns stats data", async () => {
    const { result } = renderHook(() => useGetIpStats(), {
      wrapper: makeWrapper(),
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(mockGet).toHaveBeenCalledWith("/ip-addresses/stats");
    expect(result.current.data).toEqual({ total: 10, mine: 4, others: 6 });
  });
});
