import z from "zod";

export const StoreIpAddressRequest = z.object({
  ip_address: z.string().min(1, "IP address is required"),
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
