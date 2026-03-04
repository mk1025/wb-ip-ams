import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  useGetAuthAuditLogs,
  useGetIpAuditLogs,
} from "@/hooks/queries/audit-log";
import { useAuthStore } from "@/stores/auth-store";
import { Navigate } from "react-router-dom";
import AuthLogsTable from "./_components/AuthLogsTable";
import IpLogsTable from "./_components/IpLogsTable";

export default function AuditLogPage() {
  const user = useAuthStore((state) => state.user);

  const {
    data: authLogs,
    isLoading: isLoadingAuth,
    isError: isErrorAuth,
  } = useGetAuthAuditLogs();

  const {
    data: ipLogs,
    isLoading: isLoadingIp,
    isError: isErrorIp,
  } = useGetIpAuditLogs();

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
          />
        </TabsContent>

        <TabsContent value="ip" className="mt-4">
          <IpLogsTable
            isLoading={isLoadingIp}
            isError={isErrorIp}
            data={ipLogs?.data ?? []}
          />
        </TabsContent>
      </Tabs>
    </div>
  );
}
