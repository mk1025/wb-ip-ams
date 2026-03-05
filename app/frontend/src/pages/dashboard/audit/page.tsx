import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  useGetAuthAuditLogs,
  useGetIpAuditLogs,
} from "@/hooks/queries/audit-log";
import { useAuthStore } from "@/stores/auth-store";
import { Navigate } from "react-router-dom";
import AuthLogsTable from "./_components/AuthLogsTable";
import IpLogsTable from "./_components/IpLogsTable";
import { useState } from "react";

export default function AuditLogPage() {
  const user = useAuthStore((state) => state.user);

  const [authPage, setAuthPage] = useState(1);
  const [ipPage, setIpPage] = useState(1);

  const {
    data: authLogs,
    isLoading: isLoadingAuth,
    isError: isErrorAuth,
  } = useGetAuthAuditLogs(authPage);

  const {
    data: ipLogs,
    isLoading: isLoadingIp,
    isError: isErrorIp,
  } = useGetIpAuditLogs(ipPage);

  if (user?.role !== "super-admin") {
    return <Navigate to="/dashboard" replace />;
  }

  return (
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
  );
}
