# Planify API

A multi-tenant SaaS project management API built with Laravel 12, featuring team collaboration, role-based access control, and Stripe subscription billing.

---

## Tech Stack

- **Backend:** PHP 8.3, Laravel 12
- **Database:** PostgreSQL
- **Authentication:** Laravel Sanctum (API Tokens)
- **Payments:** Stripe
- **Admin Panel:** Filament (upcoming)
- **Testing:** Pest (upcoming)

---

## Features

### ✅ Completed
- Multi-tenant architecture (tenant_id isolation + Global Scopes)
- Authentication (Register, Login, Logout) with Sanctum
- Tenant Middleware (auto-resolves tenant from authenticated user)
- Role-based access control (Owner, Admin, Member) via Laravel Policies
- Plan limits enforcement (max projects, max members per plan)
- Projects CRUD with tenant isolation
- Global API exception handling (ValidationException, AuthenticationException, BusinessException, etc.)
- Unified API response format via `ApiResponse` trait
- `BelongsToTenant` trait for automatic tenant scoping
- `BusinessException` for domain-level errors
- Role Enum (`owner`, `admin`, `member`)

### ⬜ In Progress / Upcoming
- Tasks CRUD
- Invitations System (invite members via email)
- Events & Listeners (activity logging)
- Queues & Jobs (email notifications)
- Stripe Subscriptions (plan upgrades/downgrades)
- Filament Admin Panel (super admin dashboard)
- Pest Tests (Feature + Unit)
- Email Verification & Password Reset

---

## Project Structure

```
planify-api/
├── app/
│   ├── Enum/
│   │   └── Role.php                    # owner, admin, member
│   ├── Exceptions/
│   │   ├── ApiHandler.php              # Global exception handler
│   │   └── BusinessException.php       # Domain-level exceptions
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── AuthController.php
│   │   │   └── ProjectController.php
│   │   ├── Middleware/
│   │   │   └── TenantMiddleware.php
│   │   ├── Requests/
│   │   │   ├── Auth/
│   │   │   │   ├── RegisterRequest.php
│   │   │   │   └── LoginRequest.php
│   │   │   └── Project/
│   │   │       ├── StoreProjectRequest.php
│   │   │       └── UpdateProjectRequest.php
│   │   └── Resources/
│   │       ├── AuthResource.php
│   │       └── ProjectResource.php
│   ├── Models/
│   │   ├── ActivityLog.php
│   │   ├── Invitation.php
│   │   ├── Plan.php
│   │   ├── Project.php
│   │   ├── Task.php
│   │   ├── Tenant.php
│   │   └── User.php
│   ├── Policies/
│   │   └── ProjectPolicy.php
│   ├── Services/
│   │   ├── AuthService.php
│   │   └── ProjectService.php
│   └── Traits/
│       ├── ApiResponse.php             # Unified JSON responses
│       └── BelongsToTenant.php        # Auto tenant scoping
├── database/
│   ├── migrations/
│   │   ├── create_plans_table
│   │   ├── create_tenants_table
│   │   ├── add_tenant_id_to_users_table
│   │   ├── create_projects_table
│   │   ├── create_tasks_table
│   │   ├── create_invitations_table
│   │   └── create_activity_logs_table
│   └── seeders/
│       └── PlanSeeder.php
└── routes/
    └── api.php
```

---

## Database Schema

### plans
| Column | Type | Notes |
|--------|------|-------|
| id | UUID | Primary key |
| name | string | free, basic, pro |
| price | decimal(8,2) | 0, 9, 29 |
| billing_cycle | string | monthly, yearly |
| max_projects | integer | null = unlimited |
| max_members | integer | null = unlimited |

### tenants
| Column | Type | Notes |
|--------|------|-------|
| id | UUID | Primary key |
| name | string | Company name |
| domain | string | Unique domain |
| plan_id | UUID | FK → plans |
| status | string | active, suspended |
| trial_ends_at | timestamp | nullable |

### users
| Column | Type | Notes |
|--------|------|-------|
| id | UUID | Primary key |
| tenant_id | UUID | FK → tenants |
| name | string | |
| email | string | Unique |
| password | string | Hashed |
| role | string | owner, admin, member |

### projects
| Column | Type | Notes |
|--------|------|-------|
| id | UUID | Primary key |
| tenant_id | UUID | FK → tenants |
| created_by | UUID | FK → users |
| name | string | |
| description | text | nullable |
| status | string | active, archived |

