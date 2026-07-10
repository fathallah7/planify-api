# Planify API

A production-grade multi-tenant SaaS project management REST API built with Laravel 13 and PostgreSQL. Planify enables organizations to manage projects, tasks, and team collaboration with complete data isolation between tenants, role-based access control, and Stripe-powered subscription billing.

## Table of Contents

- [Overview](#overview)
- [Tech Stack](#tech-stack)
- [Features](#features)
- [Database Schema](#database-schema)
- [API Reference](#api-reference)
- [Role & Permission Matrix](#role--permission-matrix)
- [Subscription Plans](#subscription-plans)
- [Project Structure](#project-structure)
- [Installation](#installation)
- [Environment Variables](#environment-variables)
- [Running the Application](#running-the-application)
- [Queue Worker](#queue-worker)

---

## Overview

Planify is a backend API designed for teams and organizations to manage their projects and tasks in a multi-tenant environment. Each organization operates in complete isolation — no tenant can access another tenant's data. The system enforces plan-based limits, role-based permissions, and processes background jobs asynchronously via Laravel Queues.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.5 |
| Framework | Laravel 13 |
| Database | PostgreSQL |
| Authentication | Laravel Sanctum |
| Authorization | Laravel Policies |
| Background Jobs | Laravel Queues (Database Driver) |
| Real-time Chat | Laravel Reverb (WebSockets) |
| Payments | Stripe |
| Testing | Pest |
| Containerization | Docker |

---

## Features

### Authentication
- Organization registration (creates tenant + owner account atomically)
- Member registration (standalone user account without tenant)
- Token-based authentication via Laravel Sanctum
- Secure logout with token revocation

### Multi-Tenancy
- Complete data isolation per organization
- Automatic tenant resolution from authenticated user
- Global scope enforcement on all tenant-owned resources
- Automatic `tenant_id` assignment on resource creation

### Role-Based Access Control
- Three roles: Owner, Admin, Member
- Laravel Policies enforcing permissions per resource
- Role validation via PHP Enum

### Projects
- Full CRUD operations
- Plan-based project limits enforced at service and database levels
- Tenant-scoped project visibility

### Tasks
- Full CRUD operations scoped to projects
- Task assignment to team members (tenant-validated)
- Status tracking: `todo`, `in_progress`, `done`
- Priority levels: `low`, `medium`, `high`
- Due date support
- Owners and Admins can manage tasks; assigned members can update status

### Invitations
- Email-based team member invitations
- Token-secured invitation links with 7-day expiry
- Automatic re-invitation (replaces pending invitations)
- Role assignment at invitation time
- Prevents inviting users already in the organization

### Background Jobs & Queues
- All emails processed asynchronously via Laravel Queues
- Database-backed queue driver
- Invitation emails dispatched in background
- Task assignment notifications dispatched in background

### Events & Listeners
- `ProjectCreated` event with activity logging
- `TaskCreated` event with activity logging
- `TaskAssigned` event with email notification to assignee
- All listeners implement `ShouldQueue` for background processing

### Activity Logging
- Automatic logging of create, update, delete, and assign actions
- Stores changed fields as JSON for audit trail
- Per-tenant activity history

### Subscription Plans (Stripe)
- Three-tier subscription model: Free, Basic, Pro
- Monthly and yearly billing cycles
- Stripe Webhooks for payment confirmation
- Automatic account suspension on payment failure
- Plan limit enforcement (projects, members)

### Real-time Project Chat (Laravel Reverb)
- WebSocket-based messaging per project
- Real-time message delivery to project members
- Message persistence in database
- Tenant and project-scoped channels

---

## Database Schema

### plans
| Column | Type | Description |
|---|---|---|
| id | UUID | Primary key |
| name | string | free, basic, pro |
| price | decimal(8,2) | Monthly price in USD |
| billing_cycle | string | monthly, yearly |
| max_projects | integer | null = unlimited |
| max_members | integer | null = unlimited |

### tenants
| Column | Type | Description |
|---|---|---|
| id | UUID | Primary key |
| name | string | Organization name |
| domain | string | Unique domain identifier |
| plan_id | UUID | FK to plans |
| status | string | active, suspended |
| trial_ends_at | timestamp | Trial expiry date |

### users
| Column | Type | Description |
|---|---|---|
| id | UUID | Primary key |
| tenant_id | UUID | FK to tenants (nullable for members without org) |
| name | string | Full name |
| email | string | Unique email address |
| password | string | Bcrypt hashed |
| role | string | owner, admin, member |

### projects
| Column | Type | Description |
|---|---|---|
| id | UUID | Primary key |
| tenant_id | UUID | FK to tenants |
| created_by | UUID | FK to users |
| name | string | Project name |
| description | text | Optional description |
| status | string | active, archived |

### tasks
| Column | Type | Description |
|---|---|---|
| id | UUID | Primary key |
| tenant_id | UUID | FK to tenants |
| project_id | UUID | FK to projects |
| assigned_to | UUID | FK to users (nullable) |
| created_by | UUID | FK to users (nullable) |
| title | string | Task title |
| description | text | Optional description |
| status | string | todo, in_progress, done |
| priority | string | low, medium, high |
| due_date | timestamp | Optional due date |

### invitations
| Column | Type | Description |
|---|---|---|
| id | UUID | Primary key |
| tenant_id | UUID | FK to tenants |
| invited_by | UUID | FK to users |
| email | string | Invitee email |
| role | string | admin, member |
| token | string | Unique UUID token |
| accepted_at | timestamp | Null if pending |
| expires_at | timestamp | 7 days from creation |

### activity_logs
| Column | Type | Description |
|---|---|---|
| id | UUID | Primary key |
| tenant_id | UUID | FK to tenants |
| user_id | UUID | FK to users (nullable) |
| action | string | created, updated, deleted, assigned |
| model_type | string | project, task |
| model_id | UUID | Target resource ID |
| changes | json | Changed fields (old/new values) |
| created_at | timestamp | Log timestamp |

### messages (Project Chat)
| Column | Type | Description |
|---|---|---|
| id | UUID | Primary key |
| tenant_id | UUID | FK to tenants |
| project_id | UUID | FK to projects |
| user_id | UUID | FK to users |
| content | text | Message content |
| created_at | timestamp | Sent at |

---

## API Reference

All API endpoints are prefixed with `/api`.

### Authentication

| Method | Endpoint | Description | Auth Required |
|---|---|---|---|
| POST | /auth/register | Register new organization | No |
| POST | /auth/register-member | Register as individual member | No |
| POST | /auth/login | Login | No |
| POST | /auth/logout | Logout | Yes |

#### Register Organization
```
POST /api/auth/register
Content-Type: application/json

{
    "company_name": "Acme Corp",
    "domain": "acme",
    "name": "Abdullah Fathallah",
    "email": "abdullah@acme.com",
    "password": "password",
    "password_confirmation": "password"
}
```

#### Login
```
POST /api/auth/login
Content-Type: application/json

{
    "email": "abdullah@acme.com",
    "password": "password"
}
```

### Projects

All project endpoints require authentication and tenant membership.

| Method | Endpoint | Description | Permission |
|---|---|---|---|
| GET | /projects | List all projects | All roles |
| POST | /projects | Create project | Owner, Admin |
| GET | /projects/{id} | Get project | All roles |
| PUT | /projects/{id} | Update project | Owner, Admin |
| DELETE | /projects/{id} | Delete project | Owner, Admin |

### Tasks

| Method | Endpoint | Description | Permission |
|---|---|---|---|
| GET | /projects/{project}/tasks | List project tasks | All roles |
| POST | /projects/{project}/tasks | Create task | Owner, Admin |
| GET | /projects/{project}/tasks/{task} | Get task | All roles |
| PUT | /projects/{project}/tasks/{task} | Update task | Owner, Admin, Assignee |
| DELETE | /projects/{project}/tasks/{task} | Delete task | Owner, Admin |

### Invitations

| Method | Endpoint | Description | Permission |
|---|---|---|---|
| POST | /invitations | Send invitation | Owner, Admin |
| POST | /invitations/accept/{token} | Accept invitation | Authenticated user |

### Subscriptions (Stripe)

| Method | Endpoint | Description | Auth Required |
|---|---|---|---|
| GET | /plans | List available plans | No |
| POST | /subscriptions | Subscribe to plan | Yes |
| POST | /webhooks/stripe | Stripe webhook handler | No (Stripe signature) |

### Project Chat

| Method | Endpoint | Description | Auth Required |
|---|---|---|---|
| GET | /projects/{project}/messages | Get message history | Yes |
| WebSocket | /app/{channel} | Real-time messaging | Yes |

---

## Role & Permission Matrix

| Action | Owner | Admin | Member |
|---|---|---|---|
| Create Project | Yes | Yes | No |
| Update Project | Yes | Yes | No |
| Delete Project | Yes | Yes | No |
| View Projects | Yes | Yes | Yes |
| Create Task | Yes | Yes | No |
| Update Task | Yes | Yes | No |
| Update Own Assigned Task | Yes | Yes | Yes |
| Delete Task | Yes | Yes | No |
| View Tasks | Yes | Yes | Yes |
| Send Invitation | Yes | Yes | No |
| Accept Invitation | Yes | Yes | Yes |
| Send Chat Message | Yes | Yes | Yes |

---

## Subscription Plans

| Plan | Price | Max Projects | Max Members | Features |
|---|---|---|---|---|
| Free | $0/month | 1 | 5 | Basic project management |
| Basic | $9/month | 3 | 5 | + Email notifications |
| Pro | $29/month | Unlimited | Unlimited | + Priority support, Chat |

---

## Project Structure

```
planify-api/
├── app/
│   ├── Enum/
│   │   └── Role.php
│   ├── Events/
│   │   ├── ProjectCreated.php
│   │   ├── TaskCreated.php
│   │   └── TaskAssigned.php
│   ├── Exceptions/
│   │   ├── ApiHandler.php
│   │   └── BusinessException.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── AuthController.php
│   │   │   ├── InvitationController.php
│   │   │   ├── ProjectController.php
│   │   │   └── TaskController.php
│   │   ├── Middleware/
│   │   │   └── TenantMiddleware.php
│   │   ├── Requests/
│   │   │   ├── Auth/
│   │   │   │   ├── RegisterRequest.php
│   │   │   │   ├── RegisterMemberRequest.php
│   │   │   │   └── LoginRequest.php
│   │   │   ├── Invitation/
│   │   │   │   └── StoreInvitationRequest.php
│   │   │   ├── Project/
│   │   │   │   ├── StoreProjectRequest.php
│   │   │   │   └── UpdateProjectRequest.php
│   │   │   └── Task/
│   │   │       ├── StoreTaskRequest.php
│   │   │       └── UpdateTaskRequest.php
│   │   └── Resources/
│   │       ├── AuthResource.php
│   │       ├── InvitationResource.php
│   │       ├── ProjectResource.php
│   │       └── TaskResource.php
│   ├── Listeners/
│   │   ├── LogActivity.php
│   │   └── SendTaskNotification.php
│   ├── Mail/
│   │   ├── InvitationMail.php
│   │   └── TaskAssignedMail.php
│   ├── Models/
│   │   ├── ActivityLog.php
│   │   ├── Invitation.php
│   │   ├── Plan.php
│   │   ├── Project.php
│   │   ├── Task.php
│   │   ├── Tenant.php
│   │   └── User.php
│   ├── Policies/
│   │   ├── ProjectPolicy.php
│   │   └── TaskPolicy.php
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   ├── Services/
│   │   ├── AuthService.php
│   │   ├── InvitationService.php
│   │   ├── ProjectService.php
│   │   └── TaskService.php
│   └── Traits/
│       ├── ApiResponse.php
│       └── BelongsToTenant.php
├── database/
│   ├── migrations/
│   └── seeders/
│       └── PlanSeeder.php
├── resources/
│   └── views/
│       └── emails/
│           ├── invitation.blade.php
│           └── task-assigned.blade.php
├── routes/
│   └── api.php
└── tests/
    ├── Feature/
    └── Unit/
```

---

## Installation

### Prerequisites

- PHP 8.3+
- Composer
- PostgreSQL
- Node.js (for Reverb)

### Steps

```bash
# Clone the repository
git clone https://github.com/fathallah7/planify-api.git
cd planify-api

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your database in .env
# Run migrations
php artisan migrate

# Seed subscription plans
php artisan db:seed --class=PlanSeeder

# Install Sanctum
php artisan install:api
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

QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@planify.com
MAIL_FROM_NAME=Planify

STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=localhost
REVERB_PORT=8080
```

---

## Running the Application

```bash
# Start the development server
php artisan serve

# Start the queue worker (required for background jobs)
php artisan queue:work

# Start the Reverb WebSocket server (required for chat)
php artisan reverb:start
```

---

## Queue Worker

The queue worker is required for processing background jobs including email delivery and activity logging. In production, use a process manager like Supervisor to keep the worker running.

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

Failed jobs can be retried with:

```bash
php artisan queue:retry all
```
