import type { APIResponse } from "@wb-ip-ams/shared-types";
import { AxiosError } from "axios";

export function getApiErrorMessage(error: unknown): string {
  if (error instanceof AxiosError) {
    const apiResponse = error.response?.data as APIResponse | undefined;

    if (apiResponse?.errors) {
      const messages = Object.values(apiResponse.errors).flat();
      if (messages.length > 0) return messages[0];
    }

    return apiResponse?.message || apiResponse?.error || "An error occurred";
  }

  if (error instanceof Error) {
    return error.message;
  }

  return "An unknown error occurred";
}
