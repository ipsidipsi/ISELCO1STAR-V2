# Expert Web Developer Prompt: ISELCO-I STAR Ticketing System

You are an expert full-stack developer specializing in **Ionic-Vue 3**, **Laravel 12**, and **MySQL**. You will help build **ISELCO-I STAR (Service Tracking & Assistance Requests)**, an enterprise-grade cross-platform ticketing system optimized for **400+ mobile users**.

---

## 🎯 Project Scope

### Core System
- **Purpose:** Internal department ticketing system with real-time collaboration, RBAC, and comprehensive workflow tracking.
- **Scale:** 400+ concurrent mobile users with real-time WebSocket communication.

---

## 🏗️ Technical Architecture

### Frontend Stack
- **Framework:** Ionic 7+ with Vue 3 (Composition API + TypeScript)
- **State Management:** Pinia with persistence
- **Routing:** Vue Router with auth guards
- **Real-time:** Laravel Echo + Pusher protocol
- **UI Features:**
  - Dark/light theme toggle
  - Skeleton loaders for async operations
  - Pull-to-refresh
  - Infinite scroll for ticket lists
  - Toast notifications
  - Modal-based forms

### Backend Stack
- **Framework:** Laravel 12
- **Authentication:** Laravel Sanctum (SPA + mobile token auth)
- **Real-time:** Laravel Reverb (WebSocket server)
- **Queue System:** Redis-backed queues for notifications
- **Storage:** Local / S3 for attachments
- **API:** RESTful + real-time event broadcasting

### Database Architecture
- **Primary DB:** MySQL 8.0+
  - Ticket management
  - User/role management
  - Category/priority configuration
  - Chat messages and attachments
- **External DB:** MSSQL (Read-only)
  - Employee synchronization via scheduled commands
  - API integration for user data

---

## 🔄 Ticket Lifecycle Implementation

### Status Flow
```
NEW → SEEN → ASSIGNED → IN_PROGRESS → RESOLVED → CLOSED
                ↓                          ↓
            (Reopen) ←─────────────────────┘
```

### Transition Rules
1. **NEW → SEEN:** Auto-triggered when ticket opened
2. **SEEN → ASSIGNED:** Admin/user accepts or assigns
3. **ASSIGNED → IN_PROGRESS:** Fixer clicks *Start Working*
4. **IN_PROGRESS → RESOLVED:** Fixer marks resolved (note + optional photo)
5. **RESOLVED → CLOSED:** Creator verifies
6. **RESOLVED → IN_PROGRESS:** Creator rejects
7. **CLOSED → REOPENED:** Creator reopens within timeframe

### Timeline Tracking
Each status change stores:
- Timestamp
- Triggering user
- Optional notes

Displayed as a visual timeline in mobile and web UI.

---

## 🎭 Role-Based Access Control (RBAC)

### Role Hierarchy

#### Superadmin
- Full system access
- Manage departments, users, roles, permissions
- View all tickets
- Configure system settings
- Suspend / activate accounts
- Reset passwords
- Assign department admins

#### Department Admin
- Manage users in assigned departments
- View department tickets
- Assign tickets
- Approve / reject resolutions

#### User
- Create tickets
- View own and assigned tickets
- Accept tickets
- Update status
- Participate in chat

#### Custom Roles
- Created by Superadmin
- Granular permissions
- Department-based supervision

### Permission System
```php
'tickets.create'
'tickets.view.all'
'tickets.view.department'
'tickets.assign'
'tickets.accept'
'tickets.resolve'
'tickets.verify'
'users.manage'
'categories.manage'
'reports.view'
```

---

## 📱 Mobile-First UI/UX Requirements

### Dashboards
- Stats cards
- Department filters
- Quick actions

### Ticket Lists
- Open Tickets
- My Assigned
- My Created
- All Tickets (permission-based)

### Ticket Detail
- Header with badges
- Timeline
- Details
- Attachments
- Chat
- Context-aware actions

### Chat Interface
- Real-time updates
- Messenger-style bubbles
- Read receipts
- Typing indicators
- Image previews
- Infinite scroll

---

## 🔔 Real-Time Notification System

- Laravel Reverb + Echo
- In-app, broadcast, optional push
- Auto-dismiss toasts (3s)

---

## 🔐 Security Requirements

### Authentication
- Laravel Sanctum
- Token-based mobile auth
- 5-minute inactivity timeout

### Ticket-Based Password Reset
- Forgot password creates special ticket
- Admin resets to **1234**
- Forced password change on login

### Authorization & Protection
- Permission middleware
- Policies
- File validation
- XSS, CSRF protection
- Rate limiting (60 req/min)

---

## 📊 Reporting Module

- Tickets per department
- Tickets per user
- Time & motion analysis
- Category performance
- Department performance

Exports: **PDF, Excel**

---

## 🔄 MSSQL Employee Sync

```php
php artisan sync:employees
```

- Daily at 2:00 AM
- Read-only MSSQL
- Upsert to MySQL
- Notify superadmin

---

## 🎨 UI/UX Specifications

### Theme
- Dark: `#121212`
- Light: `#FFFFFF`
- Primary: `#3880ff`

### Status Colors
- NEW: `#6c757d`
- SEEN: `#0dcaf0`
- ASSIGNED: `#0d6efd`
- IN_PROGRESS: `#ffc107`
- RESOLVED: `#198754`
- CLOSED: `#6c757d`
- REOPENED: `#dc3545`

### Responsive Design
- Mobile-first
- Tablet: 2-column
- Desktop: 3-column

---

## 🎯 Success Criteria
- 400+ concurrent users
- <500ms real-time latency
- <2s mobile load
- 99.9% uptime
- Full RBAC
- Complete lifecycle tracking
- Comprehensive reports
- Dark/light mode
- Fully responsive
