# IP Address Management System

A microservices-based web application for managing IPv4/IPv6 addresses with role-based access control and tamper-proof audit logging.

---

## Architecture

All client traffic flows through a single **Gateway** service, which proxies requests to the appropriate backend service. Services are independently deployed and database-isolated.

```
┌─────────────┐     ┌─────────────────┐     ┌──────────────────┐
│   Frontend  │────▶│    Gateway      │────▶│  Auth Service    │
│  (React/TS) │     │  (Laravel 12)   │     │  (Laravel 12)    │
└─────────────┘     └────────┬────────┘     └──────────────────┘
                             │
                             ▼
                    ┌──────────────────┐
                    │   IP Service     │
                    │  (Laravel 12)    │
                    └──────────────────┘
```

| Service | Port | Responsibility |
|---------|------|----------------|
| Frontend | `80` | React + TypeScript SPA served by nginx |
| Gateway | `3000` | Single API entry point — proxies to backend services |
| Auth Service | `8000` | Registration, login/logout, JWT issuance, auth audit log |
| IP Service | `8001` | IP address CRUD, ownership rules, IP audit log |
| MySQL (auth) | `3307` | Auth service database |
| MySQL (ip) | `3308` | IP service database |

---

## Features

- **JWT Authentication** — Login, registration, logout, and automatic token refresh via a separate refresh token
- **IP Address Management** — Full CRUD for IPv4/IPv6 records with a label and optional comment
- **Role-Based Access Control** — Users manage their own IPs; super-admins can modify or delete any record
- **Immutable Audit Logs** — Append-only logs for all auth events and IP changes; deletion is blocked at the model level
- **Session Tracking** — Every audit entry is linked to the JWT session that produced it
- **Admin Dashboard** — Super-admin-only audit viewer with filters by user, action, IP address, session ID, record ID, and date range

---

## Quick Start (Docker)

**Prerequisites:** Docker, Docker Compose, Git

### 1. Clone

```bash
git clone <repo-url>
cd wb-ip-ams
```

### 2. Set the shared JWT secret

Both the Auth and IP services must share the same secret to validate tokens. Set it before starting Docker:

```bash
# Option A — export in your shell
export JWT_SECRET=$(openssl rand -base64 64 | tr -d '\n')

# Option B — create a .env file at the project root
echo "JWT_SECRET=$(openssl rand -base64 64 | tr -d '\n')" > .env
```

### 3. Build and start

```bash
docker compose up --build
```

> First startup takes a few minutes. Each service installs dependencies, generates an app key, and runs migrations automatically.

### 4. Open the app

[http://localhost](http://localhost)

### Default seed accounts

| Email | Password | Role |
|-------|----------|------|
| `admin@example.com` | `password` | super-admin |
| `alice@example.com` | `password` | user |
| `bob@example.com` | `password` | user |

---

## Local Development (without Docker)

**Prerequisites:** PHP 8.3+, Composer, Node.js 20+, pnpm, MySQL 8.0

### Auth Service

```bash
cd app/auth-service
composer install
cp .env.example .env          # then set DB_*, JWT_SECRET
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8000
```

### IP Service

```bash
cd app/ip-service
composer install
cp .env.example .env          # then set DB_*, JWT_SECRET (must match auth-service)
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8001
```

### Gateway

```bash
cd app/gateway
composer install
cp .env.example .env          # then set AUTH_SERVICE_URL and IP_SERVICE_URL
php artisan key:generate
php artisan migrate
php artisan serve --port=3000
```

### Frontend

```bash
# From the project root
pnpm install
pnpm --filter @wb-ip-ams/shared-types run build
VITE_API_BASE_URL=http://localhost:3000/api pnpm --filter frontend run dev
```

---

## Testing

Each backend service has a PHPUnit suite. The frontend uses Vitest.

```bash
# Auth service (34 tests)
cd app/auth-service && ./vendor/bin/phpunit --no-coverage

# IP service (50 tests)
cd app/ip-service && ./vendor/bin/phpunit --no-coverage

# Gateway (2 tests)
cd app/gateway && ./vendor/bin/phpunit --no-coverage

# Frontend (62 tests)
pnpm --filter frontend run test --run
```

---

## API Reference

All requests are made to the **gateway** (`http://localhost:3000/api`). Include the JWT as `Authorization: Bearer <token>` on protected routes.

### Authentication

| Method | Path | Auth | Description |
|--------|------|:----:|-------------|
| `POST` | `/auth/register` | — | Create an account; returns tokens |
| `POST` | `/auth/login` | — | Authenticate; returns `access_token` + `refresh_token` |
| `POST` | `/auth/refresh` | ✓ | Exchange a refresh token for a new access token |
| `POST` | `/auth/logout` | ✓ | Invalidate the current session |
| `GET` | `/auth/me` | ✓ | Return the authenticated user |

### IP Addresses

| Method | Path | Auth | Description |
|--------|------|:----:|-------------|
| `GET` | `/ip-addresses` | ✓ | Paginated list; supports `?ownership=all\|mine\|others`, `search`, `sort_by`, `sort_dir`, `page` |
| `POST` | `/ip-addresses` | ✓ | Create a new IP record |
| `GET` | `/ip-addresses/{id}` | ✓ | Retrieve a single record |
| `PUT` | `/ip-addresses/{id}` | ✓ | Update label / comment (owner or super-admin) |
| `DELETE` | `/ip-addresses/{id}` | ✓ | Delete a record (super-admin only) |
| `GET` | `/ip-addresses/stats` | ✓ | Summary counts `{ total, mine, others }` |

### Audit Logs *(super-admin only)*

| Method | Path | Filters |
|--------|------|---------|
| `GET` | `/audit/auth` | `user_id`, `action`, `ip_address`, `session_id`, `date_from`, `date_to`, `page` |
| `GET` | `/audit/ip` | `user_id`, `entity_id`, `action`, `ip_address`, `session_id`, `date_from`, `date_to`, `page` |

---

## Project Structure

```
wb-ip-ams/
├── app/
│   ├── auth-service/     # Laravel 12 — JWT auth, user management, auth audit log
│   ├── ip-service/       # Laravel 12 — IP CRUD, ownership, IP audit log
│   ├── gateway/          # Laravel 12 — API gateway / reverse proxy
│   └── frontend/         # React 19, TypeScript, Vite, ShadCN UI
├── packages/
│   └── shared-types/     # Shared TypeScript interfaces (API responses, models)
├── docs/                 # Requirements and audit notes
├── postman/              # Postman collection + environments
└── docker-compose.yaml
```

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.3, Laravel 12 |
| Authentication | `php-open-source-saver/jwt-auth` |
| Databases | MySQL 8.0 (auth + IP services), SQLite (gateway) |
| Frontend | React 19, TypeScript, Vite |
| UI | ShadCN UI, Tailwind CSS |
| Data fetching | TanStack Query v5 |
| State | Zustand |
| Routing | React Router v7 |
