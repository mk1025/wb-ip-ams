import { useEffect, useState } from "react";
import axios from "axios";
import { useAuthStore } from "@/stores/auth-store";
import type {
  APIResponse,
  TokenResource,
  UserResource,
} from "@wb-ip-ams/shared-types";

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

interface Props {
  readonly children: React.ReactNode;
}

export default function AuthInitializer({ children }: Props) {
  const { isAuthenticated, setAuth } = useAuthStore();

  const [ready, setReady] = useState(isAuthenticated);

  useEffect(() => {
    if (isAuthenticated) return;

    axios
      .post<APIResponse<TokenResource>>(
        `${API_BASE_URL}/auth/refresh`,
        {},
        { withCredentials: true },
      )
      .then(async (refreshRes) => {
        const accessToken = refreshRes.data?.data?.access_token;

        if (!accessToken) return;

        const meRes = await axios.get<APIResponse<UserResource>>(
          `${API_BASE_URL}/auth/me`,
          {
            withCredentials: true,
            headers: { Authorization: `Bearer ${accessToken}` },
          },
        );

        const user = meRes.data?.data;

        if (user) {
          setAuth(user, accessToken);
        }
      })
      .finally(() => {
        setReady(true);
      });
  }, []);

  if (!ready) return null;

  return <>{children}</>;
}
