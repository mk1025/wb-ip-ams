import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import QueryState from "@/components/common/QueryState";
import {
  useGetAuthAuditLogs,
  useGetIpAuditLogs,
  type AuthAuditLogParams,
  type IpAuditLogParams,
} from "@/hooks/queries/audit-log";
import { useDebounce } from "@/hooks/use-debounce";
import { useQueryToast } from "@/hooks/use-query-toast";
import { useAuthStore } from "@/stores/auth-store";
import { Navigate } from "react-router-dom";
import AuthLogsTable from "./_components/AuthLogsTable";
import IpLogsTable from "./_components/IpLogsTable";
import { useId, useState } from "react";

export default function AuditLogPage() {
  const user = useAuthStore((state) => state.user);

  const authToastId = useId();
  const ipToastId = useId();

  const [authFilter, setAuthFilter] = useState<AuthAuditLogParams>({
    page: 1,
    sort_by: "created_at",
    sort_dir: "desc",
  });
  const [ipFilter, setIpFilter] = useState<IpAuditLogParams>({
    page: 1,
    sort_by: "created_at",
    sort_dir: "desc",
  });

  const debouncedAuthIpAddress = useDebounce(authFilter.ip_address, 400);
  const debouncedAuthSessionId = useDebounce(authFilter.session_id, 400);
  const authQueryFilter: AuthAuditLogParams = {
    ...authFilter,
    ip_address: debouncedAuthIpAddress,
    session_id: debouncedAuthSessionId,
  };

  const debouncedIpIpAddress = useDebounce(ipFilter.ip_address, 400);
  const debouncedIpSessionId = useDebounce(ipFilter.session_id, 400);
  const ipQueryFilter: IpAuditLogParams = {
    ...ipFilter,
    ip_address: debouncedIpIpAddress,
    session_id: debouncedIpSessionId,
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

  useQueryToast(
    {
      isLoading: isLoadingAuth,
      isFetching: isFetchingAuth,
      isError: isErrorAuth,
      error: authError,
    },
    {
      id: authToastId,
      loadingMessage: "Loading auth logs...",
      errorMessage: "Failed to load auth logs. Please try again.",
    },
  );

  useQueryToast(
    {
      isLoading: isLoadingIp,
      isFetching: isFetchingIp,
      isError: isErrorIp,
      error: ipError,
      isSuccess: isSuccessIp,
    },
    {
      id: ipToastId,
      loadingMessage: "Loading IP logs...",
      errorMessage: "Failed to load IP logs. Please try again.",
    },
  );

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
