import { NavLink } from "react-router-dom";
import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  navigationMenuTriggerStyle,
} from "../ui/navigation-menu";
import { useAuthStore } from "@/stores/auth-store";
import { getNavItems } from "@/lib/nav-items";
import { useMemo } from "react";
import { Role } from "@wb-ip-ams/shared-types";

export default function HeaderNavigationMenu() {
  const { user } = useAuthStore();

  const isAdmin = user?.role === Role.SuperAdmin;

  const navItems = useMemo(() => getNavItems(isAdmin), [isAdmin]);

  return (
    <NavigationMenu className="hidden lg:flex">
      <NavigationMenuList>
        {navItems.map(({ url, label, icon: Icon }) => (
          <NavigationMenuItem key={url}>
            <NavigationMenuLink
              asChild
              className={navigationMenuTriggerStyle()}
            >
              <NavLink to={url}>
                <span className="inline-flex items-center gap-2.5">
                  <Icon className="size-4" />
                  {label}
                </span>
              </NavLink>
            </NavigationMenuLink>
          </NavigationMenuItem>
        ))}
      </NavigationMenuList>
    </NavigationMenu>
  );
}