### tasks
| Column | Type | Notes |
|--------|------|-------|
| id | UUID | Primary key |
| tenant_id | UUID | FK → tenants |
| project_id | UUID | FK → projects |
| assigned_to | UUID | FK → users, nullable |
| created_by | UUID | FK → users, nullable |
| title | string | |
| description | text | nullable |
| status | string | todo, in_progress, done |
| priority | string | low, medium, high |
| due_date | timestamp | nullable |

### invitations
| Column | Type | Notes |
|--------|------|-------|
| id | UUID | Primary key |
| tenant_id | UUID | FK → tenants |
| invited_by | UUID | FK → users |
| email | string | |
| role | string | admin, member |
| token | string | Unique |
| accepted_at | timestamp | nullable |
| expires_at | timestamp | |

### activity_logs
| Column | Type | Notes |
|--------|------|-------|
| id | UUID | Primary key |
| tenant_id | UUID | FK → tenants |
| user_id | UUID | FK → users, nullable |
| action | string | created, updated, deleted, assigned |
| model_type | string | project, task |
| model_id | UUID | |
| changes | json | nullable |

---

## API Endpoints

### Auth
```
POST /api/auth/register     # Register new company (creates tenant + owner)
POST /api/auth/login        # Login
POST /api/auth/logout       # Logout (requires auth)
```

### Projects (requires auth + tenant middleware)
```
GET    /api/projects        # List all projects
POST   /api/projects        # Create project (owner/admin only)
GET    /api/projects/{id}   # Get project
PUT    /api/projects/{id}   # Update project (owner/admin only)
DELETE /api/projects/{id}   # Delete project (owner/admin only)
```

### Tasks (upcoming)
```
GET    /api/projects/{id}/tasks
POST   /api/projects/{id}/tasks
GET    /api/projects/{id}/tasks/{taskId}
PUT    /api/projects/{id}/tasks/{taskId}
DELETE /api/projects/{id}/tasks/{taskId}
```

### Invitations (upcoming)
```
POST /api/invitations           # Send invitation
GET  /api/invitations/accept/{token}  # Accept invitation
```

### Subscriptions (upcoming)
```
GET  /api/plans                 # List plans
POST /api/subscriptions         # Subscribe to plan
POST /api/webhooks/stripe       # Stripe webhook
```

---

## API Response Format

### Success
```json
{
    "success": true,
    "message": "Operation successful",
    "data": {}
}
```

### Error
```json
{
    "success": false,
    "message": "Error message",
    "errors": {}
}
```

---

## Multi-Tenancy Architecture

```
Register → Creates Tenant + Owner User
         → Assigns Free Plan automatically

Every Request:
Auth:sanctum → Verifies token
TenantMiddleware → Resolves tenant_id from user
                 → Stores in app container

BelongsToTenant Trait:
→ Auto-applies TenantScope (filters by tenant_id)
→ Auto-assigns tenant_id on create
```

---

## Role & Permission System

| Action | Owner | Admin | Member |
|--------|-------|-------|--------|
| Create Project | ✅ | ✅ | ❌ |
| Update Project | ✅ | ✅ | ❌ |
| Delete Project | ✅ | ✅ | ❌ |
| View Projects | ✅ | ✅ | ✅ |
| Create Task | ✅ | ✅ | ❌ |
| Update Task | ✅ | ✅ | ❌ |
| Assign Task | ✅ | ✅ | ❌ |
| View Tasks | ✅ | ✅ | ✅ |
| Invite Members | ✅ | ✅ | ❌ |

---

## Plans

| Plan | Price | Max Projects | Max Members |
|------|-------|-------------|-------------|
| Free | $0/month | 1 | 5 |
| Basic | $9/month | 3 | 5 |
| Pro | $29/month | Unlimited | Unlimited |

---

## Installation

```bash
# Clone the repo
git clone https://github.com/fathallah7/planify-api.git
cd planify-api

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database in .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=planify
DB_USERNAME=postgres
DB_PASSWORD=yourpassword

# Run migrations
php artisan migrate

# Seed plans
php artisan db:seed --class=PlanSeeder

# Install Sanctum
php artisan install:api

# Start server
php artisan serve
```

---

## Environment Variables

```env
APP_NAME=Planify
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=planify
DB_USERNAME=postgres
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost

STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
```

---

## Key Concepts Used

- **Multi-tenancy** via `tenant_id` column + Global Scopes
- **BelongsToTenant** trait for automatic scoping
- **Laravel Policies** for role-based access
- **Form Requests** for validation
- **API Resources** for response transformation
- **Service Layer** for business logic
- **BusinessException** for domain errors
- **Global Exception Handler** for unified error responses
- **Role Enum** for type-safe roles
