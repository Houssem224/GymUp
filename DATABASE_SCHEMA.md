# GymUp Database Schema Documentation

## Overview
This document describes the complete database schema for the GymUp gym management system. The database is designed using Doctrine ORM with PostgreSQL as the recommended database engine.

## Entity Relationship Diagram Summary

### Core Entities
- **USER**: Central entity representing all system users (Admin, Receptionist, Coach, Member)
- **PLAN**: Membership plans with pricing and access scope
- **SUBSCRIPTION**: Links users to plans with time-based validity
- **CLASS_SESSION**: Scheduled classes with coach, room, and capacity
- **BOOKING**: User bookings for class sessions
- **PAYMENT**: Payment records linked to subscriptions
- **SCHEDULE**: Member weekly attendance schedules
- **CHECK_IN**: Attendance tracking via QR scans
- **STREAK**: Member streak tracking for engagement
- **JOB_POSTING**: Job openings for staff recruitment
- **APPLICATION**: Job applications with CV attachments

## Detailed Table Schema

### 1. users
Central user table for all system users with role-based access.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INTEGER | PK, AUTO_INCREMENT | Unique identifier |
| email | VARCHAR(180) | UNIQUE, NOT NULL | User email (login identifier) |
| full_name | VARCHAR(150) | NULLABLE | User's full name |
| password | VARCHAR | NOT NULL | Hashed password |
| role | ENUM | NOT NULL | User role (ADMIN, RECEPTIONIST, COACH, MEMBER) |
| status | VARCHAR(50) | NOT NULL, DEFAULT 'ACTIVE' | Account status (ACTIVE, INACTIVE) |
| created_at | DATETIME_IMMUTABLE | NOT NULL | Account creation timestamp |

**Indexes:**
- Primary key on id
- Unique index on email

**Relationships:**
- One-to-One with schedules (as member)
- One-to-One with streaks (as member)
- One-to-Many with check_ins (as member)
- One-to-Many with bookings (as member)
- One-to-Many with payments (as member)
- One-to-Many with class_sessions (as coach)
- One-to-Many with payments (as processed_by staff)

---

### 2. plans
Membership plans defining pricing, duration, and access scope.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INTEGER | PK, AUTO_INCREMENT | Unique identifier |
| name | VARCHAR(100) | NOT NULL | Plan name |
| price | DECIMAL(10,2) | NOT NULL | Plan price |
| duration_days | INTEGER | NOT NULL | Plan duration in days |
| access_scope | ENUM | NOT NULL | Access scope (OPEN_GYM, CLASSES, BOTH) |
| max_members | INTEGER | NULLABLE | Maximum subscribers (NULL = unlimited) |
| is_active | BOOLEAN | NOT NULL, DEFAULT TRUE | Plan availability status |

**Indexes:**
- Primary key on id

**Relationships:**
- One-to-Many with subscriptions

**Business Rules:**
- Rule 10: Plan with max_members cannot accept new subscribers once cap reached
- Only active plans can be subscribed to

---

### 3. subscriptions
Links members to plans with time-based validity.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INTEGER | PK, AUTO_INCREMENT | Unique identifier |
| member_id | INTEGER | FK → users.id, NOT NULL, CASCADE | Member reference |
| plan_id | INTEGER | FK → plans.id, NOT NULL | Plan reference |
| start_date | DATE_IMMUTABLE | NOT NULL | Subscription start date |
| end_date | DATE_IMMUTABLE | NOT NULL | Subscription end date |
| status | ENUM | NOT NULL | Status (PENDING, ACTIVE, EXPIRED) |

**Indexes:**
- Primary key on id
- Index on (member_id, status) for active lookups

**Relationships:**
- Many-to-One with users (member)
- Many-to-One with plans
- One-to-Many with payments

**Business Logic:**
- Active when status = ACTIVE and current date is between start_date and end_date
- Status changes to ACTIVE upon payment
- Status changes to EXPIRED after end_date

---

### 4. class_sessions
Scheduled gym classes with coach, room, and capacity details.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INTEGER | PK, AUTO_INCREMENT | Unique identifier |
| name | VARCHAR(120) | NOT NULL | Class name |
| coach_id | INTEGER | FK → users.id, NOT NULL, CASCADE | Assigned coach |
| room | VARCHAR(100) | NOT NULL | Room location |
| day_of_week | VARCHAR(20) | NOT NULL | Day (MONDAY, TUESDAY, etc.) |
| start_time | TIME_IMMUTABLE | NOT NULL | Class start time |
| end_time | TIME_IMMUTABLE | NOT NULL | Class end time |
| capacity | INTEGER | NOT NULL | Maximum participants |

