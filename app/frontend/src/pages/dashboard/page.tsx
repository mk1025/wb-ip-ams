import { Button } from "@/components/ui/button";
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { useGetIpStats } from "@/hooks/queries/ip-address";
import { useAuthStore } from "@/stores/auth-store";
import { LogsIcon, NetworkIcon, UserIcon, UserStarIcon } from "lucide-react";
import { Link } from "react-router-dom";

export default function DashboardPage() {
  const user = useAuthStore((state) => state.user);

  const isAdmin = user?.role === "super-admin";
  const { data: stats, isLoading: statsLoading } = useGetIpStats();

  return (
    <>
      <title>Dashboard - IP Address Manager</title>
      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Dashboard</h1>
          <p className="text-muted-foreground">Welcome back, {user?.email}</p>
        </div>

        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          <Card>
            <CardHeader>
              <CardTitle>Your Role</CardTitle>
              <CardDescription>Your current access level</CardDescription>
              <CardAction>
                <UserIcon className="text-muted-foreground size-5" />
              </CardAction>
            </CardHeader>
            <CardContent>
              <p className="inline-flex items-center gap-2.5 text-2xl font-bold text-purple-400 capitalize">
                {user?.role === "super-admin" && <UserStarIcon />}
                {user?.role?.replace("-", " ")}
              </p>
            </CardContent>
          </Card>

          <Card className="flex flex-col">
            <CardHeader>
              <CardTitle>IP Addresses</CardTitle>
              <CardAction>
                <NetworkIcon className="text-muted-foreground size-5" />
              </CardAction>
            </CardHeader>
            <CardContent className="flex-1">
              {statsLoading ? (
                <div className="flex gap-6">
                  <div className="space-y-1">
                    <Skeleton className="h-8 w-12" />
                    <Skeleton className="h-3 w-10" />
                  </div>
                  <div className="space-y-1">
                    <Skeleton className="h-8 w-12" />
                    <Skeleton className="h-3 w-10" />
                  </div>
                </div>
              ) : (
                <div className="flex items-stretch gap-0 divide-x">
                  <div className="flex flex-col pr-6">
                    <span className="text-3xl font-bold tabular-nums">
                      {stats?.total ?? 0}
                    </span>
                    <span className="text-muted-foreground mt-1 text-xs">
                      Total
                    </span>
                  </div>
                  <div className="flex flex-col pl-6">
                    <span className="text-3xl font-bold tabular-nums">
                      {stats?.mine ?? 0}
                    </span>
                    <span className="text-muted-foreground mt-1 text-xs">
                      Added by you
                    </span>
                  </div>
                </div>
              )}
            </CardContent>
            <CardFooter>
              <Button asChild size="sm" variant="outline">
                <Link to="/ips">View all IPs</Link>
              </Button>
            </CardFooter>
          </Card>

          {isAdmin && (
            <Card className="flex flex-col">
              <CardHeader>
                <CardTitle>Audit Logs</CardTitle>
                <CardDescription>System activity records</CardDescription>
                <CardAction>
                  <LogsIcon className="text-muted-foreground size-5" />
                </CardAction>
              </CardHeader>
              <CardContent className="flex-1 space-y-3">
                <p className="text-muted-foreground text-sm">
                  View auth events and IP changes across all users.
                </p>
              </CardContent>
              <CardFooter>
                <Button asChild size="sm" variant="outline">
                  <Link to="/audit">View audit logs</Link>
                </Button>
              </CardFooter>
            </Card>
          )}
        </div>
      </div>
    </>
  );
}
