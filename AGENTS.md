# Repository Guide

## Boundaries

- This repository contains two independent apps: the Laravel API/backend in `backend/` and the Vue 3/Vite app in `frontend/`.
- There is no root package manager or root test command. Run commands from the app directory they belong to.
- `backend/AGENTS.md` contains the Laravel Boost rules and is authoritative for backend changes.
- Boost-managed skills are tracked in `.agents/skills/backend/`, where agents discover them. Treat it as generated output.
- On every `composer update`, `php artisan boost:update` regenerates `backend/.agents/skills/` (it exists only transiently during the update), `backend/scripts/sync-skills.php` mirrors it into `.agents/skills/backend/`, then deletes `backend/.agents/`. Do not hand-edit either copy; updates overwrite them.
- To add or override a skill durably, create it under `backend/.ai/skills/{name}/SKILL.md`; `boost:update` publishes custom skills alongside its built-ins, and a same-name custom skill overrides the built-in one.
- The backend's `package.json` builds Laravel assets from `backend/resources/`; it is not the Vue frontend's package manifest.
- Backend HTTP wiring is in `backend/bootstrap/app.php` and `backend/routes/`; the frontend entrypoint is `frontend/src/main.js`, with routes in `frontend/src/router/index.js`.

## Setup And Development

- From `backend/`, `composer run setup` installs PHP and backend JavaScript dependencies, creates `.env` if needed, generates the app key, migrates, and builds backend assets.
- From `frontend/`, run `npm install` once. The frontend requires Node `^22.18.0` or `>=24.12.0`.
- If PowerShell blocks `npm.ps1` with an execution-policy error, use the equivalent `npm.cmd` command.
- From `backend/`, `composer run dev` starts the Laravel server, queue listener, and backend Vite watcher. It does not start the standalone Vue app.
- Run `npm run dev` separately from `frontend/` for the Vue development server.

## Verification

- Backend tests: from `backend/`, run `composer run test`, or focus with `php artisan test --compact --filter=testName`.
- Backend asset build: from `backend/`, run `npm run build`.
- Frontend production build: from `frontend/`, run `npm run build`.
- Backend tests use Pest and are configured for an in-memory SQLite database in `backend/phpunit.xml`; feature tests use the Laravel test case.
