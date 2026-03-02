import type { APIResponse } from "@wb-ip-ams/shared-types";
import { AxiosError } from "axios";

export function getApiErrorMessage(error: unknown): string {
  if (error instanceof AxiosError) {
    const apiResponse = error.response?.data as APIResponse | undefined;
    return apiResponse?.message || apiResponse?.error || "An error occurred";
  }

  if (error instanceof Error) {
    return error.message;
  }

  return "An unknown error occurred";
}
