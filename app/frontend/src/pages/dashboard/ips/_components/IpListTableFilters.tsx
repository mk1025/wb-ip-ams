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
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectSeparator,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import type {
  GetIpAddressesParams,
  OwnershipFilter,
} from "@/hooks/queries/ip-address";
import { cn } from "@/lib/utils";
import type { IpAddressesResponse } from "@wb-ip-ams/shared-types";
import { format } from "date-fns";
import { CalendarIcon, SearchIcon, XIcon } from "lucide-react";
import { Spinner } from "@/components/ui/spinner";
import type { DateRange } from "react-day-picker";

function encodeOwnerValue(filter: GetIpAddressesParams): string {
  if (filter.owner_id) return `user:${filter.owner_id}`;
  if (filter.ownership === "mine") return "ownership:mine";
  if (filter.ownership === "others") return "ownership:others";
  return "ownership:all";
}

export default function IpListTableFilters({
  total,
  filter = {},
  filterOptions,
  isFetching,
  onFilterChange,
}: Readonly<{
  total: number;
  filter?: GetIpAddressesParams;
  filterOptions?: IpAddressesResponse["filter_options"];
  isFetching?: boolean;
  onFilterChange?: (patch: Partial<GetIpAddressesParams>) => void;
}>) {
  const hasFilters =
    filter.search ||
    filter.ownership !== "all" ||
    filter.owner_id ||
    filter.date_from ||
    filter.date_to;

  function handleOwnerChange(val: string) {
    if (val.startsWith("ownership:")) {
      const ownership = val.slice("ownership:".length) as OwnershipFilter;
      onFilterChange?.({ ownership, owner_id: undefined, page: 1 });
    } else {
      const id = val.slice("user:".length);
      onFilterChange?.({ owner_id: id, ownership: "all", page: 1 });
    }
  }

  return (
    <div className="flex flex-wrap items-center gap-2.5 md:flex-nowrap">
      <InputGroup className="max-w-xs">
        <InputGroupInput
          placeholder="Filter by IP address..."
          value={filter.search ?? ""}
          onChange={(e) =>
            onFilterChange?.({ search: e.target.value, page: 1 })
          }
        />
        <InputGroupAddon>
          {isFetching ? <Spinner /> : <SearchIcon />}
        </InputGroupAddon>
        <InputGroupAddon align="inline-end">
          {total} result{total !== 1 ? "s" : ""}
        </InputGroupAddon>
      </InputGroup>

      <Select
        value={encodeOwnerValue(filter)}
        onValueChange={handleOwnerChange}
      >
        <SelectTrigger className="w-44">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectGroup>
            <SelectItem value="ownership:all">All IPs</SelectItem>
            <SelectItem value="ownership:mine">My IPs</SelectItem>
            <SelectItem value="ownership:others">Others' IPs</SelectItem>
          </SelectGroup>
          {filterOptions?.owners && filterOptions.owners.length > 0 && (
            <>
              <SelectSeparator />
              <SelectGroup>
                <SelectLabel>By user</SelectLabel>
                {filterOptions.owners.map((o) => (
                  <SelectItem
                    key={o.id}
                    value={`user:${o.id}`}
                    className="*:[span]:last:w-full"
                  >
                    <span className="flex w-full min-w-0 items-center justify-between gap-2">
                      <span className="inline-flex min-w-0 items-center gap-2.5 truncate">
                        <Badge variant="ghost" className="font-mono text-xs">
                          #{o.id}
                        </Badge>
                        {o.email}
                      </span>
                      <Badge className="shrink-0 tabular-nums">{o.count}</Badge>
                    </span>
                  </SelectItem>
                ))}
              </SelectGroup>
            </>
          )}
        </SelectContent>
      </Select>

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
              search: "",
              ownership: "all",
              owner_id: undefined,
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
