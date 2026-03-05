import { Spinner } from "@/components/ui/spinner";
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
import { cn } from "@/lib/utils";
import type { IpAuditLogResource } from "@wb-ip-ams/shared-types";
import CustomTablePagination from "../../../../components/common/CustomTablePagination";

function getIpActionColor(action: string): string {
  switch (action) {
    case "create":
      return "bg-green-100 text-green-700";
    case "update":
      return "bg-blue-100 text-blue-700";
    case "delete":
      return "bg-red-100 text-red-700";
    default:
      return "bg-gray-100 text-gray-700";
  }
}

export default function IpLogsTable({
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
  data?: IpAuditLogResource[];
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
        Failed to load IP audit logs.
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
            <TableHead>IP Record ID</TableHead>
            <TableHead>Old Value</TableHead>
            <TableHead>New Value</TableHead>
            <TableHead>Session ID</TableHead>
            <TableHead>Timestamp</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {data.length === 0 ? (
            <TableRow>
              <TableCell
                colSpan={7}
                className="text-muted-foreground h-24 text-center"
              >
                No IP changes found.
              </TableCell>
            </TableRow>
          ) : (
            data.map((log) => (
              <TableRow key={log.id}>
                <TableCell>
                  <span
                    className={cn(
                      `rounded px-2 py-0.5 text-xs font-medium`,
                      getIpActionColor(log.action),
                    )}
                  >
                    {log.action}
                  </span>
                </TableCell>
                <TableCell className="text-muted-foreground">
                  {log.user_id ?? "—"}
                </TableCell>
                <TableCell className="text-muted-foreground">
                  {log.entity_id}
                </TableCell>
                <TableCell className="text-muted-foreground text-xs">
                  <IpValueCell value={log.old_value} />
                </TableCell>
                <TableCell className="text-muted-foreground text-xs">
                  <IpValueCell value={log.new_value} />
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

interface IpValueRecord {
  ip_address?: string;
  label?: string;
  comment?: string;
  [key: string]: unknown;
}

function IpValueCell({ value }: { value: IpValueRecord | null | undefined }) {
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
            <p className="max-w-[160px] truncate">
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
