# Architecture

```
Browser
  │
  ▼
Gateway (port 3000)
  ├──▶ Auth Service (port 8000)   — /auth/*, /audit/auth
  └──▶ IP Service   (port 8001)   — /ip-addresses/*, /audit/ip
```

All traffic goes through the Gateway. The frontend never calls Auth or IP directly.

---

## Gateway

Routes requests by path prefix and proxies them to the correct service. Forwards `Authorization`, `Cookie`, and standard `X-Forwarded-*` headers. Passes `Set-Cookie` from downstream responses back to the browser. Does not validate JWTs — that's each service's job. Returns `503` if a downstream service is unreachable.

---

## Auth & Session Lifecycle

Every `login`, `register`, and `token_refresh`:

1. Generates a UUID `session_id` and embeds it as a JWT claim.
2. Issues an access token (RS256, expires per `JWT_TTL`).
3. Issues a refresh token (64-char random string, stored in `refresh_tokens`) delivered as an `HttpOnly` cookie — path `/api/auth/refresh`, `SameSite=Lax`, `Secure` in production.
4. Writes an audit log entry with the `session_id`.

When the access token expires, the browser sends the cookie to `POST /auth/refresh` automatically. A new access token and `session_id` are issued. The refresh token is never readable by JS.

### RS256 key pair

Auth holds the private key and is the only service that can sign tokens. IP holds only the public key — it can verify but never forge. Keys are stored as base64-encoded PEM strings in env vars and decoded in `config/jwt.php`.

### Session ID in IP Service

IP reads `session_id` from the JWT payload via `JWTAuth::parseToken()->getPayload()->get('session_id')` and stores it on every audit log row.

---

## User Sync

Auth and IP have separate databases. On `login` and `register`, Auth calls:

```
POST /api/internal/users/sync
X-Internal-Secret: <shared secret>
Body: { id, email, role }
```

IP stores a minimal user mirror (id, email, role) for ownership checks and audit log display. The endpoint is only reachable within the Docker network. Sync failures are logged as warnings and don't affect the auth response.

---

## Audit Log Immutability

`AuthAuditLog` and `IpAuditLog` override `save()` and `delete()` to throw `LogicException` on any mutation attempt. Immutability is enforced at the model layer — no DB triggers needed.

---

## RBAC

| Operation | user | super-admin |
|---|:---:|:---:|
| View / create IP records | ✓ | ✓ |
| Edit own record | ✓ | ✓ |
| Edit any record | — | ✓ |
| Delete any record | — | ✓ |
| View audit dashboard | — | ✓ |

Authorization is checked inline in each controller method.

---

## Data Consistency

Services have separate databases — no distributed transactions. Trade-offs:

- **Users** — synced on login/register; stale data until the next sync on failure.
- **Audit logs** — written in the same request, always consistent with the action.
- **IP records** — owned entirely by IP Service; Auth has no knowledge of them.
