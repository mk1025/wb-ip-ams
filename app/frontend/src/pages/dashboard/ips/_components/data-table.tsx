import {
  flexRender,
  getCoreRowModel,
  getSortedRowModel,
  useReactTable,
  type ColumnDef,
  type SortingState,
} from "@tanstack/react-table";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import CustomTablePagination from "@/components/common/CustomTablePagination";
import {
  InputGroup,
  InputGroupAddon,
  InputGroupInput,
} from "@/components/ui/input-group";
import {
  SearchIcon,
  UserLockIcon,
  UserSearchIcon,
  UsersIcon,
  XIcon,
} from "lucide-react";
import { Spinner } from "@/components/ui/spinner";
import { cn } from "@/lib/utils";
import type {
  GetIpAddressesParams,
  OwnershipFilter,
} from "@/hooks/queries/ip-address";
import { Button } from "@/components/ui/button";
import { ToggleGroup, ToggleGroupItem } from "@/components/ui/toggle-group";

interface DataTableProps<TData, TValue> {
  columns: ColumnDef<TData, TValue>[];
  data: TData[];
  currentPage: number;
  lastPage: number;
  total: number;
  filter: GetIpAddressesParams;
  onFilterChange: (patch: Partial<GetIpAddressesParams>) => void;
  isFetching?: boolean;
}

export function IpListDataTable<TData, TValue>({
  columns,
  data,
  currentPage,
  lastPage,
  total,
  filter,
  onFilterChange,
  isFetching,
}: DataTableProps<TData, TValue>) {
  /*
   * This is new.
   * I needed to put this here because of React Compiler.
   *
   */

  "use no memo";

  const sorting: SortingState = [
    { id: filter.sortBy ?? "created_at", desc: filter.sortDir !== "asc" },
  ];

  const table = useReactTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    manualSorting: true,
    onSortingChange: (updater) => {
      const next = typeof updater === "function" ? updater(sorting) : updater;
      const col = next[0];
      onFilterChange({
        sortBy: (col?.id ?? "created_at") as GetIpAddressesParams["sortBy"],
        sortDir: col?.desc === false ? "asc" : "desc",
        page: 1,
      });
    },
    state: {
      sorting,
    },
  });

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center gap-2.5 md:flex-nowrap">
        <InputGroup className="max-w-xs">
          <InputGroupInput
            placeholder="Filter by IP address..."
            value={filter.search}
            onChange={(e) =>
              onFilterChange({ search: e.target.value, page: 1 })
            }
          />
          <InputGroupAddon>
            {isFetching ? <Spinner /> : <SearchIcon />}
          </InputGroupAddon>
          <InputGroupAddon align="inline-end">
            {total} result{total !== 1 ? "s" : ""}
          </InputGroupAddon>
        </InputGroup>
        <ToggleGroup
          type="single"
          variant="outline"
          value={filter.ownership ?? "all"}
          onValueChange={(val) => {
            if (!val) return;
            onFilterChange({
              ownership: val as OwnershipFilter,
              page: 1,
            });
          }}
        >
          <ToggleGroupItem value="all">
            <UsersIcon />
            All
          </ToggleGroupItem>
          <ToggleGroupItem value="mine">
            <UserLockIcon />
            Mine
          </ToggleGroupItem>
          <ToggleGroupItem value="others">
            <UserSearchIcon />
            Others
          </ToggleGroupItem>
        </ToggleGroup>
        {(filter.search || filter.ownership !== "all") && (
          <Button
            variant="ghost"
            size="sm"
            className="gap-1.5"
            onClick={() =>
              onFilterChange({ search: "", ownership: "all", page: 1 })
            }
          >
            <XIcon className="size-3.5" />
            Clear
          </Button>
        )}
      </div>

      <div
        className={cn(
          "overflow-hidden rounded-md border",
          isFetching && "pointer-events-none cursor-progress opacity-60",
        )}
      >
        <Table>
          <TableHeader>
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id}>
                {headerGroup.headers.map((header) => (
                  <TableHead key={header.id}>
                    {header.isPlaceholder
                      ? null
                      : flexRender(
                          header.column.columnDef.header,
                          header.getContext(),
                        )}
                  </TableHead>
                ))}
              </TableRow>
            ))}
          </TableHeader>
          <TableBody>
            {table.getRowModel().rows?.length ? (
              table.getRowModel().rows.map((row) => (
                <TableRow
                  key={row.id}
                  data-state={row.getIsSelected() && "selected"}
                >
                  {row.getVisibleCells().map((cell) => (
                    <TableCell key={cell.id}>
                      {flexRender(
                        cell.column.columnDef.cell,
                        cell.getContext(),
                      )}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell
                  colSpan={columns.length}
                  className="h-24 text-center"
                >
                  No IP addresses found.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
        <CustomTablePagination
          currentPage={currentPage}
          lastPage={lastPage}
          total={total}
          onPageChange={(page) => onFilterChange({ page })}
        />
      </div>
    </div>
  );
}
