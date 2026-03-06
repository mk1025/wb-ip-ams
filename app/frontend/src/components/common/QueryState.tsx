import { Spinner } from "@/components/ui/spinner";
import type { ReactNode } from "react";

export default function QueryState({
  isLoading,
  isError,
  error,
  children,
}: Readonly<{
  isLoading: boolean;
  isError: boolean;
  error?: Error | null;
  children: ReactNode;
}>) {
  if (isLoading) {
    return (
      <div className="flex justify-center py-12">
        <Spinner />
      </div>
    );
  }

  if (isError) {
    return (
      <p className="text-destructive py-12 text-center">
        {error?.message ?? "Something went wrong."}
      </p>
    );
  }

  return <>{children}</>;
}
