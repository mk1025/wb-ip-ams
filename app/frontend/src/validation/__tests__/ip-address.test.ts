import { describe, it, expect } from "vitest";
import {
  StoreIpAddressRequest,
  UpdateIpAddressRequest,
} from "@/validation/ip-address";

describe("StoreIpAddressRequest", () => {
  it("accepts a valid IPv4 address", () => {
    expect(
      StoreIpAddressRequest.safeParse({
        ip_address: "192.168.1.1",
        label: "My Server",
      }).success,
    ).toBe(true);
  });

  it("accepts a valid IPv6 address", () => {
    expect(
      StoreIpAddressRequest.safeParse({
        ip_address: "2001:db8::1",
        label: "IPv6 Server",
      }).success,
    ).toBe(true);
  });

  it("rejects an invalid IP address", () => {
    const result = StoreIpAddressRequest.safeParse({
      ip_address: "not-an-ip",
      label: "Bad",
    });
    expect(result.success).toBe(false);
  });

  it("rejects an empty label", () => {
    expect(
      StoreIpAddressRequest.safeParse({
        ip_address: "10.0.0.1",
        label: "",
      }).success,
    ).toBe(false);
  });

  it("rejects a label exceeding 255 characters", () => {
    expect(
      StoreIpAddressRequest.safeParse({
        ip_address: "10.0.0.1",
        label: "a".repeat(256),
      }).success,
    ).toBe(false);
  });

  it("accepts a label of exactly 255 characters", () => {
    expect(
      StoreIpAddressRequest.safeParse({
        ip_address: "10.0.0.1",
        label: "a".repeat(255),
      }).success,
    ).toBe(true);
  });

  it("accepts an optional comment", () => {
    expect(
      StoreIpAddressRequest.safeParse({
        ip_address: "10.0.0.1",
        label: "Test",
        comment: "Some note",
      }).success,
    ).toBe(true);
  });

  it("succeeds when comment is omitted", () => {
    expect(
      StoreIpAddressRequest.safeParse({
        ip_address: "10.0.0.1",
        label: "Test",
      }).success,
    ).toBe(true);
  });
});

describe("UpdateIpAddressRequest", () => {
  it("succeeds with a valid label", () => {
    expect(
      UpdateIpAddressRequest.safeParse({ label: "Updated Label" }).success,
    ).toBe(true);
  });

  it("fails with an empty label", () => {
    expect(UpdateIpAddressRequest.safeParse({ label: "" }).success).toBe(false);
  });

  it("fails when label is missing", () => {
    expect(UpdateIpAddressRequest.safeParse({}).success).toBe(false);
  });

  it("accepts an optional comment on update", () => {
    expect(
      UpdateIpAddressRequest.safeParse({
        label: "Updated",
        comment: "New note",
      }).success,
    ).toBe(true);
  });
});
