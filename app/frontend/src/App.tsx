import { BrowserRouter } from "react-router-dom";
import "./App.css";
import { Toaster } from "./components/ui/sonner";
import AppRoutes from "./routes";

export default function App() {
  return (
    <BrowserRouter>
      <AppRoutes />
      <Toaster
        richColors
        position="top-center"
        duration={10_000}
        closeButton
        theme="system"
      />
    </BrowserRouter>
  );
}
