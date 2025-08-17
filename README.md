# Wochenplan 2.0

## Overview
- Framework: Laravel 12 (PHP 8.3)
- Admin UI: Filament
- Services (via docker-compose):
    - App container: Ubuntu + Nginx + PHP-FPM 8.3 + OpenSSH
    - MariaDB 11
    - Redis 7
- CI/CD: GitHub Actions workflow to build and publish Docker images to Docker Hub

## Project structure (high-level)
- app/ … Laravel application code (models, controllers, Filament resources)
- public/ … web root served by Nginx
- database/ … migrations, seeders
- resources/ … views, assets
- docker/ … container configuration (Nginx, Supervisor, entrypoint, first-run)
- Dockerfile … image recipe (Ubuntu + PHP 8.3 + Nginx + SSH)
- docker-compose.yml … local development stack

## Quick start (Docker)
Prerequisites: Docker and Docker Compose installed.

1) Clone the repository
2) Start the stack:
    - docker compose up -d
3) Open the app:
    - http://localhost:8080
4) SSH access (optional):
    - Set SSH_AUTHORIZED_KEYS_URL in docker-compose to a public keys URL (e.g., https://github.com/PalmarHealer.keys) and uncomment the SSH port mapping.
    - Then connect: ssh app@localhost -p 2222

On the first run, the app container will automatically:
- Create .env from .env.example (if missing)
- composer install
- php artisan key:generate (if missing)
- php artisan migrate:fresh --force
- php artisan db:seed --force
- php artisan storage:link

You can rerun migrations/seeding manually any time from inside the container:
- docker compose exec app php artisan migrate:fresh --force
- docker compose exec app php artisan db:seed --force

## Configuration
The app ships with .env.example (defaults to SQLite). The docker-compose overrides these at runtime to use MariaDB and Redis:
- DB_CONNECTION=mysql
- DB_HOST=db
- DB_PORT=3306
- DB_DATABASE=wochenplan
- DB_USERNAME=wochenplan
- DB_PASSWORD=wochenplan
- REDIS_HOST=redis
- REDIS_PORT=6379

App container environment of interest:
- SSH_AUTHORIZED_KEYS_URL: URL to fetch public keys (e.g., https://github.com/YourUser.keys). If empty/unset, SSH is disabled.
- ADMIN_EMAIL, ADMIN_PASSWORD, DAY_VIEW_DISPLAY_ALL_ABSENCE_NOTES, LUNCH_API_URL, LUNCH_API_KEY, APP_LOCALE, APP_FAKER_LOCALE, AZURE_CLIENT_ID, AZURE_CLIENT_SECRET, AZURE_REDIRECT_URI, AZURE_TENANT_ID, AZURE_PROXY, TRUSTED_PROXIES

Ports (host → container):
- 8080 → 80 (Nginx HTTP)
- 2222 → 22 (SSH; optional, commented out in compose)
  Note: MariaDB and Redis are not exposed to the host; the app communicates with them over the internal Docker network.

## Data persistence
- Application code is not bind-mounted by default in docker-compose.
- Only application logs are persisted via the named volume: wochenplan-logs → /var/www/html/storage/logs.
- Database and cache state are persisted in named volumes: wochenplan-db-data (MariaDB) and wochenplan-redis-data (Redis).
- All named volumes are prefixed with "wochenplan-" to avoid collisions across multiple stacks on the same host.

## Manual (non-Docker) installation
- Clone the repo
- composer install
- cp .env.example .env && php artisan key:generate
- Configure DB in .env (SQLite or MySQL/MariaDB/…)
- php artisan migrate:fresh
- php artisan db:seed
- php artisan serve (or use your local Nginx/Apache + PHP-FPM)

Login with credentials configured in your .env (e.g., ADMIN_EMAIL and ADMIN_PASSWORD).

## Roadmap / TODO
- Dashboard
    - Lesson view
    - Switch sorting by days

## Features for later
- Plugins
    - Laravel PDF
- Students should be able to express interest in offered activities.

## CI/CD (Docker Hub)
This repo includes .github/workflows/docker-publish.yml which builds and pushes the image to Docker Hub as wochenplan.
Required GitHub Environment secrets (set under Repo → Settings → Environments → production → Secrets):
- DOCKERHUB_USERNAME: Your Docker Hub username
- DOCKERHUB_TOKEN: Docker Hub Access Token (create at https://hub.docker.com/settings/security)

The workflow pushes tags: latest (on default branch), git SHA, and git tags (vX.Y.Z).

## Notes
- The container runs Nginx, PHP-FPM, and SSH via Supervisor.
- SSH login user: app (not root). SSH is key-based and disabled unless SSH_AUTHORIZED_KEYS_URL is set. Map port 2222 if you want SSH access.
- For production, add HTTPS at the reverse proxy or extend Nginx config accordingly.
