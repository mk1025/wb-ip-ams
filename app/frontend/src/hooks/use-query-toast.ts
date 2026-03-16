import { useEffect } from "react";
import { toast } from "sonner";

interface QueryToastState {
  isLoading: boolean;
  isFetching: boolean;
  isError: boolean;
  error: unknown;
  isSuccess?: boolean;
}

interface QueryToastOptions {
  id: string;
  loadingMessage: string;
  errorMessage: string;
}

export function useQueryToast(
  { isLoading, isFetching, isError, error, isSuccess }: QueryToastState,
  { id, loadingMessage, errorMessage }: QueryToastOptions,
) {
  useEffect(() => {
    if (isError) {
      toast.error(errorMessage, {
        id,
        description: error instanceof Error ? error.message : undefined,
      });
    } else if (isLoading) {
      toast.loading(loadingMessage, { id, description: undefined });
    } else if (isFetching) {
      toast.loading("Updating results...", { id, description: undefined });
    } else {
      toast.dismiss(id);
    }
  }, [
    id,
    isLoading,
    isFetching,
    isError,
    error,
    isSuccess,
    loadingMessage,
    errorMessage,
  ]);
}
