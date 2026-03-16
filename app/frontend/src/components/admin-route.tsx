import { useAuthStore } from "@/stores/auth-store";
import { Role } from "@wb-ip-ams/shared-types";
import { Navigate, Outlet } from "react-router-dom";

export default function AdminRoute() {
  const user = useAuthStore((state) => state.user);

  if (user?.role !== Role.SuperAdmin) {
    return <Navigate to="/dashboard" replace />;
  }

  return <Outlet />;
}
