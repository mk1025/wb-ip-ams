import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Calendar } from "@/components/ui/calendar";
import {
  InputGroup,
  InputGroupAddon,
  InputGroupInput,
} from "@/components/ui/input-group";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import type {
  AuthAuditAction,
  AuthAuditLogParams,
} from "@/hooks/queries/audit-log";
import { cn } from "@/lib/utils";
import type { AuthAuditLogsResponse } from "@wb-ip-ams/shared-types";
import { format } from "date-fns";
import type { DateRange } from "react-day-picker";
import { CalendarIcon, SearchIcon, XIcon } from "lucide-react";
import { getAuthActionColor } from "../util";

export default function AuthLogsTableFilters({
  response,
  filter = {},
  onFilterChange,
}: Readonly<{
  response?: AuthAuditLogsResponse;
  filter?: AuthAuditLogParams;
  onFilterChange?: (patch: Partial<AuthAuditLogParams>) => void;
}>) {
  const total = response?.logs.total ?? 0;
  const filterOptions = response?.filter_options;
  const hasFilters =
    filter.user_id ||
    filter.action ||
    filter.ip_address ||
    filter.session_id ||
    filter.date_from ||
    filter.date_to;

  return (
    <div className="flex flex-wrap items-center gap-2">
      <InputGroup className="max-w-xs">
        <InputGroupInput
          placeholder="Search IP address..."
          type="search"
          className="font-mono text-xs"
          data-slot="input-group-control"
          value={filter.ip_address ?? ""}
          onChange={(e) =>
            onFilterChange?.({
              ip_address: e.target.value || undefined,
              page: 1,
            })
          }
        />
        <InputGroupAddon>
          <SearchIcon />
        </InputGroupAddon>
        <InputGroupAddon align="inline-end">
          {total} result{total === 1 ? "" : "s"}
        </InputGroupAddon>
      </InputGroup>
      <Select
        value={filter.user_id ?? "all"}
        onValueChange={(val) =>
          onFilterChange?.({
            user_id: val === "all" ? undefined : val,
            page: 1,
          })
        }
      >
        <SelectTrigger className="max-w-96">
          <SelectValue placeholder="All users" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All users</SelectItem>
          {filterOptions?.users.map((u) => (
            <SelectItem
              key={u.id}
              value={String(u.id)}
              className="*:[span]:last:w-full"
            >
              <span className="flex w-full min-w-0 items-center justify-between gap-2">
                <span className="inline-flex min-w-0 items-center gap-2.5 truncate">
                  <Badge variant="ghost" className="font-mono text-xs">
                    #{u.id}
                  </Badge>
                  {u.email}
                </span>
                <Badge className="shrink-0 tabular-nums">{u.count}</Badge>
              </span>
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
      <Select
        value={filter.action ?? "all"}
        onValueChange={(val) =>
          onFilterChange?.({
            action: val === "all" ? undefined : (val as AuthAuditAction),
            page: 1,
          })
        }
      >
        <SelectTrigger className="max-w-96">
          <SelectValue placeholder="All actions" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All actions</SelectItem>
          {filterOptions?.actions.map((a) => (
            <SelectItem
              key={a.value}
              value={a.value}
              className="*:[span]:last:w-full"
            >
              <span className="flex w-full min-w-0 items-center justify-between gap-2">
                <Badge className={cn(getAuthActionColor(a.value))}>
                  {a.value.replaceAll("_", " ")}
                </Badge>
                <Badge className="shrink-0 tabular-nums">{a.count}</Badge>
              </span>
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
      <InputGroup className="max-w-52">
        <InputGroupAddon>
          <SearchIcon />
        </InputGroupAddon>
        <InputGroupInput
          placeholder="Session ID..."
          type="search"
          className="font-mono text-xs"
          data-slot="input-group-control"
          value={filter.session_id ?? ""}
          onChange={(e) =>
            onFilterChange?.({
              session_id: e.target.value || undefined,
              page: 1,
            })
          }
        />
      </InputGroup>
      <Popover>
        <PopoverTrigger asChild>
          <Button
            variant="outline"
            className={cn(
              "w-56 justify-start gap-1.5 text-xs font-normal",
              !filter.date_from && !filter.date_to && "text-muted-foreground",
            )}
          >
            <CalendarIcon className="size-3.5" />
            {filter.date_from && filter.date_to ? (
              <>
                {format(new Date(filter.date_from), "MMM d, yyyy")}
                {" – "}
                {format(new Date(filter.date_to), "MMM d, yyyy")}
              </>
            ) : filter.date_from ? (
              <>From {format(new Date(filter.date_from), "MMM d, yyyy")}</>
            ) : filter.date_to ? (
              <>To {format(new Date(filter.date_to), "MMM d, yyyy")}</>
            ) : (
              "Date range"
            )}
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-auto p-0" align="start">
          <Calendar
            mode="range"
            numberOfMonths={2}
            selected={{
              from: filter.date_from ? new Date(filter.date_from) : undefined,
              to: filter.date_to ? new Date(filter.date_to) : undefined,
            }}
            onSelect={(range: DateRange | undefined) =>
              onFilterChange?.({
                date_from: range?.from
                  ? format(range.from, "yyyy-MM-dd")
                  : undefined,
                date_to: range?.to ? format(range.to, "yyyy-MM-dd") : undefined,
                page: 1,
              })
            }
          />
        </PopoverContent>
      </Popover>
      {hasFilters && (
        <Button
          variant="ghost"
          size="sm"
          className="gap-1.5"
          onClick={() =>
            onFilterChange?.({
              user_id: undefined,
              action: undefined,
              ip_address: undefined,
              session_id: undefined,
              date_from: undefined,
              date_to: undefined,
              page: 1,
            })
          }
        >
          <XIcon className="size-3.5" />
          Clear
        </Button>
      )}
    </div>
  );
}
