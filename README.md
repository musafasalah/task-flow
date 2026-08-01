# TaskFlow — Task Management API

A RESTful API for a Task Management System built with **Laravel 13** and **Laravel Sanctum**. Users manage their own projects, each project contains tasks, and a dashboard endpoint aggregates statistics.

---

## Table of Contents
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Requirements](#requirements)
- [Installation](#installation)
- [Environment Setup](#environment-setup)
- [Database & Sample Data](#database--sample-data)
- [Running Tests](#running-tests)
- [API Documentation](#api-documentation)
- [Overdue Task Notifications (Queued Job)](#overdue-task-notifications-queued-job)
- [Postman Collection](#postman-collection)
- [OpenAPI / Swagger](#openapi--swagger)
- [Docker](#docker)

---

## Features
- **Authentication** via Laravel Sanctum (register, login, logout, current user)
- **Projects** — full CRUD, scoped to the owner, with status (`active`, `completed`, `archived`)
- **Tasks** — full CRUD nested under projects, with priority (`low`, `medium`, `high`), status (`todo`, `in_progress`, `done`) and due dates
- **Filtering & search** — tasks by status, priority, and title search
- **Dashboard** — total/active projects, total/completed/pending/overdue tasks
- **Authorization** — policies enforce per-user ownership (403 on others' resources)
- **Soft deletes**, **pagination**, **Eloquent API Resources**, **Form Request validation**
- **Consistent JSON error handling** with proper HTTP status codes
- **Queued notification** when a task becomes overdue
- **43+ feature tests** (Pest)

## Tech Stack
| | |
|---|---|
| Language | PHP 8.4 |
| Framework | Laravel 13 |
| Auth | Laravel Sanctum (token-based) |
| Database | MySQL (SQLite in-memory for tests) |
| Testing | Pest v4 |
| Formatting | Laravel Pint |

## Architecture
Clean, layered architecture applied across every module:

```
Controller  ──►  Service          ──►  Repository (interface)  ──►  Eloquent Model
(thin, HTTP)     (business logic)       (data access)                (persistence)
```

- **Controllers** are thin — validate (Form Requests), authorize (Policies), delegate to services, return Resources.
- **Services** hold business logic and orchestration (`AuthService`, `ProjectService`, `TaskService`, `DashboardService`).
- **Repositories** encapsulate all data access behind interfaces (`App\Repositories\Contracts\*`), bound to Eloquent implementations in `App\Providers\RepositoryServiceProvider`.
- **Policies** (`ProjectPolicy`, `TaskPolicy`) enforce ownership.

```
app/
├─ Enums/                     ProjectStatus, TaskPriority, TaskStatus
├─ Http/
│  ├─ Controllers/Api/        Auth, Project, Task, Dashboard
│  ├─ Requests/               Form Request validation
│  └─ Resources/              API Resources
├─ Models/                    User, Project, Task
├─ Notifications/             TaskOverdueNotification (queued)
├─ Policies/                  ProjectPolicy, TaskPolicy
├─ Repositories/
│  ├─ Contracts/              Interfaces
│  └─ Eloquent/               Implementations
├─ Services/                  Business logic
└─ Console/Commands/          tasks:notify-overdue
```

---

## Requirements
- PHP 8.4+
- Composer
- MySQL 8+ (or MariaDB)
- Node is **not** required (API only)

## Installation

```bash
# 1. Clone
git clone https://github.com/musafasalah/task-flow.git
cd task-flow

# 2. Install PHP dependencies
composer install

# 3. Environment
cp .env.example .env
php artisan key:generate

# 4. Configure the database in .env (see below), then run migrations + seeders
php artisan migrate --seed

# 5. Serve (any one)
php artisan serve          # http://127.0.0.1:8000
# or use Laravel Herd/Valet -> https://task-flow.test
```

## Environment Setup
Set your database credentials in `.env`:

```dotenv
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=taskflow
DB_USERNAME=root
DB_PASSWORD=

# Queue for the overdue-notification job
QUEUE_CONNECTION=database

# Mail driver — "log" writes emails to storage/logs/laravel.log (no SMTP needed)
MAIL_MAILER=log
```

Create the database first (e.g. `CREATE DATABASE taskflow;`).

## Database & Sample Data
```bash
php artisan migrate --seed
```
The seeder creates:
- **5 users**, each with **3 projects**, each project with **7 tasks** (incl. overdue ones)
- A known demo account:

| Email | Password |
|-------|----------|
| `test@example.com` | `password` |

> All seeded users share the password **`password`**.

## Running Tests
```bash
php artisan test
```
Tests run against an in-memory SQLite database (configured in `phpunit.xml`) — they never touch your MySQL data.

---

## API Documentation

**Base URL:** `{APP_URL}/api` — whatever host you run the app on. Common options:
- `http://localhost:8000/api` — `php artisan serve` or Docker
- `https://task-flow.test/api` — Laravel Herd/Valet

Set this once as the `base_url` variable in Postman (or the `servers` entry in the OpenAPI spec).

All requests should send:
```
Accept: application/json
```
Protected endpoints require:
```
Authorization: Bearer <token>
```

### Authentication
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/register` | – | Register, returns user + token |
| POST | `/api/login` | – | Login, returns user + token |
| POST | `/api/logout` | ✔ | Revoke the current token |
| GET | `/api/user` | ✔ | Current authenticated user |

**Register / Login body**
```json
{ "name": "Jane Doe", "email": "jane@example.com", "password": "password123", "password_confirmation": "password123" }
```
**Response (201/200)**
```json
{ "user": { "id": 1, "name": "Jane Doe", "email": "jane@example.com" }, "token": "1|xxxx" }
```

### Projects
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/projects?per_page=15` | List the user's projects (paginated) |
| POST | `/api/projects` | Create |
| GET | `/api/projects/{project}` | Show |
| PUT/PATCH | `/api/projects/{project}` | Update |
| DELETE | `/api/projects/{project}` | Soft delete (204) |

**Body:** `{ "name": "...", "description": "...", "status": "active|completed|archived" }`

### Tasks (nested under a project)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/projects/{project}/tasks` | List + filter + search |
| POST | `/api/projects/{project}/tasks` | Create |
| GET | `/api/projects/{project}/tasks/{task}` | Show |
| PUT/PATCH | `/api/projects/{project}/tasks/{task}` | Update |
| DELETE | `/api/projects/{project}/tasks/{task}` | Soft delete (204) |

**Filters (query params):** `status=todo|in_progress|done`, `priority=low|medium|high`, `search=<title>`, `per_page=<1..100>`

**Body:** `{ "title": "...", "description": "...", "priority": "high", "status": "todo", "due_date": "2026-12-31" }`

### Dashboard
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/dashboard` | Aggregated statistics for the user |

**Response**
```json
{ "data": { "total_projects": 3, "active_projects": 2, "total_tasks": 21, "completed_tasks": 6, "pending_tasks": 15, "overdue_tasks": 4 } }
```

### HTTP Status Codes
| Code | Meaning |
|------|---------|
| 200 | OK |
| 201 | Created |
| 204 | No Content (delete) |
| 401 | Unauthenticated (missing/invalid token) |
| 403 | Forbidden (authenticated but not the owner) |
| 404 | Resource not found |
| 422 | Validation error (`{ "message", "errors": {...} }`) |

---

## Overdue Task Notifications (Queued Job)
A queued notification (`TaskOverdueNotification implements ShouldQueue`) is dispatched to a task's owner when the task becomes overdue (past `due_date`, not `done`, not yet notified).

```bash
# 1. Ensure an overdue task exists (past due_date, status != done)
# 2. Queue notifications
php artisan tasks:notify-overdue

# 3. Process the queue (QUEUE_CONNECTION=database)
php artisan queue:work --stop-when-empty
```
Delivery: **mail** (written to `storage/logs/laravel.log` with the `log` mailer) and **database** (the `notifications` table). Running the command again won't re-notify the same task; the flag resets if the task is later un-overdued.

## Postman Collection
Import **`docs/TaskFlow.postman_collection.json`** into Postman.
- Set the `base_url` collection variable to match your host — it defaults to `http://localhost:8000` (works for `php artisan serve` and Docker); change it to `https://task-flow.test` for Herd, or any other URL.
- Run **Auth → Register** or **Login** first — the `token` is captured automatically and applied to all protected requests. Created project/task IDs are captured too.

## OpenAPI / Swagger
A complete OpenAPI 3.0 spec is provided at **`docs/openapi.yaml`**. View it by:
- Pasting the contents into <https://editor.swagger.io>, or
- Opening it with any Swagger UI / Redoc viewer.

## Docker
Run the full stack (app + MySQL) with Docker:

```bash
docker compose up -d --build
docker compose exec app php artisan migrate --seed
```
The API is then available at `http://localhost:8000`. See `docker-compose.yml` and `Dockerfile`.
