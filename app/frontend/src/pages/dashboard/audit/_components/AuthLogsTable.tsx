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
import { Button } from "@/components/ui/button";
import { getAuthActionColor } from "../util";
import { ArrowDownIcon, ArrowUpDownIcon, ArrowUpIcon } from "lucide-react";

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
  const hasFilters =
    filter.user_id ||
    filter.action ||
    filter.ip_address ||
    filter.session_id ||
    filter.date_from ||
    filter.date_to;

  function sortHeader(
    col: NonNullable<AuthAuditLogParams["sort_by"]>,
    label: string,
  ) {
    const isActive = filter.sort_by === col;
    return (
      <Button
        variant="ghost"
        onClick={() =>
          onFilterChange?.({
            sort_by: col,
            sort_dir: isActive && filter.sort_dir === "asc" ? "desc" : "asc",
            page: 1,
          })
        }
        className="px-0!"
      >
        {label}
        {isActive ? (
          filter.sort_dir === "asc" ? (
            <ArrowUpIcon className="size-3.5" />
          ) : (
            <ArrowDownIcon className="size-3.5" />
          )
        ) : (
          <ArrowUpDownIcon className="text-muted-foreground size-3.5" />
        )}
      </Button>
    );
  }

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
              <TableHead>{sortHeader("action", "Action")}</TableHead>
              <TableHead>{sortHeader("user_id", "User")}</TableHead>
              <TableHead>{sortHeader("ip_address", "IP Address")}</TableHead>
              <TableHead>Session</TableHead>
              <TableHead>{sortHeader("created_at", "Timestamp")}</TableHead>
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
                  <TableCell className="text-muted-foreground font-mono text-xs">
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
