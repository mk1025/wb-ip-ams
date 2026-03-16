import AuthLayout from "@/components/layouts/auth-layout";
import DashboardLayout from "@/components/layouts/dashboard-layout";
import AdminRoute from "@/components/admin-route";
import ProtectedRoute from "@/components/protected-route";
import AuditLogPage from "@/pages/dashboard/audit/page";
import IpForm from "@/pages/dashboard/ips/_forms/IpForm";
import IpListPage from "@/pages/dashboard/ips/page";
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
          <Route path="/ips" element={<IpListPage />} />
          <Route path="/ips/create" element={<IpForm />} />
          <Route path="/ips/:id/edit" element={<IpForm />} />
          <Route element={<AdminRoute />}>
            <Route path="/audit" element={<AuditLogPage />} />
          </Route>
        </Route>
      </Route>

      {/* Redirects */}
      <Route path="/" element={<Navigate to="/dashboard" replace />} />
      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  );
}
