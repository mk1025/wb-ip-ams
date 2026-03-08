import { describe, it, expect } from "vitest";
import { RegisterRequest, LoginRequest } from "@/validation/auth";

describe("RegisterRequest", () => {
  it("succeeds for valid input", () => {
    expect(
      RegisterRequest.safeParse({
        email: "user@example.com",
        password: "password123",
        password_confirmation: "password123",
      }).success,
    ).toBe(true);
  });

  it("fails for invalid email format", () => {
    expect(
      RegisterRequest.safeParse({
        email: "not-an-email",
        password: "password123",
        password_confirmation: "password123",
      }).success,
    ).toBe(false);
  });

  it("fails when password is shorter than 8 characters", () => {
    expect(
      RegisterRequest.safeParse({
        email: "user@example.com",
        password: "short",
        password_confirmation: "short",
      }).success,
    ).toBe(false);
  });

  it("fails when email is missing", () => {
    expect(
      RegisterRequest.safeParse({
        password: "password123",
        password_confirmation: "password123",
      }).success,
    ).toBe(false);
  });

  it("fails when password is missing", () => {
    expect(
      RegisterRequest.safeParse({
        email: "user@example.com",
        password_confirmation: "password123",
      }).success,
    ).toBe(false);
  });

  it("fails when passwords do not match", () => {
    const result = RegisterRequest.safeParse({
      email: "user@example.com",
      password: "password123",
      password_confirmation: "different123",
    });
    expect(result.success).toBe(false);
    if (!result.success) {
      expect(result.error.issues[0].path).toContain("password_confirmation");
      expect(result.error.issues[0].message).toBe("Passwords do not match");
    }
  });
});

describe("LoginRequest", () => {
  it("succeeds for valid input", () => {
    expect(
      LoginRequest.safeParse({
        email: "user@example.com",
        password: "anypassword",
      }).success,
    ).toBe(true);
  });

  it("fails for invalid email format", () => {
    expect(
      LoginRequest.safeParse({
        email: "bad-email",
        password: "anypassword",
      }).success,
    ).toBe(false);
  });

  it("fails for empty password", () => {
    expect(
      LoginRequest.safeParse({
        email: "user@example.com",
        password: "",
      }).success,
    ).toBe(false);
  });

  it("fails when email is missing", () => {
    expect(LoginRequest.safeParse({ password: "anypassword" }).success).toBe(
      false,
    );
  });
});
