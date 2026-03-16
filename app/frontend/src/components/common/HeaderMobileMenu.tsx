import { LogOutIcon, MenuIcon } from "lucide-react";
import { Button } from "../ui/button";
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "../ui/sheet";
import { NavLink, useNavigate } from "react-router-dom";
import { useAuthStore } from "@/stores/auth-store";
import { useLogoutMutation } from "@/hooks/mutations/auth";
import { useMemo, useState } from "react";
import { Spinner } from "../ui/spinner";
import { cn } from "@/lib/utils";
import { getNavItems } from "@/lib/nav-items";
import { Role } from "@wb-ip-ams/shared-types";

export default function HeaderMobileMenu() {
  const { user } = useAuthStore();

  const navigate = useNavigate();

  const [mobileOpen, setMobileOpen] = useState(false);

  const isAdmin = user?.role === Role.SuperAdmin;

  const { mutateAsync: logout, isPending } = useLogoutMutation();

  async function handleLogout() {
    await logout().finally(() => navigate("/login"));
  }

  const navItems = useMemo(() => getNavItems(isAdmin), [isAdmin]);

  return (
    <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
      <SheetTrigger asChild>
        <Button variant="ghost" size="icon" className="lg:hidden">
          <MenuIcon className="h-5 w-5" />
          <span className="sr-only">Open menu</span>
        </Button>
      </SheetTrigger>
      <SheetContent side="right">
        <SheetHeader>
          <SheetTitle>IP Address Manager</SheetTitle>
        </SheetHeader>
        <nav className="flex flex-col gap-1.5 p-4">
          {navItems.map(({ url, label, icon: Icon }) => (
            <NavLink
              key={url}
              to={url}
              onClick={() => setMobileOpen(false)}
              className={({ isActive }) =>
                cn(
                  "inline-flex items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50 hover:text-gray-900",
                  isActive && "bg-gray-100 text-gray-900 hover:bg-gray-100",
                )
              }
            >
              <Icon className="size-4" />
              {label}
            </NavLink>
          ))}
        </nav>
        <div className="mt-6 flex flex-col gap-2.5 border-t p-4 pt-6">
          <div className="truncate text-sm text-gray-700">{user?.email}</div>
          {isAdmin && (
            <span className="w-fit rounded bg-purple-100 px-2 py-1 text-xs text-purple-800">
              Super Admin
            </span>
          )}
          <Button
            type="button"
            variant="outline"
            onClick={() => {
              setMobileOpen(false);
              handleLogout();
            }}
            disabled={isPending}
            className="text-destructive"
          >
            {isPending ? <Spinner /> : <LogOutIcon />}
            Logout
          </Button>
        </div>
      </SheetContent>
    </Sheet>
  );
}
