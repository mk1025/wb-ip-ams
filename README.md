# IP Address Management System

A small full-stack project for managing IPv4/IPv6 addresses across a microservices backend. Built with Laravel 12 and React 19.

---

## Architecture

All traffic goes through a **Gateway** service that proxies to the two backend services. Each service has its own database.

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

| Service | Port |
|---------|------|
| Frontend | `80` |
| Gateway | `3000` |
| Auth Service | `8000` |
| IP Service | `8001` |
| MySQL (auth) | `3307` |
| MySQL (ip) | `3308` |

Tokens are signed with **RS256** — the Auth Service holds the private key, the IP Service only has the public key, so a compromised IP Service cannot forge tokens.

---

## Quick Start

**Prerequisites:** Docker, Docker Compose, Git

```bash
git clone <repo-url>
cd wb-ip-ams
bash scripts/setup.sh
docker compose up --build
```

`setup.sh` copies the env files, generates the RSA key pair, and installs dependencies. First build takes a few minutes while migrations and seeds run.

Open [http://localhost](http://localhost).

**Seed accounts:**

| Email | Password | Role |
|-------|----------|------|
| `admin@example.com` | `password` | super-admin |
| `alice@example.com` | `password` | user |
| `bob@example.com` | `password` | user |

---

## Local Development (without Docker)

**Prerequisites:** PHP 8.3+, Composer, Node.js 20+, pnpm, MySQL 8.0

Run `bash scripts/setup.sh` first to copy env files and generate keys, then bring up each service manually:

```bash
cd app/auth-service && composer install && php artisan migrate --seed && php artisan serve --port=8000
cd app/ip-service   && composer install && php artisan migrate --seed && php artisan serve --port=8001
cd app/gateway      && composer install && php artisan migrate       && php artisan serve --port=3000
```

For the frontend:

```bash
pnpm --filter frontend run dev
```

> `shared-types` is built as part of `setup.sh`. If you pull new type changes later, run `pnpm --filter @wb-ip-ams/shared-types run build` again.

---

## Testing

```bash
bash scripts/test.sh
```

Or run suites individually:

```bash
cd app/auth-service && ./vendor/bin/phpunit --no-coverage
cd app/ip-service   && ./vendor/bin/phpunit --no-coverage
cd app/gateway      && ./vendor/bin/phpunit --no-coverage
pnpm --filter frontend run test --run
```

---

## API Reference

All requests go to the gateway at `http://localhost:3000/api`. Protected routes require `Authorization: Bearer <token>`.

### Authentication

| Method | Path | Auth |
|--------|------|:----:|
| `POST` | `/auth/register` | — |
| `POST` | `/auth/login` | — |
| `POST` | `/auth/refresh` | ✓ |
| `POST` | `/auth/logout` | ✓ |
| `GET` | `/auth/me` | ✓ |

### IP Addresses

| Method | Path | Auth |
|--------|------|:----:|
| `GET` | `/ip-addresses` | ✓ |
| `POST` | `/ip-addresses` | ✓ |
| `GET` | `/ip-addresses/{id}` | ✓ |
| `PUT` | `/ip-addresses/{id}` | ✓ |
| `DELETE` | `/ip-addresses/{id}` | ✓ |
| `GET` | `/ip-addresses/stats` | ✓ |

`GET /ip-addresses` accepts `?ownership=all|mine|others`, `search`, `sort_by`, `sort_dir`, `page`.

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
│   ├── auth-service/     # JWT auth, user management, auth audit log
│   ├── ip-service/       # IP CRUD, ownership, IP audit log
│   ├── gateway/          # reverse proxy
│   └── frontend/         # React + TypeScript SPA
├── packages/
│   └── shared-types/     # shared TypeScript interfaces
├── scripts/              # setup, test, lint, key generation
└── docker-compose.yaml
```

## Tech Stack

| | |
|---|---|
| Backend | PHP 8.3, Laravel 12 |
| Auth | RS256 JWT (`php-open-source-saver/jwt-auth`) |
| Databases | MySQL 8.0 (auth + IP), SQLite (gateway) |
| Frontend | React 19, TypeScript, Vite |
| UI | ShadCN UI, Tailwind CSS v4 |
| Data fetching | TanStack Query v5 |
| State | Zustand |
| Routing | React Router v7 |
