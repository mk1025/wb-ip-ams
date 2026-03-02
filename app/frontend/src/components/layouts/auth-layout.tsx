import { useAuthStore } from "@/stores/auth-store";
import { Navigate, Outlet } from "react-router-dom";

export default function AuthLayout() {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);

  if (isAuthenticated) {
    return <Navigate to="/dashboard" replace />;
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50">
      <Outlet />
    </div>
  );
}
