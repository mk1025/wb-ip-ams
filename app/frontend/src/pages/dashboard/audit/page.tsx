import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import QueryState from "@/components/common/QueryState";
import {
  useGetAuthAuditLogs,
  useGetIpAuditLogs,
  type AuthAuditLogParams,
  type IpAuditLogParams,
} from "@/hooks/queries/audit-log";
import { useDebounce } from "@/hooks/use-debounce";
import { useAuthStore } from "@/stores/auth-store";
import { Navigate } from "react-router-dom";
import AuthLogsTable from "./_components/AuthLogsTable";
import IpLogsTable from "./_components/IpLogsTable";
import { useEffect, useId, useState } from "react";
import { toast } from "sonner";

export default function AuditLogPage() {
  const user = useAuthStore((state) => state.user);

  const authToastId = useId();
  const ipToastId = useId();

  const [authFilter, setAuthFilter] = useState<AuthAuditLogParams>({
    page: 1,
    sortBy: "created_at",
    sortDir: "desc",
  });
  const [ipFilter, setIpFilter] = useState<IpAuditLogParams>({
    page: 1,
    sortBy: "created_at",
    sortDir: "desc",
  });

  const debouncedAuthText = useDebounce({
    ip_address: authFilter.ip_address,
  });
  const authQueryFilter: AuthAuditLogParams = {
    ...authFilter,
    ...debouncedAuthText,
  };

  const debouncedIpText = useDebounce({
    ip_address: ipFilter.ip_address,
  });
  const ipQueryFilter: IpAuditLogParams = {
    ...ipFilter,
    ...debouncedIpText,
  };

  const {
    data: authLogs,
    isLoading: isLoadingAuth,
    isFetching: isFetchingAuth,
    isError: isErrorAuth,
    error: authError,
  } = useGetAuthAuditLogs(authQueryFilter);

  const {
    data: ipLogs,
    isLoading: isLoadingIp,
    isError: isErrorIp,
    error: ipError,
    isFetching: isFetchingIp,
    isSuccess: isSuccessIp,
  } = useGetIpAuditLogs(ipQueryFilter);

  useEffect(() => {
    if (isErrorAuth) {
      toast.error("Failed to load auth logs. Please try again.", {
        id: authToastId,
        description: authError instanceof Error ? authError.message : undefined,
      });
    } else if (isLoadingAuth) {
      toast.loading("Loading auth logs...", {
        id: authToastId,
        description: undefined,
      });
    } else if (isFetchingAuth) {
      toast.loading("Updating results...", {
        id: authToastId,
        description: undefined,
      });
    } else {
      toast.dismiss(authToastId);
    }
  }, [authToastId, isLoadingAuth, isFetchingAuth, isErrorAuth, authError]);

  useEffect(() => {
    if (isErrorIp) {
      toast.error("Failed to load IP logs. Please try again.", {
        id: ipToastId,
        description: ipError instanceof Error ? ipError.message : undefined,
      });
    } else if (isLoadingIp) {
      toast.loading("Loading IP logs...", {
        id: ipToastId,
        description: undefined,
      });
    } else if (isFetchingIp) {
      toast.loading("Updating results...", {
        id: ipToastId,
        description: undefined,
      });
    } else {
      toast.dismiss(ipToastId);
    }
  }, [ipToastId, isLoadingIp, isFetchingIp, isErrorIp, ipError, isSuccessIp]);

  if (user?.role !== "super-admin") {
    return <Navigate to="/dashboard" replace />;
  }

  return (
    <>
      <title>Audit Logs - IP Address Manager</title>
      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Audit Logs</h1>
          <p className="text-muted-foreground">
            Read-only record of all system activity
          </p>
        </div>

        <Tabs defaultValue="auth">
          <TabsList>
            <TabsTrigger value="auth">Auth Events</TabsTrigger>
            <TabsTrigger value="ip">IP Changes</TabsTrigger>
          </TabsList>

          <TabsContent value="auth" className="mt-4">
            <QueryState
              isLoading={isLoadingAuth}
              isError={isErrorAuth}
              error={authError}
            >
              <AuthLogsTable
                response={authLogs}
                filter={authFilter}
                isFetching={isFetchingAuth}
                onFilterChange={(patch) =>
                  setAuthFilter((prev) => ({ ...prev, ...patch }))
                }
              />
            </QueryState>
          </TabsContent>

          <TabsContent value="ip" className="mt-4">
            <QueryState
              isLoading={isLoadingIp}
              isError={isErrorIp}
              error={ipError}
            >
              <IpLogsTable
                response={ipLogs}
                filter={ipFilter}
                isFetching={isFetchingIp}
                onFilterChange={(patch) =>
                  setIpFilter((prev) => ({ ...prev, ...patch }))
                }
              />
            </QueryState>
          </TabsContent>
        </Tabs>
      </div>
    </>
  );
}
