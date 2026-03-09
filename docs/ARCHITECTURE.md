# Architecture

## Overview

The system is split into four independently containerised units: a React frontend, a Gateway, an Auth Service, and an IP Service. The frontend never calls Auth or IP directly — all traffic goes through the Gateway, which acts as a thin reverse proxy with no business logic of its own.

```
Browser
  │
  ▼
Gateway (port 3000)
  ├──▶ Auth Service (port 8000)   — /auth/*, /audit/auth
  └──▶ IP Service   (port 8001)   — /ip-addresses/*, /audit/ip
```

---

## Gateway

The Gateway's only job is routing. Incoming requests are matched by path prefix, the original `Authorization` header and relevant forwarding headers (`X-Forwarded-For`, `X-Forwarded-Host`, etc.) are preserved, and the full request is proxied to the correct downstream service. The response body and status code are passed back verbatim.

The Gateway does **not** validate JWTs. That responsibility stays with each service. This means:

- Services remain self-contained — they can be tested and deployed without the Gateway.
- There is no single decryption point; each service independently verifies the token using the shared secret.
- The Gateway stays simple and fast; adding a new service only requires a new route rule.

If a downstream service is unreachable, the Gateway returns a `503` with a consistent JSON error envelope rather than letting the connection error propagate.

---

## Authentication & Session Lifecycle

### Token issuance

Every successful `login`, `register`, and `token_refresh` call:

1. Generates a UUID (`session_id`).
2. Embeds it as a custom claim inside the JWT: `JWTAuth::claims(['session_id' => $sessionId])->fromUser($user)`.
3. Issues a separate opaque **refresh token** (64-char random string, 30-day expiry) stored in the `refresh_tokens` table.
4. Writes the `session_id` to the corresponding audit log row.

The access token expires after **60 minutes** by default (configurable via `JWT_TTL`). When it expires, the client exchanges the refresh token for a new access token via `POST /auth/refresh`, which also generates a new `session_id` — starting a new auditable session.

### Asymmetric signing (RS256)

Tokens are signed with **RS256** (RSA + SHA-256) using a 4096-bit key pair:

- The **Auth Service** holds the **private key** and is the only service that can issue tokens.
- The **IP Service** holds only the **public key** — it can verify tokens but can never forge one.

This is a meaningful security boundary: even if the IP Service were compromised, an attacker would gain no ability to mint valid JWTs. With a shared HMAC secret (HS256), any service holding the secret could forge tokens for any user.

The keys are stored as base64-encoded PEM strings in environment variables (`JWT_PRIVATE_KEY` / `JWT_PUBLIC_KEY`). The `config/jwt.php` in each service base64-decodes them before passing to the library. The IP Service's config sets `keys.private` to the public key value as a placeholder — the underlying `php-open-source-saver/jwt-auth` library requires both keys to be non-null for asymmetric algorithms, but only ever calls `getSigningKey()` (private) during token encoding, which the IP Service never does.

### Cross-service JWT validation

Both Auth and IP services use `php-open-source-saver/jwt-auth` backed by the same public key. Either service can independently validate any token without a network call. The IP Service uses the `auth:api` middleware on all its routes.

### Session ID propagation

When the IP Service handles an authenticated request, it reads the `session_id` claim directly from the incoming JWT payload:

```php
JWTAuth::parseToken()->getPayload()->get('session_id')
```

`parseToken()` is called explicitly (rather than relying on a previously set facade state) to ensure the token is read from the `Authorization` header of the proxied request. This value is stored on every `ip_audit_logs` row alongside the user, action, and entity.

---

## User Synchronisation

Auth and IP have independent databases. The IP Service stores a lightweight mirror of users (id, email, role) so it can:

- Enforce ownership rules without querying Auth.
- Join user email onto audit log rows for display.

Synchronisation happens automatically on `login` and `register`. Auth calls the IP Service's internal endpoint:

```
POST /api/internal/users/sync
Headers: X-Internal-Secret: <shared secret>
Body: { id, email, role }
```

The `X-Internal-Secret` header is validated by a middleware on the IP Service. This endpoint is not exposed through the Gateway — it is only reachable within the Docker network. The sync is fire-and-forget with a 5-second timeout; a failure is logged as a warning but does not break the auth response.

---

## Audit Log Immutability

Both `AuthAuditLog` and `IpAuditLog` models override Eloquent's `save()` and `delete()` methods:

- `save()` throws a `LogicException` if called on an already-persisted record (`$this->exists === true`), preventing updates.
- `delete()` always throws a `LogicException`, preventing deletion regardless of caller.

This means immutability is enforced at the application layer — no special database permissions or triggers are needed. Any code path that attempts to mutate an audit row (including privileged admin code) will receive an exception rather than a silent no-op.

---

## Role-Based Access Control

Roles (`user`, `super-admin`) are stored on the `users` table in Auth and mirrored to IP via the sync mechanism.

| Operation | user | super-admin |
|---|:---:|:---:|
| View all IP records | ✓ | ✓ |
| Create an IP record | ✓ | ✓ |
| Edit label/comment on own record | ✓ | ✓ |
| Edit label/comment on any record | — | ✓ |
| Delete any IP record | — | ✓ |
| View audit dashboard | — | ✓ |

Authorization is checked inside the relevant controller method. No separate policy classes are used — the rules are simple enough that inline checks keep the logic co-located with the action.

---

## Data Consistency

Because services hold separate databases, strict transactional consistency across them is not possible. The chosen trade-off:

- **User data** — synced on every login/register. A sync failure is non-fatal; the IP Service operates on stale data until the next successful sync.
- **Audit logs** — written locally within each service in the same request lifecycle, so they are always consistent with the action that produced them.
- **IP ownership** — stored entirely in the IP Service database. Auth has no knowledge of IP records.

This model prioritises availability and simplicity over strong consistency, which is appropriate for this use case.
