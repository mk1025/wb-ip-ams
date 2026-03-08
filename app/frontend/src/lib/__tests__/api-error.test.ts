import { describe, it, expect } from "vitest";
import { AxiosError, type AxiosResponse } from "axios";
import { getApiErrorMessage } from "@/lib/api-error";

function makeAxiosError(responseData?: object): AxiosError {
  const err = new AxiosError("Request failed");
  if (responseData) {
    err.response = { data: responseData } as AxiosResponse;
  }
  return err;
}

describe("getApiErrorMessage", () => {
  it("returns the first validation error from errors object", () => {
    const err = makeAxiosError({
      errors: { email: ["Email is already taken"] },
    });
    expect(getApiErrorMessage(err)).toBe("Email is already taken");
  });

  it("returns message when no errors object is present", () => {
    const err = makeAxiosError({ message: "Unauthorized" });
    expect(getApiErrorMessage(err)).toBe("Unauthorized");
  });

  it("returns error field when no message is present", () => {
    const err = makeAxiosError({ error: "Invalid token" });
    expect(getApiErrorMessage(err)).toBe("Invalid token");
  });

  it("returns generic fallback for AxiosError with empty response data", () => {
    const err = makeAxiosError({});
    expect(getApiErrorMessage(err)).toBe("An error occurred");
  });

  it("returns generic fallback for AxiosError with no response", () => {
    const err = new AxiosError("Network Error");
    expect(getApiErrorMessage(err)).toBe("An error occurred");
  });

  it("returns the message from a plain Error", () => {
    expect(getApiErrorMessage(new Error("Something broke"))).toBe(
      "Something broke",
    );
  });

  it("returns unknown fallback for non-Error values", () => {
    expect(getApiErrorMessage("oops")).toBe("An unknown error occurred");
    expect(getApiErrorMessage(null)).toBe("An unknown error occurred");
    expect(getApiErrorMessage(42)).toBe("An unknown error occurred");
  });
});
