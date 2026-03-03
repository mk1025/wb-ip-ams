import { useLogoutMutation } from "@/hooks/mutations/auth";
import { useAuthStore } from "@/stores/auth-store";
import { NavLink, Outlet, useNavigate } from "react-router-dom";
import { Button } from "../ui/button";
import { Spinner } from "../ui/spinner";
import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  navigationMenuTriggerStyle,
} from "../ui/navigation-menu";

export default function DashboardLayout() {
  const navigate = useNavigate();

  const { user } = useAuthStore();

  const { mutateAsync: logout, isPending } = useLogoutMutation();

  async function handleLogout() {
    await logout().then(() => navigate("/login"));
  }

  const isAdmin = user?.role === "super-admin";

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="border-b bg-white">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="flex h-16 items-center justify-between">
            <div className="flex items-center gap-6">
              <h1 className="text-xl font-bold">IP Address Manager</h1>
              <NavigationMenu>
                <NavigationMenuList>
                  <NavigationMenuItem>
                    <NavigationMenuLink
                      asChild
                      className={navigationMenuTriggerStyle()}
                    >
                      <NavLink to="/dashboard">Dashboard</NavLink>
                    </NavigationMenuLink>
                  </NavigationMenuItem>
                  <NavigationMenuItem>
                    <NavigationMenuLink
                      asChild
                      className={navigationMenuTriggerStyle()}
                    >
                      <NavLink to="/ips">IP Addresses</NavLink>
                    </NavigationMenuLink>
                  </NavigationMenuItem>
                  {isAdmin && (
                    <NavigationMenuItem>
                      <NavigationMenuLink
                        asChild
                        className={navigationMenuTriggerStyle()}
                      >
                        <NavLink to="/audit">Audit Logs</NavLink>
                      </NavigationMenuLink>
                    </NavigationMenuItem>
                  )}
                </NavigationMenuList>
              </NavigationMenu>
            </div>

            <div className="flex items-center gap-4">
              <span className="text-sm text-gray-700">{user?.email}</span>
              {isAdmin && (
                <span className="rounded bg-purple-100 px-2 py-1 text-xs text-purple-800">
                  Super Admin
                </span>
              )}
              <Button
                variant="outline"
                size="sm"
                onClick={handleLogout}
                disabled={isPending}
              >
                {isPending && <Spinner />}
                Logout
              </Button>
            </div>
          </div>
        </div>
      </header>
      <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <Outlet />
      </main>
    </div>
  );
}
