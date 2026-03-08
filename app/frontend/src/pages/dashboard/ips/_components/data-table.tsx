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
import { cn } from "@/lib/utils";
import type { GetIpAddressesParams } from "@/hooks/queries/ip-address";
import type { IpAddressesResponse } from "@wb-ip-ams/shared-types";
import IpListTableFilters from "./IpListTableFilters";

interface DataTableProps<TData, TValue> {
  columns: ColumnDef<TData, TValue>[];
  data: TData[];
  currentPage: number;
  lastPage: number;
  total: number;
  filter: GetIpAddressesParams;
  filterOptions?: IpAddressesResponse["filter_options"];
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
  filterOptions,
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
      <IpListTableFilters
        total={total}
        filter={filter}
        filterOptions={filterOptions}
        isFetching={isFetching}
        onFilterChange={onFilterChange}
      />

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
