import { useLogoutMutation } from "@/hooks/mutations/auth";
import { useAuthStore } from "@/stores/auth-store";
import { Outlet, useNavigate } from "react-router-dom";
import { Button } from "../ui/button";
import { Spinner } from "../ui/spinner";
import HeaderMobileMenu from "../common/HeaderMobileMenu";
import HeaderNavigationMenu from "../common/HeaderNavigationMenu";
import { LogOutIcon } from "lucide-react";

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
              <HeaderNavigationMenu />
            </div>

            <div className="flex items-center gap-4">
              <span className="hidden text-sm text-gray-700 lg:block">
                {user?.email}
              </span>
              {isAdmin && (
                <span className="hidden rounded bg-purple-100 px-2 py-1 text-xs text-purple-800 lg:block">
                  Super Admin
                </span>
              )}
              <Button
                variant="outline"
                size="sm"
                onClick={handleLogout}
                disabled={isPending}
                className="text-destructive hidden lg:flex"
              >
                {isPending ? <Spinner /> : <LogOutIcon />}
                Logout
              </Button>

              <HeaderMobileMenu />
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
