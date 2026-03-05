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
            <CardContent className="flex-1 space-y-3">
              {statsLoading ? (
                <div className="space-y-2">
                  <Skeleton className="h-8 w-16" />
                  <Skeleton className="h-4 w-24" />
                </div>
              ) : (
                <>
                  <p className="text-2xl font-bold">{stats?.total ?? 0}</p>
                  <div className="text-muted-foreground flex items-center gap-3 text-xs">
                    <span>
                      <span className="text-foreground font-semibold">
                        {stats?.mine ?? 0}
                      </span>{" "}
                      yours
                    </span>
                    <span className="text-border">·</span>
                    <span>
                      <span className="text-foreground font-semibold">
                        {stats?.total ?? 0}
                      </span>{" "}
                      total
                    </span>
                  </div>
                </>
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
