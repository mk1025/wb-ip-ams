import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { cn } from "@/lib/utils";
import type { AuthAuditLogsResponse } from "@wb-ip-ams/shared-types";
import CustomTablePagination from "../../../../components/common/CustomTablePagination";
import type { AuthAuditLogParams } from "@/hooks/queries/audit-log";
import AuthLogsTableFilters from "./AuthLogsTableFilters";
import { Badge } from "@/components/ui/badge";
import { getAuthActionColor } from "../util";

export default function AuthLogsTable({
  response,
  filter = {},
  onFilterChange,
  isFetching = false,
}: Readonly<{
  response?: AuthAuditLogsResponse;
  filter?: AuthAuditLogParams;
  onFilterChange?: (patch: Partial<AuthAuditLogParams>) => void;
  isFetching?: boolean;
}>) {
  const data = response?.logs.data ?? [];
  const currentPage = response?.logs.current_page ?? 1;
  const lastPage = response?.logs.last_page ?? 1;
  const total = response?.logs.total ?? 0;
  const hasFilters = filter.user_id || filter.action || filter.session_id;

  return (
    <div className="space-y-3">
      <AuthLogsTableFilters
        response={response}
        filter={filter}
        onFilterChange={onFilterChange}
      />
      <div
        className={cn(
          "overflow-hidden rounded-md border",
          isFetching && "pointer-events-none opacity-60",
        )}
      >
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Action</TableHead>
              <TableHead>User</TableHead>
              <TableHead>IP Address</TableHead>
              <TableHead>Session ID</TableHead>
              <TableHead>Timestamp</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {data.length === 0 ? (
              <TableRow>
                <TableCell
                  colSpan={5}
                  className="text-muted-foreground h-24 text-center"
                >
                  {hasFilters
                    ? "No results match your filters."
                    : "No auth events found."}
                </TableCell>
              </TableRow>
            ) : (
              data.map((log) => (
                <TableRow key={log.id}>
                  <TableCell>
                    <Badge
                      className={cn(
                        "text-xs font-medium",
                        getAuthActionColor(log.action),
                      )}
                    >
                      {log.action}
                    </Badge>
                  </TableCell>
                  <TableCell className="text-muted-foreground inline-flex items-center gap-2.5 text-xs">
                    <span>#{log.user_id}</span>
                    <span className="text-xs">{log.user_email ?? "-"}</span>
                  </TableCell>
                  <TableCell className="font-mono text-xs">
                    {log.ip_address ?? "—"}
                  </TableCell>
                  <TableCell className="max-w-50 truncate font-mono text-xs">
                    {log.session_id ?? "—"}
                  </TableCell>
                  <TableCell className="text-muted-foreground">
                    {new Date(log.created_at).toLocaleString()}
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
        <CustomTablePagination
          currentPage={currentPage}
          lastPage={lastPage}
          total={total}
          onPageChange={(page) => onFilterChange?.({ page })}
        />
      </div>
    </div>
  );
}
