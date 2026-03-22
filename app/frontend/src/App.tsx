import { BrowserRouter } from "react-router-dom";
import "./App.css";
import { Toaster } from "./components/ui/sonner";
import AppRoutes from "./routes";
import AuthInitializer from "./components/auth-initializer";

export default function App() {
  return (
    <BrowserRouter>
      <AuthInitializer>
        <AppRoutes />
      </AuthInitializer>
      <Toaster
        richColors
        position="top-center"
        duration={5_000}
        closeButton
        theme="light"
      />
    </BrowserRouter>
  );
}
