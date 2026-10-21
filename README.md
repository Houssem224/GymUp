# GymUp - Gym Management System

A comprehensive web application for digitizing and centralizing gym management, combining flexible membership models, staff workflows, and QR-based attendance tracking.

## 🎯 Project Overview

GymUp is designed to replace manual methods (paper registers, spreadsheets) with a digital solution that manages:
- Membership plans and subscriptions
- Class scheduling and bookings
- Staff hiring and management
- QR-based attendance tracking
- Streak system for member engagement
- Revenue tracking and reporting

## 🏗️ Architecture

- **Backend**: Symfony 6+ (PHP) with Doctrine ORM
- **Frontend**: Angular (to be implemented)
- **Database**: PostgreSQL (recommended), MySQL compatible
- **Authentication**: JWT tokens
- **Email**: Symfony Mailer with Twig templates

## 📋 Features

### For Administrators
- ✅ Create, edit, and deactivate membership plans
- ✅ Create and manage classes (name, coach, room, schedule, capacity)
- ✅ Post job openings for Coach or Receptionist positions
- ✅ Review job applications (form + CV) and accept/reject
- ✅ View and manage bookings and waitlists per class
- ✅ View expiring/expired memberships
- ✅ View payments and revenue reports (online vs cash)
- ✅ Access dashboard with comprehensive metrics

### For Receptionists
- ✅ View list of all running membership plans
- ✅ Register walk-in members and enroll in a plan
- ✅ Record cash payments for in-person registrations and renewals
- ✅ Apply to position through job-application process

### For Coaches
- ✅ Apply to position through job-application process
- ✅ View assigned class schedule
- ✅ View list of members booked for each class
- ✅ Edit own profile information after logging in

### For Members
- ✅ Register and subscribe to plan online with online payment
- ✅ Be registered in person by receptionist with cash payment
- ✅ View membership status and expiry date
- ✅ Browse class schedule and book a class
- ✅ Join waitlist when class is full
- ✅ Cancel a booking
- ✅ Set personal weekly attendance schedule
- ✅ Scan QR code at gym entrance to check in
- ✅ View booking history, attendance record, and current streak

## 🔐 Business Rules Implemented

All 7 system rules are enforced:

1. **Rule 7**: Class booking requires active membership with class access
2. **Rule 8**: QR check-in requires active membership with open gym access
3. **Rule 9**: Class capacity enforcement with waitlist
4. **Rule 10**: Plan member cap enforcement
5. **Rule 11**: Coach conflict prevention (no overlapping classes)
6. **Rule 12**: Room conflict prevention (no overlapping classes)
7. **Rule 13**: Member conflict prevention (no overlapping bookings)

## 📚 Documentation

- **[DATABASE_SCHEMA.md](DATABASE_SCHEMA.md)** - Complete database schema with all tables, relationships, and constraints
- **[API_ROUTES_DOCUMENTATION.md](API_ROUTES_DOCUMENTATION.md)** - Comprehensive API endpoint documentation
- **[BACKEND_COMPLETION_REPORT.md](BACKEND_COMPLETION_REPORT.md)** - Full implementation report with status and coverage
- **[BOOKING_MEMBERSHIP_BACKEND_SUMMARY.md](BOOKING_MEMBERSHIP_BACKEND_SUMMARY.md)** - Detailed feature implementation summary

## 🚀 Getting Started

### Prerequisites

- PHP 8.1 or higher
- Composer
- PostgreSQL or MySQL
- Symfony CLI (optional but recommended)

### Installation

1. Clone the repository:
```bash
git clone https://github.com/Houssem224/GymUp.git
cd GymUp
git checkout backend-implementation
```

2. Install dependencies:
```bash
composer install
```

3. Configure environment variables:
```bash
cp .env .env.local
# Edit .env.local with your database and email configuration
```

4. Create database:
```bash
php bin/console doctrine:database:create
```

5. Run migrations:
```bash
php bin/console doctrine:migrations:migrate
```

6. Create initial admin user:
```bash
php bin/console doctrine:fixtures:load
```

7. Start the server:
```bash
symfony server:start
# or
php -S localhost:8000 -t public
```

### Environment Configuration

Required environment variables in `.env.local`:

```env
# Database
DATABASE_URL="postgresql://user:password@127.0.0.1:5432/gymup?serverVersion=16&charset=utf8"

# JWT
JWT_SECRET_KEY="your-secret-key"
JWT_TOKEN_TTL="3600"

# Email
MAILER_DSN="smtp://user:password@smtp.example.com:587"
APP_URL="http://localhost:4200"
EMAIL_FROM="noreply@gymup.com"
EMAIL_FROM_NAME="GymUp"

# File Upload
UPLOAD_DIR="public/uploads/cvs"
```

## 🧪 Testing

Run tests:
```bash
php bin/phpunit
```

## 📊 Cron Jobs

Set up the expiry reminder cron job:
```bash
# Daily at 9 AM
0 9 * * * php /path/to/bin/console membership:send-expiry-reminders --days=7
```

Test the command manually:
```bash
php bin/console membership:send-expiry-reminders --days=7 --dry-run
```

## 🗂️ Project Structure

```
src/
├── Admin/              # Dashboard and admin features
├── Attendance/         # QR check-in, streak system, schedules
├── Auth/               # Authentication and user management
├── Booking/            # Class sessions and bookings
├── Hiring/             # Job postings and applications
├── Membership/         # Plans and subscriptions
├── Payment/            # Payment processing
└── Shared/             # Shared enums, traits, services
```

## 🔒 Security

- JWT token-based authentication
- Role-based access control (Admin, Receptionist, Coach, Member)
- Password hashing using Symfony's password hasher
- SQL injection prevention via Doctrine ORM
- Input validation at controller and service layers

## 📈 API Base URL

```
http://localhost:8000/api
```

## 👥 Team

- **Houssem Amiri** - Developer
- **Salim Afli** - Developer

## 📄 License

This project is part of an academic coursework project.

## 🙏 Acknowledgments

Built with Symfony 6 and following Symfony best practices.

---

**Note**: This is the backend implementation. The Angular frontend is to be implemented separately.
