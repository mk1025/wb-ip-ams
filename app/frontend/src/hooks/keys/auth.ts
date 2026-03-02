export const AUTH_QUERY_KEYS = {
  ME: ["auth", "currentUser"] as const,
};

export const AUTH_MUTATION_KEYS = {
  REGISTER: ["auth", "register"] as const,
  LOGIN: ["auth", "login"] as const,
  LOGOUT: ["auth", "logout"] as const,
  REFRESH: ["auth", "refresh"] as const,
};
