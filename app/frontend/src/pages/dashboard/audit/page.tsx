import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  useGetAuthAuditLogs,
  useGetIpAuditLogs,
} from "@/hooks/queries/audit-log";
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

  const [authPage, setAuthPage] = useState(1);
  const [ipPage, setIpPage] = useState(1);

  const {
    data: authLogs,
    isLoading: isLoadingAuth,
    isFetching: isFetchingAuth,
    isError: isErrorAuth,
    error: authError,
    isSuccess: isSuccessAuth,
  } = useGetAuthAuditLogs(authPage);

  const {
    data: ipLogs,
    isLoading: isLoadingIp,
    isError: isErrorIp,
    error: ipError,
    isFetching: isFetchingIp,
    isSuccess: isSuccessIp,
  } = useGetIpAuditLogs(ipPage);

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
  }, [
    authToastId,
    isLoadingAuth,
    isFetchingAuth,
    isErrorAuth,
    authError,
    isSuccessAuth,
  ]);

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
            <AuthLogsTable
              isLoading={isLoadingAuth}
              isError={isErrorAuth}
              data={authLogs?.data ?? []}
              currentPage={authPage}
              lastPage={authLogs?.last_page ?? 1}
              total={authLogs?.total ?? 0}
              onPageChange={(page) => setAuthPage(page)}
            />
          </TabsContent>

          <TabsContent value="ip" className="mt-4">
            <IpLogsTable
              isLoading={isLoadingIp}
              isError={isErrorIp}
              data={ipLogs?.data ?? []}
              currentPage={ipPage}
              lastPage={ipLogs?.last_page ?? 1}
              total={ipLogs?.total ?? 0}
              onPageChange={(page) => setIpPage(page)}
            />
          </TabsContent>
        </Tabs>
      </div>
    </>
  );
}
