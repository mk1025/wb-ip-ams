# Requirements

## Overview

Build a web-based system for managing IP address records. Authenticated users should be able to create and manage IPv4/IPv6 entries, with access rules determined by their role. All activity must be captured in a tamper-proof audit log.

---

## Architecture

The system is composed of three independently deployable services:

- **Gateway Service** — single entry point; routes all frontend requests to the appropriate backend service
- **Auth Service** — handles identity: registration, login/logout, JWT issuance, and auth event logging
- **IP Management Service** — handles IP address CRUD and IP change logging

Each service must have its own isolated database. Inter-service communication happens via internal HTTP requests; the frontend must only communicate with the gateway.

---

## Authentication & Authorization

- Users authenticate with email and password and receive a JWT access token.
- The system must support token renewal so active sessions do not expire unexpectedly.
- Two roles exist: **user** (standard) and **super-admin** (privileged).

---

## IP Address Management

- Any authenticated user can create an IP record consisting of an IP address (IPv4 or IPv6), a label, and an optional comment.
- Any authenticated user can view all IP records regardless of who created them.
- Users may only edit records they own.
- Users may not delete any records.
- Super-admins may edit or delete any record.

---

## Audit Logging

- Every significant event must be logged: IP record creation, modification, deletion, user login, logout, and token refresh.
- Logs must be queryable to support two axes of investigation:
  - **By IP record** — all changes to a specific IP address over its lifetime, or within a particular session.
  - **By user** — all actions taken by a specific user over their lifetime, or within a particular session.
- Audit records are immutable — no user, regardless of role, may delete or modify them.
- A dedicated audit dashboard, restricted to super-admins, must expose these logs with appropriate filtering.

---

## Frontend

- Built with React and TypeScript (strict typing required).
- Communicates exclusively with the Gateway — never directly with Auth or IP services.
- UI/UX should be clean, intuitive, and usable by both technical and non-technical users.
