import { renderHook, act } from "@testing-library/react";
import { describe, it, expect, beforeEach, afterEach, vi } from "vitest";
import { useDebounce } from "@/hooks/use-debounce";

describe("useDebounce", () => {
  beforeEach(() => vi.useFakeTimers());
  afterEach(() => vi.useRealTimers());

  it("returns initial value immediately", () => {
    const { result } = renderHook(() => useDebounce("hello", 300));
    expect(result.current).toBe("hello");
  });

  it("returns debounced value only after delay elapses", () => {
    const { result, rerender } = renderHook(
      ({ value }: { value: string }) => useDebounce(value, 300),
      { initialProps: { value: "initial" } },
    );

    rerender({ value: "updated" });
    expect(result.current).toBe("initial");

    act(() => vi.advanceTimersByTime(300));
    expect(result.current).toBe("updated");
  });

  it("resets timer when value changes before delay", () => {
    const { result, rerender } = renderHook(
      ({ value }: { value: string }) => useDebounce(value, 300),
      { initialProps: { value: "first" } },
    );

    rerender({ value: "second" });
    act(() => vi.advanceTimersByTime(200));
    expect(result.current).toBe("first");

    rerender({ value: "third" });
    act(() => vi.advanceTimersByTime(200));
    expect(result.current).toBe("first");

    act(() => vi.advanceTimersByTime(100));
    expect(result.current).toBe("third");
  });
});