**Indexes:**
- Primary key on id
- Index on (day_of_week, start_time, end_time) for conflict detection

**Relationships:**
- Many-to-One with users (coach)
- One-to-Many with bookings

**Business Rules:**
- Rule 11: Coach cannot be assigned to overlapping classes
- Rule 12: Room cannot host overlapping classes
- Start time must be before end time

---

### 5. bookings
User bookings for class sessions with waitlist support.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INTEGER | PK, AUTO_INCREMENT | Unique identifier |
| member_id | INTEGER | FK → users.id, NOT NULL, CASCADE | Booking member |
| class_session_id | INTEGER | FK → class_sessions.id, NOT NULL, CASCADE | Booked class |
| status | ENUM | NOT NULL | Status (CONFIRMED, WAITLIST, CANCELLED) |
| booked_at | DATETIME_IMMUTABLE | NOT NULL | Booking timestamp |

**Indexes:**
- Primary key on id
- Index on (member_id, status) for member bookings
- Index on (class_session_id, status) for class bookings

**Relationships:**
- Many-to-One with users (member)
- Many-to-One with class_sessions

**Business Rules:**
- Rule 7: Booking requires active membership with class access
- Rule 9: Confirmed bookings limited by capacity; extras go to waitlist
- Rule 13: Member cannot book overlapping classes
- Cancelled confirmed bookings auto-promote waitlist

---

### 6. payments
Payment records for subscriptions (online and cash).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INTEGER | PK, AUTO_INCREMENT | Unique identifier |
| member_id | INTEGER | FK → users.id, NOT NULL, CASCADE | Paying member |
| subscription_id | INTEGER | FK → subscriptions.id, NOT NULL, CASCADE | Related subscription |
| amount | DECIMAL(10,2) | NOT NULL | Payment amount |
| method | ENUM | NOT NULL | Payment method (ONLINE, CASH) |
| processed_by_id | INTEGER | FK → users.id, NULLABLE, SET NULL | Staff who processed (NULL for online) |
| paid_at | DATETIME_IMMUTABLE | NOT NULL | Payment timestamp |

**Indexes:**
- Primary key on id
- Index on paid_at for revenue queries
- Index on method for breakdown reports

**Relationships:**
- Many-to-One with users (member)
- Many-to-One with subscriptions
- Many-to-One with users (processed_by staff)

**Business Logic:**
- Online payments: processed_by is NULL
- Cash payments: processed_by is the receptionist/admin
- Payment activates subscription status

---

### 7. schedules
Member weekly attendance schedules for streak calculation.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INTEGER | PK, AUTO_INCREMENT | Unique identifier |
| member_id | INTEGER | FK → users.id, NOT NULL, UNIQUE, CASCADE | Schedule owner |
| week_days | JSON | NOT NULL | Array of days ["MONDAY", "WEDNESDAY", ...] |
| updated_at | DATETIME_IMMUTABLE | NOT NULL | Last update timestamp |

**Indexes:**
- Primary key on id
- Unique index on member_id

**Relationships:**
- One-to-One with users (member)

**Business Logic:**
- JSON array stores scheduled days
- Streak increments only on scheduled days
- Missed scheduled day resets streak

---

### 8. check_ins
Attendance tracking via QR code scans.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INTEGER | PK, AUTO_INCREMENT | Unique identifier |
| member_id | INTEGER | FK → users.id, NOT NULL, CASCADE | Checking in member |
| check_in_date | DATE_IMMUTABLE | NOT NULL | Date of check-in (normalized) |
| scanned_at | DATETIME_IMMUTABLE | NOT NULL | Exact scan timestamp |

**Indexes:**
- Primary key on id
- Index on (member_id, check_in_date) for duplicate prevention

**Relationships:**
- Many-to-One with users (member)

**Business Rules:**
- Rule 8: QR check-in requires active membership with open gym access
- One check-in per day per member
- Same scan used for gym and class attendance

---

### 9. streaks
Member streak tracking for engagement gamification.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INTEGER | PK, AUTO_INCREMENT | Unique identifier |
| member_id | INTEGER | FK → users.id, NOT NULL, UNIQUE, CASCADE | Streak owner |
| current_streak | INTEGER | NOT NULL, DEFAULT 0 | Current streak count |
| last_success_date | DATE_IMMUTABLE | NULLABLE | Last successful check-in date |

**Indexes:**
- Primary key on id
- Unique index on member_id

**Relationships:**
- One-to-One with users (member)

**Business Logic:**
- Streak increments on scheduled day check-ins
- Missed scheduled day resets streak to 0
- Non-scheduled day visits don't affect streak

---

