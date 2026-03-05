import { useGetIpAddresses } from "@/hooks/queries/ip-address";
import { useAuthStore } from "@/stores/auth-store";
import { Button } from "@/components/ui/button";
import { Spinner } from "@/components/ui/spinner";
import { Link } from "react-router-dom";
import { IpListColumns } from "./_components/columns";
import { IpListDataTable } from "./_components/data-table";
import { useMemo, useState } from "react";

export default function IpListPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");

  const { data, isLoading, isError } = useGetIpAddresses({ page, search });
  const user = useAuthStore((state) => state.user);

  const isAdmin = user?.role === "super-admin";

  const columns = useMemo(
    () => IpListColumns({ currentUserId: user?.id, isAdmin }),
    [user?.id, isAdmin],
  );

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
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">IP Addresses</h1>
          <p className="text-muted-foreground">
            Manage your IP address records
          </p>
        </div>
        <Button asChild>
          <Link to="/ips/create">Add IP Address</Link>
        </Button>
      </div>

      <IpListDataTable
        columns={columns}
        data={data?.data ?? []}
        currentPage={data?.current_page ?? 1}
        lastPage={data?.last_page ?? 1}
        total={data?.total ?? 0}
        search={search}
        onSearchChange={(val) => {
          setSearch(val);
          setPage(1);
        }}
        onPageChange={setPage}
      />
    </div>
  );
}
