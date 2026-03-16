import { useAuthStore } from "@/stores/auth-store";
import { Navigate, Outlet } from "react-router-dom";

export default function AdminRoute() {
  const user = useAuthStore((state) => state.user);

  if (user?.role !== "super-admin") {
    return <Navigate to="/dashboard" replace />;
  }

  return <Outlet />;
}
