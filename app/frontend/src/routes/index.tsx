import AuthLayout from "@/components/layouts/auth-layout";
import DashboardLayout from "@/components/layouts/dashboard-layout";
import ProtectedRoute from "@/components/protected-route";
import DashboardPage from "@/pages/dashboard/page";
import LoginPage from "@/pages/login/page";
import NotFoundPage from "@/pages/not-found-page";
import RegisterPage from "@/pages/register/page";
import { Navigate, Route, Routes } from "react-router-dom";

export default function AppRoutes() {
  return (
    <Routes>
      {/* Public */}
      <Route element={<AuthLayout />}>
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
      </Route>

      {/* Protected */}
      <Route element={<ProtectedRoute />}>
        <Route element={<DashboardLayout />}>
          <Route path="/dashboard" element={<DashboardPage />} />
        </Route>
      </Route>

      {/* Redirects */}
      <Route path="/" element={<Navigate to="/dashboard" replace />} />
      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  );
}
