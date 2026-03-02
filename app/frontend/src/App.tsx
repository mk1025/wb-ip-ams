import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import "./App.css";
import { Toaster } from "./components/ui/sonner";
import AuthLayout from "./components/layouts/auth-layout";
import ProtectedRoute from "./components/protected-route";
import DashboardLayout from "./components/layouts/dashboard-layout";
import NotFoundPage from "./pages/not-found-page";
import LoginPage from "./pages/login/page";

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route element={<AuthLayout />}>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<div>Register Page</div>} />
        </Route>

        <Route element={<ProtectedRoute />}>
          <Route element={<DashboardLayout />}>
            <Route path="/dashboard" element={<div>Dashboard Content</div>} />
          </Route>
        </Route>

        <Route path="/" element={<Navigate to="/dashboard" replace />} />

        <Route path="*" element={<NotFoundPage />} />
      </Routes>
      <Toaster
        richColors
        position="bottom-right"
        duration={10_000}
        closeButton
        theme="light"
      />
    </BrowserRouter>
  );
}
