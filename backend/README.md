# Appointment Management — Backend

Laravel 13 REST API (PHP ≥ 8.3) serving the Vue SPA in [`../frontend`](../frontend). Uses Laravel Sanctum with **cookie-based SPA authentication** and role-based access for admins and clients.

## Stack

- Laravel `^13.17`, PHP `^8.3`
- Laravel Sanctum (stateful SPA auth)
- MySQL (default), database queue, database session/cache
- Pest for testing (in-memory SQLite via `phpunit.xml`)
- Pint for code style
- Laravel Boost for AI-assisted development
- Vite builds assets from `resources/` (this package.json is not the frontend's)

## Getting started

```bash
composer run setup
```

Installs PHP + JS dependencies, creates `.env` from `.env.example`, generates the app key, migrates, and builds backend assets.

### Environment notes (`backend/.env`)

| Variable | Purpose |
| --- | --- |
| `DB_*` | MySQL connection (`appointment_management` database by default) |
| `APP_URL` | API origin, default `http://localhost:8000` |
| `CORS_ALLOWED_ORIGINS` | Allowed SPA origins (defaults to Vite dev server on port 5173) |
| `SANCTUM_STATEFUL_DOMAINS` | Domains allowed to make authenticated stateful requests |

### Seeding

```bash
php artisan migrate:fresh --seed
```

Runs `AdminSeeder` (admin account), `ClientSeeder`, `ServiceSeeder`, and `AppointmentSeeder`. The seeded admin login is `admin@example.com` / `password`.

## Development

```bash
composer run dev
```

Starts three processes concurrently: `php artisan serve` (API at http://localhost:8000), a queue listener, and the Vite asset watcher.

## Testing & style

```bash
composer run test                                  # full suite (Pest)
php artisan test --compact --filter=testName       # single test
vendor/bin/pint --dirty                            # format changed files
```

Feature tests cover authentication, access boundaries between roles, appointment workflows, domain data, and resource operations.

## Domain model

| Model | Notes |
| --- | --- |
| `User` | Authenticatable; `is_admin` boolean distinguishes admin from client accounts. Optional `client` profile relation. |
| `Client` | Client profile linked to a `user_id`; `active` flag; has many appointments. |
| `Service` | Bookable service offered by the business; has many appointments. |
| `Appointment` | Belongs to a client + service; date/time range; casts to enums below. |

Enums:

- `AppointmentStatus`: `Requested`, `Confirmed`, `Completed`, `Cancelled`
- `AppointmentPriority`: `Low`, `Medium`, `High`

Lifecycle: a client creates a booking as `Requested`; admins confirm it; it is then completed or cancelled.

## API surface (`routes/api.php`)

Base URL: `{APP_URL}/api`

### Public

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `/login` | Log in (sets Sanctum cookie) |
| POST | `/client/register` | Register a new client account |
| GET | `/services` | List active services |
| GET | `/services/categories` | List service categories |
| GET | `/services/{service}` | Get a service by ID |
| GET | `/user` | Current user (`null` when unauthenticated) |

### Authenticated (`auth:sanctum`)

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `/logout` | Revoke session |

### Admin only (`admin` middleware)

| Method | Endpoint | Description |
| --- | --- | --- |
| GET/POST/PUT/PATCH/DELETE | `/appointments` | Full CRUD (`apiResource`) |
| POST | `/appointments/{id}/confirm` · `/complete` · `/cancel` | Status transitions |
| GET/POST/PUT/PATCH | `/clients` | CRUD except delete |
| PATCH | `/clients/{id}/deactivate` · `/activate` | Toggle active flag |
| GET/POST/PUT/PATCH | `/management/services` | Service management CRUD except delete |
| GET | `/management/services/categories` | Service categories (admin) |
| PATCH | `/services/{id}/deactivate` · `/activate` | Toggle service active flag |
| GET | `/dashboard/stats` | Dashboard statistics |

### Client only (`client` middleware)

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `/booking-requests` | Request an appointment |
| GET | `/client/dashboard` | Client dashboard data |
| GET | `/client/appointments` | Own appointments |
| PATCH | `/client/appointments/{id}/cancel` | Cancel own appointment |

## Architecture

- **Form Requests** — input validation via dedicated request classes (e.g. `LoginRequest`, `StoreAppointmentRequest`, `BookingRequest`).
- **Policies** — authorization rules for appointments, clients, and services.
- **AppointmentWorkflowService** — encapsulates appointment status transitions (`confirm`, `complete`, `cancel`).
- **Rate limiting** — five named limiters (`public`, `authenticated`, `login`, `register`, `booking`) configured in `AppServiceProvider`.
