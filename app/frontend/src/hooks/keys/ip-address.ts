export const IP_ADDRESS_QUERY_KEYS = {
  ALL: ["ip-addresses"] as const,
  DETAIL: (id: number) => ["ip-addresses", id] as const,
};

export const IP_ADDRESS_MUTATION_KEYS = {
  CREATE: ["ip-addresses", "create"] as const,
  UPDATE: ["ip-addresses", "update"] as const,
  DELETE: ["ip-addresses", "delete"] as const,
};
