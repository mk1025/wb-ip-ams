import { Badge } from "@/components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import type { IpAuditLogParams } from "@/hooks/queries/audit-log";
import { cn } from "@/lib/utils";
import type { IpAuditLogsResponse } from "@wb-ip-ams/shared-types";
import CustomTablePagination from "../../../../components/common/CustomTablePagination";
import IpLogsTableFilters from "./IpLogsTableFilters";
import { getIpActionColor } from "../util";
import { Button } from "@/components/ui/button";
import { ArrowDownIcon, ArrowUpDownIcon, ArrowUpIcon } from "lucide-react";

export default function IpLogsTable({
  response,
  filter = {},
  onFilterChange,
  isFetching = false,
}: Readonly<{
  response?: IpAuditLogsResponse;
  filter?: IpAuditLogParams;
  onFilterChange?: (patch: Partial<IpAuditLogParams>) => void;
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
    col: NonNullable<IpAuditLogParams["sort_by"]>,
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
      <IpLogsTableFilters
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
              <TableHead>{sortHeader("entity_id", "IP Record")}</TableHead>
              <TableHead>Session</TableHead>
              <TableHead>Old Value</TableHead>
              <TableHead>New Value</TableHead>
              <TableHead>{sortHeader("created_at", "Timestamp")}</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {data.length === 0 ? (
              <TableRow>
                <TableCell
                  colSpan={7}
                  className="text-muted-foreground h-24 text-center"
                >
                  {hasFilters
                    ? "No results match your filters."
                    : "No IP changes found."}
                </TableCell>
              </TableRow>
            ) : (
              data.map((log) => (
                <TableRow key={log.id}>
                  <TableCell>
                    <Badge
                      className={cn(
                        "text-xs font-medium",
                        getIpActionColor(log.action),
                      )}
                    >
                      {log.action}
                    </Badge>
                  </TableCell>
                  <TableCell className="text-muted-foreground text-xs">
                    {log.user_id ? (
                      <span className="inline-flex items-center gap-2.5">
                        <Badge variant="ghost" className="font-mono text-xs">
                          #{log.user_id}
                        </Badge>
                        {log.user_email}
                      </span>
                    ) : (
                      "—"
                    )}
                  </TableCell>
                  <TableCell className="text-muted-foreground text-xs">
                    <Badge variant="ghost" className="font-mono text-xs">
                      #{log.entity_id}
                    </Badge>
                  </TableCell>
                  <TableCell className="text-muted-foreground font-mono text-xs">
                    {log.session_id ?? "—"}
                  </TableCell>
                  <TableCell className="text-muted-foreground text-xs">
                    <IpValueCell value={log.old_value} />
                  </TableCell>
                  <TableCell className="text-muted-foreground text-xs">
                    <IpValueCell value={log.new_value} />
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

interface IpValueRecord {
  ip_address?: string;
  label?: string;
  comment?: string;
  [key: string]: unknown;
}

function IpValueCell({
  value,
}: Readonly<{ value: IpValueRecord | null | undefined }>) {
  if (!value) return <span>—</span>;

  return (
    <Tooltip>
      <TooltipTrigger asChild>
        <div className="cursor-default space-y-0.5">
          {value.ip_address && (
            <p className="font-mono font-semibold">{value.ip_address}</p>
          )}
          {value.label && (
            <p>
              <span className="text-muted-foreground">label: </span>
              {value.label}
            </p>
          )}
          {value.comment && (
            <p className="max-w-40 truncate">
              <span className="text-muted-foreground">note: </span>
              {value.comment}
            </p>
          )}
        </div>
      </TooltipTrigger>
      <TooltipContent side="right" className="w-max font-mono text-xs">
        <pre>{JSON.stringify(value, null, 2)}</pre>
      </TooltipContent>
    </Tooltip>
  );
}
