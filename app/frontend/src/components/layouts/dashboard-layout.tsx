import { useLogoutMutation } from "@/hooks/mutations/auth";
import { useAuthStore } from "@/stores/auth-store";
import { Outlet, useNavigate } from "react-router-dom";
import { Button } from "../ui/button";
import { Spinner } from "../ui/spinner";

export default function DashboardLayout() {
  const { user } = useAuthStore();

  const navigate = useNavigate();

  const { mutateAsync: logout, isPending } = useLogoutMutation();

  async function handleLogout() {
    await logout().then(() => {
      navigate("/login");
    });
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="border-b bg-white">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="flex h-16 items-center justify-between">
            <h1 className="text-xl font-bold">IP Address Manager</h1>
            <div className="flex items-center gap-4">
              <span className="text-sm text-gray-700">{user?.email}</span>
              {user?.role === "super-admin" && (
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
