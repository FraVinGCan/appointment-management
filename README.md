# Appointment Management System

A full-stack appointment booking platform for service businesses, split into two independent apps:

| App | Path | Stack |
| --- | --- | --- |
| **API** | [`backend/`](backend/) | Laravel 13 (PHP ≥ 8.3), Sanctum cookie auth, MySQL |
| **SPA** | [`frontend/`](frontend/) | Vue 3, Vite, Nuxt UI v4, Tailwind CSS 4, Pinia |

There is no root package manager or test runner — always run commands from the app directory they belong to.

## Features

- Two role types: **admins** manage appointments, clients, and services; **clients** register, browse active services, book appointments, and cancel their own bookings.
- Appointment lifecycle: `Requested → Confirmed → Completed` (or `Cancelled`), with Low/Medium/High priority.
- Clients and services can be deactivated without deleting history.
- Cookie-based authentication (Laravel Sanctum) with route guards on the frontend.

## Prerequisites

- PHP ≥ 8.3, Composer
- Node.js `^22.18.0 || >=24.12.0`
- MySQL (or adjust `DB_CONNECTION` in `backend/.env`)
- On Windows PowerShell, use `npm` instead of `npm` if execution policy blocks `npm.ps1`.

## Quick start

### 1. Backend

```bash
cd backend
composer run setup
```

This installs PHP + JS dependencies, creates `.env`, generates the app key, migrates, and builds backend assets. Then seed demo data (admin account, sample clients/services/appointments):

```bash
php artisan migrate:fresh --seed
```

Start the API (server + queue listener + asset watcher):

```bash
composer run dev   # http://localhost:8000
```

### 2. Frontend

```bash
cd frontend
npm install
cp .env.example .env
npm run dev        # http://localhost:5173
```

### 3. Sign in

Seeded accounts use the password `password`:

| Role | Email |
| --- | --- |
| Admin | `admin@example.com` |
| Client | `maria@example.com` (see `ClientSeeder` for the rest) |

## Verification

| Task | Command | From |
| --- | --- | --- |
| Backend tests (Pest) | `composer run test` | `backend/` |
| Single test | `php artisan test --compact --filter=testName` | `backend/` |
| Backend assets | `npm run build` | `backend/` |
| Frontend production build | `npm run build` | `frontend/` |

More details: [backend/README.md](backend/README.md) · [frontend/README.md](frontend/README.md)
