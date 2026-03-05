import { Spinner } from "@/components/ui/spinner";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { cn } from "@/lib/utils";
import type { AuthAuditLogResource } from "@wb-ip-ams/shared-types";
import CustomTablePagination from "../../../components/common/CustomTablePagination";

function getAuthActionColor(action: string): string {
  switch (action) {
    case "login":
      return "bg-green-100 text-green-700";
    case "logout":
      return "bg-gray-100 text-gray-700";
    case "register":
      return "bg-blue-100 text-blue-700";
    case "token_refresh":
      return "bg-yellow-100 text-yellow-700";
    default:
      return "bg-gray-100 text-gray-700";
  }
}

export default function AuthLogsTable({
  isLoading = false,
  isError = false,
  data = [],
  currentPage = 1,
  lastPage = 1,
  total = 0,
  onPageChange,
}: {
  isLoading?: boolean;
  isError?: boolean;
  data?: AuthAuditLogResource[];
  currentPage?: number;
  lastPage?: number;
  total?: number;
  onPageChange?: (page: number) => void;
}) {
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
        Failed to load auth audit logs.
      </p>
    );
  }

  return (
    <div className="overflow-hidden rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Action</TableHead>
            <TableHead>User ID</TableHead>
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
                No auth events found.
              </TableCell>
            </TableRow>
          ) : (
            data.map((log) => (
              <TableRow key={log.id}>
                <TableCell>
                  <span
                    className={cn(
                      "rounded px-2 py-0.5 text-xs font-medium",
                      getAuthActionColor(log.action),
                    )}
                  >
                    {log.action}
                  </span>
                </TableCell>
                <TableCell className="text-muted-foreground">
                  {log.user_id ?? "—"}
                </TableCell>
                <TableCell className="font-mono text-xs">
                  {log.ip_address ?? "—"}
                </TableCell>
                <TableCell className="max-w-[200px] truncate font-mono text-xs">
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
        onPageChange={(page) => onPageChange?.(page)}
      />
    </div>
  );
}
