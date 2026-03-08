import { renderHook, waitFor } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import type { ReactNode } from "react";
import { describe, it, expect, vi, beforeEach, type Mock } from "vitest";
import api from "@/lib/axios";
import {
  useCreateIpAddressMutation,
  useDeleteIpAddressMutation,
  useUpdateIpAddressMutation,
} from "@/hooks/mutations/ip-address";

vi.mock("@/lib/axios", () => ({
  default: { post: vi.fn(), put: vi.fn(), delete: vi.fn() },
}));
vi.mock("sonner", () => ({
  toast: { loading: vi.fn(), success: vi.fn(), error: vi.fn() },
}));

const mockPost = api.post as Mock;
const mockPut = api.put as Mock;
const mockDelete = api.delete as Mock;

const mockIpResource = {
  id: 1,
  ip_address: "10.0.0.1",
  label: "Test",
  comment: null,
  owner_id: 1,
  owner_email: "t@t.com",
  created_at: "",
  updated_at: "",
};

function makeWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: { mutations: { retry: false } },
  });
  return {
    queryClient,
    Wrapper: ({ children }: { children: ReactNode }) => (
      <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
    ),
  };
}

describe("useCreateIpAddressMutation", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockPost.mockResolvedValue({
      data: { success: true, data: mockIpResource },
    });
  });

  it("on success: invalidates ip-addresses queries", async () => {
    const { queryClient, Wrapper } = makeWrapper();
    const invalidateSpy = vi.spyOn(queryClient, "invalidateQueries");

    const { result } = renderHook(() => useCreateIpAddressMutation(), {
      wrapper: Wrapper,
    });

    result.current.mutate({ ip_address: "10.0.0.1", label: "Test" });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(invalidateSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        queryKey: expect.arrayContaining(["ip-addresses"]),
      }),
    );
  });

  it("calls POST /ip-addresses with the correct payload", async () => {
    const { Wrapper } = makeWrapper();
    const { result } = renderHook(() => useCreateIpAddressMutation(), {
      wrapper: Wrapper,
    });

    result.current.mutate({ ip_address: "10.0.0.1", label: "My IP" });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(mockPost).toHaveBeenCalledWith("/ip-addresses", {
      ip_address: "10.0.0.1",
      label: "My IP",
    });
  });
});

describe("useUpdateIpAddressMutation", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockPut.mockResolvedValue({
      data: { success: true, data: mockIpResource },
    });
  });

  it("on success: invalidates ip-addresses queries", async () => {
    const { queryClient, Wrapper } = makeWrapper();
    const invalidateSpy = vi.spyOn(queryClient, "invalidateQueries");

    const { result } = renderHook(() => useUpdateIpAddressMutation(1), {
      wrapper: Wrapper,
    });

    result.current.mutate({ label: "Updated" });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(invalidateSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        queryKey: expect.arrayContaining(["ip-addresses"]),
      }),
    );
  });

  it("calls PUT with the correct resource URL", async () => {
    const { Wrapper } = makeWrapper();
    const { result } = renderHook(() => useUpdateIpAddressMutation(42), {
      wrapper: Wrapper,
    });

    result.current.mutate({ label: "Updated" });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(mockPut).toHaveBeenCalledWith("/ip-addresses/42", {
      label: "Updated",
    });
  });
});

describe("useDeleteIpAddressMutation", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockDelete.mockResolvedValue({ data: { success: true } });
  });

  it("on success: invalidates ip-addresses queries", async () => {
    const { queryClient, Wrapper } = makeWrapper();
    const invalidateSpy = vi.spyOn(queryClient, "invalidateQueries");

    const { result } = renderHook(() => useDeleteIpAddressMutation(), {
      wrapper: Wrapper,
    });

    result.current.mutate(7);

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(invalidateSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        queryKey: expect.arrayContaining(["ip-addresses"]),
      }),
    );
  });

  it("calls DELETE with the correct resource URL", async () => {
    const { Wrapper } = makeWrapper();
    const { result } = renderHook(() => useDeleteIpAddressMutation(), {
      wrapper: Wrapper,
    });

    result.current.mutate(7);

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(mockDelete).toHaveBeenCalledWith("/ip-addresses/7");
  });
});
