import {
  useGetIpAddresses,
  type GetIpAddressesParams,
} from "@/hooks/queries/ip-address";
import { useAuthStore } from "@/stores/auth-store";
import { Button } from "@/components/ui/button";
import { Spinner } from "@/components/ui/spinner";
import { Link } from "react-router-dom";
import { IpListColumns } from "./_components/columns";
import { IpListDataTable } from "./_components/data-table";
import { useEffect, useId, useMemo, useState } from "react";
import { useDebounce } from "@/hooks/use-debounce";
import { PlusIcon } from "lucide-react";
import { toast } from "sonner";

export default function IpListPage() {
  const [filter, setFilter] = useState<GetIpAddressesParams>({
    page: 1,
    search: "",
    ownership: "all",
    sortBy: "created_at",
    sortDir: "desc",
  });

  const toastId = useId();

  const debouncedSearch = useDebounce(filter.search, 400);

  const { data, isLoading, isFetching, isError, error, isSuccess } =
    useGetIpAddresses({
      ...filter,
      search: debouncedSearch,
    });
  const user = useAuthStore((state) => state.user);

  const isAdmin = user?.role === "super-admin";

  const columns = useMemo(
    () => IpListColumns({ currentUserId: user?.id, isAdmin }),
    [user?.id, isAdmin],
  );

  useEffect(() => {
    if (isError) {
      toast.error("Failed to load IP addresses. Please try again.", {
        id: toastId,
        description: error instanceof Error ? error.message : undefined,
      });
    } else if (isLoading) {
      toast.loading("Loading IP addresses...", {
        id: toastId,
        description: undefined,
      });
    } else if (isFetching) {
      toast.loading("Updating results...", {
        id: toastId,
        description: undefined,
      });
    } else {
      toast.dismiss(toastId);
    }
  }, [toastId, isLoading, isFetching, isError, error, isSuccess]);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Spinner />
      </div>
    );
  }

  if (isError) {
    return (
      <div className="text-destructive py-20 text-center">
        Failed to load IP addresses. Please try again.
      </div>
    );
  }

  return (
    <>
      <title>IP Addresses - IP Address Manager</title>
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold">IP Addresses</h1>
            <p className="text-muted-foreground">
              Manage your IP address records
            </p>
          </div>
          <Button asChild>
            <Link to="/ips/create">
              <PlusIcon className="size-4" />
              Add IP Address
            </Link>
          </Button>
        </div>

        <IpListDataTable
          isFetching={isFetching}
          columns={columns}
          data={data?.data ?? []}
          currentPage={data?.current_page ?? 1}
          lastPage={data?.last_page ?? 1}
          total={data?.total ?? 0}
          filter={filter}
          onFilterChange={(patch) =>
            setFilter((prev) => ({ ...prev, ...patch }))
          }
        />
      </div>
    </>
  );
}
