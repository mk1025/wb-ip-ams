import z from "zod";

export const StoreIpAddressRequest = z.object({
  ip_address: z.union(
    [
      z.ipv4("Invalid IPv4 or IPv6 address"),
      z.ipv6("Invalid IPv4 or IPv6 address"),
    ],
    "Invalid IPv4 or IPv6 address",
  ),
  label: z
    .string()
    .min(1, "Label is required")
    .max(255, "Label must not exceed 255 characters"),
  comment: z.string().optional(),
});

export const UpdateIpAddressRequest = z.object({
  label: z
    .string()
    .min(1, "Label is required")
    .max(255, "Label must not exceed 255 characters"),
  comment: z.string().optional(),
});
