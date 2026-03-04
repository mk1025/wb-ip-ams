import { HomeIcon, LogsIcon, NetworkIcon } from "lucide-react";

export function getNavItems(isAdmin = false) {
  const headerItems = [
    {
      url: "/dashboard",
      label: "Dashboard",
      icon: HomeIcon,
    },
    {
      url: "/ips",
      label: "IP Addresses",
      icon: NetworkIcon,
    },
  ];

  const adminItems = [
    {
      url: "/audit",
      label: "Audit Logs",
      icon: LogsIcon,
    },
  ];

  return isAdmin ? [...headerItems, ...adminItems] : headerItems;
}
