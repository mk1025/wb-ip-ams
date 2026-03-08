import z from "zod";

// AUTH SERVICE FORM REQUESTS

export const RegisterRequest = z
  .object({
    email: z
      .email("Please provide a valid email address")
      .min(1, "Email address is required"),
    password: z.string().min(8, "Password must be at least 8 characters"),
    password_confirmation: z
      .string()
      .min(8, "Password confirmation must be at least 8 characters"),
  })
  .superRefine(({ password, password_confirmation }, ctx) => {
    if (password !== password_confirmation) {
      ctx.addIssue({
        code: "custom",
        message: "Passwords do not match",
        path: ["password_confirmation"],
      });
    }
  });

export const LoginRequest = z.object({
  email: z
    .email("Please provide a valid email address")
    .min(1, "Email address is required"),
  password: z.string().min(1, "Password is required"),
});

export const RefreshTokenRequest = z.object({
  refresh_token: z
    .string("Refresh token must be a string")
    .min(1, "Refresh token is required"),
});
