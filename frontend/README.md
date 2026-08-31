# Appointment Management — Frontend

Vue 3 single-page application (Vite) for the appointment management system, talking to the Laravel API in [`../backend`](../backend).

## Stack

- Vue 3 (`<script setup>`) + Vite
- Nuxt UI v4 + Tailwind CSS 4 for components/styling
- Pinia for state management
- Vue Router (history mode) with auth/role guards
- Axios with cookie-based (Sanctum) authentication
- vue3-apexcharts for charting
- @internationalized/date for date handling

## Requirements

- Node.js `^22.18.0 || >=24.12.0`
- On Windows PowerShell, use `npm.cmd` instead of `npm` if execution policy blocks `npm.ps1`.

## Getting started

```bash
npm install
cp .env.example .env   # sets VITE_API_BASE_URL=http://localhost:8000/api
npm run dev            # http://localhost:5173
```

The backend must be running at the URL in `VITE_API_BASE_URL`. Make sure `CORS_ALLOWED_ORIGINS` and `SANCTUM_STATEFUL_DOMAINS` in `backend/.env` include `http://localhost:5173` (the defaults do).

## Scripts

| Command | Description |
| --- | --- |
| `npm run dev` | Start Vite dev server with Vue DevTools |
| `npm run build` | Production build to `dist/` |
| `npm run preview` | Preview the production build |

## Project structure

```
src/
├── App.vue                # Root component: role-based layout switching
├── main.js                # App entry: Pinia, router, Nuxt UI plugin, ApexCharts
├── router/index.js        # Routes + navigation guards
├── stores/                # Pinia stores: auth, appointments, clients, services, dashboard
├── services/              # API layer: axios instance + per-resource services + error handler
├── composables/           # Shared logic: useDebouncedWatch, useUrlState
├── layouts/               # AdminLayout / ClientLayout shells
├── pages/                 # Route view components
├── components/            # Shared UI (forms, tables, badges, modals, etc.)
│   └── dashboard/         # Dashboard-specific components
└── assets/css/            # Tailwind entry point
```

## Authentication & roles

- Login/register set a Sanctum cookie; requests use `withCredentials` + `XSRF` token (`src/services/api.js`).
- The auth store initializes the session on first navigation; a `401` response dispatches an `auth:expired` event to force logout.
- Router guards enforce `requiresAuth`, `requiresAdmin`, `requiresClient`, and `guestOnly` route meta.

## Routes

| Path | Access | Page |
| --- | --- | --- |
| `/login`, `/register` | guests | Auth pages |
| `/marketplace` | public | Browse active services |
| `/marketplace/services/:id` | public | Public service details |
| `/` | authenticated | Home / role workspace |
| `/appointments`, `/appointments/:id[/edit]`, `/appointments/create` | admin | Appointment management |
| `/clients`, `/clients/:id[/edit]`, `/clients/create` | admin | Client management |
| `/services`, `/services/:id[/edit]`, `/services/create` | admin | Service management |
| `/book` | client | Redirects to marketplace |
| `/client/marketplace` | client | Browse services (authenticated) |
| `/client/services/:id` | client | Book an appointment |
| `/client/appointments` | client | My appointments |