### 10. job_postings
Job openings for coach and receptionist positions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INTEGER | PK, AUTO_INCREMENT | Unique identifier |
| position_type | ENUM | NOT NULL | Position (COACH, RECEPTIONIST) |
| status | ENUM | NOT NULL | Status (OPEN, CLOSED) |
| opened_at | DATETIME_IMMUTABLE | NOT NULL | Posting creation date |

**Indexes:**
- Primary key on id

**Relationships:**
- One-to-Many with applications

**Business Logic:**
- Only OPEN postings accept applications
- CLOSED postings are no longer visible to applicants

---

### 11. applications
Job applications with CV attachments.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INTEGER | PK, AUTO_INCREMENT | Unique identifier |
| job_posting_id | INTEGER | FK → job_postings.id, NOT NULL, CASCADE | Related job posting |
| full_name | VARCHAR(150) | NOT NULL | Applicant name |
| email | VARCHAR(180) | NOT NULL | Applicant email |
| cv_path | VARCHAR(255) | NOT NULL | CV file path |
| status | ENUM | NOT NULL | Status (PENDING, ACCEPTED, REJECTED) |
| submitted_at | DATETIME_IMMUTABLE | NOT NULL | Submission timestamp |

**Indexes:**
- Primary key on id

**Relationships:**
- Many-to-One with job_postings

**Business Logic:**
- PENDING applications can be accepted or rejected
- ACCEPTED applications create staff accounts
- Email notifications sent on status changes

---

## Entity Relationships Summary

### User Relationships
- **As Member**: subscriptions, bookings, payments, check_ins, schedule, streak
- **As Coach**: class_sessions (teaching)
- **As Staff**: payments (processed_by)
- **As Applicant**: applications

### Plan Relationships
- **Has Many**: subscriptions

### Subscription Relationships
- **Belongs To**: user (member), plan
- **Has Many**: payments

### Class Session Relationships
- **Belongs To**: user (coach)
- **Has Many**: bookings

### Booking Relationships
- **Belongs To**: user (member), class_session

### Payment Relationships
- **Belongs To**: user (member), subscription, user (processed_by)

### Job Posting Relationships
- **Has Many**: applications

### Application Relationships
- **Belongs To**: job_posting

## Database Constraints and Rules

### Foreign Key Constraints
All foreign keys have CASCADE delete to maintain referential integrity:
- Deleting a user deletes their subscriptions, bookings, payments, check-ins, schedule, and streak
- Deleting a plan deletes all subscriptions
- Deleting a class session deletes all bookings
- Deleting a subscription deletes all payments
- Deleting a job posting deletes all applications

### Business Rule Enforcement
Application-level enforcement (via service layer):
- **Rule 7**: Booking validation checks active subscription with class access
- **Rule 8**: Check-in validation checks active subscription with open gym access
- **Rule 9**: Capacity check before booking; overflow goes to waitlist
- **Rule 10**: Plan cap check before subscription
- **Rule 11**: Coach conflict detection before class creation/update
- **Rule 12**: Room conflict detection before class creation/update
- **Rule 13**: Member conflict detection before booking

### Data Integrity
- Email addresses stored in lowercase for consistency
- Passwords hashed using Symfony's password hasher
- Dates normalized to midnight for day-based calculations
- Weekday names standardized to uppercase (MONDAY, TUESDAY, etc.)
- Monetary values stored as DECIMAL(10,2) for precision

## Scalability Considerations

### Future Multi-Gym Support
The schema is designed to support multiple gym branches with minimal changes:
- Add `gym_id` column to relevant tables (plans, class_sessions, bookings, etc.)
- Create `gyms` table with branch information
- Update queries to filter by gym_id

### Performance Optimization
- Strategic indexes on frequently queried columns
- Cascade deletes for automatic cleanup
- JSON type for flexible data (schedules)
- Date/time immutability for consistency

## Migration Strategy

### Initial Setup
1. Create all tables in order respecting foreign key dependencies
2. Insert initial admin user
3. Create initial membership plans
4. Set up file upload directory for CVs

### Version Control
Use Doctrine Migrations for schema changes:
- Each migration represents a schema change
- Reversible migrations for rollback support
- Migration version tracking in doctrine_migration_versions table

## Security Considerations

### Sensitive Data
- Passwords: Hashed using bcrypt/argon2
- Email: Unique identifiers, indexed
- Personal data: full_name, phone (if added)

### Access Control
- Role-based access enforced at application layer
- Database user with least privilege principle
- Prepared statements via Doctrine ORM (SQL injection protection)

## Backup Strategy

### Recommended Backup Schedule
- Daily full backups
- Hourly transaction log backups
- Retention: 30 days daily, 12 months weekly

### Recovery
- Point-in-time recovery using transaction logs
- Test restore procedures monthly
- Documented recovery runbook
