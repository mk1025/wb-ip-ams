import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";

export default function NotFoundPage() {
  return (
    <>
      <title>404 Not Found - IP Address Manager</title>
      <div className="flex min-h-screen items-center justify-center bg-gray-50">
        <div className="text-center">
          <h1 className="text-6xl font-bold text-gray-900">404</h1>
          <p className="mt-4 text-xl text-gray-600">Page not found</p>
          <p className="mt-2 text-gray-500">
            The page you're looking for doesn't exist.
          </p>
          <Button asChild className="mt-6">
            <Link to="/">Go back home</Link>
          </Button>
        </div>
      </div>
    </>
  );
}
