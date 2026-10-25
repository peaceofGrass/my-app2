# CLAUDE.md

Blog project in its early stage: Laravel 13 + Inertia.js 2 + Vue 3 (Breeze scaffold, SSR enabled), running in Laravel Sail on **rootless Podman** (WSL2).

## Golden rule: run everything through Sail

PHP, Composer, Node, and npm commands must run **inside the container** via `./vendor/bin/sail` (the user has `alias sail='./vendor/bin/sail'`; in non-interactive shells use the full path). Do not run `php`, `composer`, or `npm` directly on the host.

## Environment

- `docker` is a symlink to `podman`; `docker compose` delegates to `podman-compose`. Sail uses `compose.yaml`.
- Services (`compose.yaml`): `laravel.test` (Sail PHP 8.5 runtime, app on port 80, Vite on 5173), `mariadb` (MariaDB 11), `redis`.
- There is **no nginx** in this setup: the Sail container serves the app with `php artisan serve` under supervisord. If nginx is added later, update this section.
- `.env`: `DB_CONNECTION=mariadb`, `DB_HOST=mariadb`. Session, cache, and queue all use the `database` driver. Mail goes to `log`.
- Tests use a separate `testing` database, which Sail's MariaDB init script creates (see `phpunit.xml`).

## Commands

```bash
sail up -d                 # start containers
sail down                  # stop containers
sail ps                    # status

sail npm run dev           # Vite dev server (HMR, http://localhost:5173)
sail npm run build         # client build + SSR build (vite build && vite build --ssr)
sail npm run lint          # ESLint --fix on resources/js (.js, .vue)
sail npm install           # .npmrc sets ignore-scripts=true; install scripts do not run

sail artisan migrate
sail artisan make:model Post -mfc   # always prefer artisan generators
sail artisan tinker
sail composer require <pkg>

sail test                              # all tests (Pest)
sail test --filter=ProfileTest         # one test file / name
sail pest tests/Feature/ProfileTest.php
sail bin pint                          # PHP code style (Laravel preset)
sail bin pint --dirty                  # only changed files
```

Before finishing a change, run `sail bin pint --dirty`, `sail npm run lint`, and `sail test`.

## Architecture

- **Routing**: `routes/web.php` (pages) and `routes/auth.php` (Breeze auth). Routes are named; the frontend uses them through Ziggy's `route()` helper (`ZiggyVue`).
- **Pages**: controllers return `Inertia::render('Dir/Name', [...props])`, which maps to `resources/js/Pages/Dir/Name.vue`. Shared props are set in `app/Http/Middleware/HandleInertiaRequests.php`.
- **Frontend**: `resources/js/app.js` (client) and `resources/js/ssr.js` (SSR entry; keep the two in sync when changing app setup). Reusable UI is in `resources/js/Components/` and layouts in `resources/js/Layouts/` (`AuthenticatedLayout`, `GuestLayout`). The `@/` alias points to `resources/js/`.
- **Validation**: use Form Request classes (`app/Http/Requests/`), following the Breeze pattern.
- **Styling**: Tailwind CSS **v3** via PostCSS (`postcss.config.js`, `tailwind.config.js`, `@tailwind` directives in `resources/css/app.css`). `@tailwindcss/vite` (v4) is installed but **not** used. Don't mix v4 syntax (`@import "tailwindcss"`) in.
- **Tests**: Pest 4. Feature tests use `RefreshDatabase` automatically (`tests/Pest.php`). Write Pest-style `test()`/`it()` tests, not PHPUnit classes. Add a feature test for every new route or controller action.

## Code style

- `.editorconfig`: 4-space indent, LF, final newline (YAML uses 2 spaces, except `compose.yaml`, which uses 4).
- PHP: Laravel Pint defaults.
- JS/Vue: Prettier (single quotes, organize-imports, and tailwind class sorting plugins) plus ESLint `vue3-essential`. Use `<script setup>` in Vue SFCs, as the Breeze components do.

## Git

- Remote: `origin` → `github.com/peaceofGrass/my-app2`. Default branch is `main`; create a feature branch before committing.
- Commit messages follow **Conventional Commits**: `type: subject` (`feat`, `fix`, `chore`, `refactor`, `test`, `docs`, `style`). The subject may be in Korean, e.g. `feat: 게시글 작성 기능 추가`.
- Never commit `.env`, `public/build`, `public/hot`, `bootstrap/ssr`, `node_modules`, or `vendor` (all are already in `.gitignore`).
