# GymUp API Routes Documentation

## Overview
This document provides a comprehensive listing of all API endpoints in the GymUp backend system. Endpoints are organized by module and include required authentication roles.

## Base URL
```
http://localhost:8000/api
```

## Authentication
All endpoints (except public hiring endpoints) require authentication via JWT token in the Authorization header:
```
Authorization: Bearer <token>
```

## Response Format
All endpoints return JSON responses:
- Success: HTTP 200/201 with data
- Errors: HTTP 400/401/403/404/409 with error message

---

## Module: Authentication

### Public Endpoints

#### Register Member
- **Endpoint**: `POST /api/auth/register`
- **Auth**: None
- **Request Body**:
```json
{
  "email": "member@example.com",
  "password": "SecurePassword123!",
  "fullName": "John Doe"
}
```
- **Response**: User object with id, email, fullName, role

#### Login
- **Endpoint**: `POST /api/auth/login`
- **Auth**: None
- **Request Body**:
```json
{
  "email": "user@example.com",
  "password": "password"
}
```
- **Response**: User object with id, email, fullName, role, status

### Authenticated Endpoints

#### Get Current User
- **Endpoint**: `GET /api/auth/me`
- **Auth**: Any authenticated user
- **Response**: Current user details

#### Update Profile
- **Endpoint**: `PUT /api/auth/profile`
- **Auth**: Any authenticated user
- **Request Body**:
```json
{
  "fullName": "Updated Name",
  "password": "NewPassword123!"
}
```
- **Response**: Updated user object

---

## Module: Membership

### Admin Endpoints

#### List All Plans
- **Endpoint**: `GET /api/admin/plans`
- **Auth**: ROLE_ADMIN
- **Response**: Array of all plans

#### Create Plan
- **Endpoint**: `POST /api/admin/plans`
- **Auth**: ROLE_ADMIN
- **Request Body**:
```json
{
  "name": "Premium Plan",
  "price": 99.99,
  "durationDays": 30,
  "accessScope": "BOTH",
  "maxMembers": 100
}
```
- **Response**: Created plan object

#### Update Plan
- **Endpoint**: `PATCH /api/admin/plans/{id}`
- **Auth**: ROLE_ADMIN
- **Request Body** (all fields optional):
```json
{
  "name": "Updated Plan Name",
  "price": 109.99,
  "durationDays": 60,
  "accessScope": "CLASSES",
  "maxMembers": 150,
  "isActive": true
}
```
- **Response**: Updated plan object

#### Activate Plan
- **Endpoint**: `PATCH /api/admin/plans/{id}/activate`
- **Auth**: ROLE_ADMIN
- **Response**: Success message

#### Deactivate Plan
- **Endpoint**: `PATCH /api/admin/plans/{id}/deactivate`
- **Auth**: ROLE_ADMIN
- **Response**: Success message

#### List Subscriptions
- **Endpoint**: `GET /api/admin/subscriptions`
- **Auth**: ROLE_ADMIN
- **Query Params**:
  - `status`: Filter by status (ACTIVE, PENDING, EXPIRED)
  - `planId`: Filter by plan ID
  - `limit`: Number of results (default: 50, max: 100)
- **Response**: Array of subscriptions with member and plan details

#### View Subscription Details
- **Endpoint**: `GET /api/admin/subscriptions/{id}`
- **Auth**: ROLE_ADMIN
- **Response**: Subscription with payment history

#### Get Expiring Subscriptions
- **Endpoint**: `GET /api/admin/plans/expiring`
- **Auth**: ROLE_ADMIN
- **Query Params**:
  - `days`: Days threshold (default: 7)
- **Response**: Array of expiring subscriptions

### Receptionist Endpoints

#### View Running Plans
- **Endpoint**: `GET /api/receptionist/plans`
- **Auth**: ROLE_RECEPTIONIST
- **Response**: Array of active plans

#### Register Walk-in Member
- **Endpoint**: `POST /api/receptionist/members/register`
- **Auth**: ROLE_RECEPTIONIST
- **Request Body**:
```json
{
  "email": "walkin@example.com",
  "fullName": "Jane Smith",
  "planId": 1
}
```
- **Response**: Created member, subscription, and temporary password

### Member Endpoints

#### View Available Plans
- **Endpoint**: `GET /api/member/membership/plans`
- **Auth**: ROLE_MEMBER
- **Response**: Array of active plans

#### Subscribe to Plan
- **Endpoint**: `POST /api/member/membership/subscribe`
- **Auth**: ROLE_MEMBER
- **Request Body**:
```json
{
  "planId": 1
}
```
- **Response**: Created subscription (PENDING status)

#### Renew Subscription
- **Endpoint**: `POST /api/member/membership/renew`
- **Auth**: ROLE_MEMBER
- **Request Body**:
```json
{
  "planId": 1
}
```
- **Response**: Created renewal subscription

#### View Subscription Status
- **Endpoint**: `GET /api/member/membership/status`
- **Auth**: ROLE_MEMBER
- **Response**: Current subscription with expiry info

#### View Subscription History
- **Endpoint**: `GET /api/member/membership/history`
- **Auth**: ROLE_MEMBER
- **Response**: Array of all subscriptions

---

## Module: Booking

### Admin Endpoints

#### List All Classes
- **Endpoint**: `GET /api/admin/classes`
- **Auth**: ROLE_ADMIN
- **Response**: Array of all class sessions with booking counts

#### Create Class
- **Endpoint**: `POST /api/admin/classes`
- **Auth**: ROLE_ADMIN
- **Request Body**:
```json
{
  "name": "Yoga Class",
  "coachId": 5,
  "room": "Room A",
  "dayOfWeek": "MONDAY",
  "startTime": "09:00",
  "endTime": "10:00",
  "capacity": 20
}
```
- **Response**: Created class session

#### Update Class
- **Endpoint**: `PATCH /api/admin/classes/{id}`
- **Auth**: ROLE_ADMIN
- **Request Body** (all fields optional):
```json
{
  "name": "Updated Class Name",
  "coachId": 6,
  "room": "Room B",
  "dayOfWeek": "WEDNESDAY",
  "startTime": "10:00",
  "endTime": "11:00",
  "capacity": 25
}
```
- **Response**: Updated class session

#### Delete Class
- **Endpoint**: `DELETE /api/admin/classes/{id}`
- **Auth**: ROLE_ADMIN
- **Response**: Success message (prevents deletion if bookings exist)

#### View Class Bookings
- **Endpoint**: `GET /api/admin/classes/{id}/bookings`
- **Auth**: ROLE_ADMIN
- **Response**: Array of bookings for the class

#### View Class Waitlist
- **Endpoint**: `GET /api/admin/classes/{id}/waitlist`
- **Auth**: ROLE_ADMIN
- **Response**: Array of waitlisted members with positions

### Coach Endpoints

#### View My Schedule
- **Endpoint**: `GET /api/coach/schedule`
- **Auth**: ROLE_COACH
- **Response**: Array of assigned class sessions

#### View Class Members
- **Endpoint**: `GET /api/coach/classes/{id}/members`
- **Auth**: ROLE_COACH
- **Response**: List of enrolled members for the class

### Member Endpoints

#### Browse Classes
- **Endpoint**: `GET /api/member/classes`
- **Auth**: ROLE_MEMBER
- **Response**: Array of available classes with availability

#### Book a Class
- **Endpoint**: `POST /api/member/classes/{id}/book`
- **Auth**: ROLE_MEMBER
- **Response**: Booking details (may be CONFIRMED or WAITLIST)

#### Cancel Booking
- **Endpoint**: `POST /api/member/bookings/{id}/cancel`
- **Auth**: ROLE_MEMBER
- **Response**: Success message with promoted waitlist ID (if any)

#### View My Bookings
- **Endpoint**: `GET /api/member/bookings`
- **Auth**: ROLE_MEMBER
- **Response**: Array of member's bookings

---

## Module: Payment

### Admin Endpoints

#### View Revenue Summary
- **Endpoint**: `GET /api/admin/revenue/summary`
- **Auth**: ROLE_ADMIN
- **Query Params**:
  - `startDate`: Filter start date (ISO format)
  - `endDate`: Filter end date (ISO format)
- **Response**: Revenue breakdown (online vs cash)

#### View Recent Payments
- **Endpoint**: `GET /api/admin/revenue/payments`
- **Auth**: ROLE_ADMIN
- **Query Params**:
  - `limit`: Number of results (default: 50, max: 100)
- **Response**: Array of recent payments

### Receptionist Endpoints

#### Record Cash Payment
- **Endpoint**: `POST /api/receptionist/payments/cash`
- **Auth**: ROLE_RECEPTIONIST
- **Request Body**:
```json
{
  "memberId": 1,
  "subscriptionId": 5,
  "amount": 99.99
}
```
- **Response**: Payment record with activated subscription

### Member Endpoints

#### Process Online Payment
- **Endpoint**: `POST /api/member/payments/checkout`
- **Auth**: ROLE_MEMBER
- **Request Body**:
```json
{
  "subscriptionId": 5
}
```
- **Response**: Payment record with activated subscription

#### View Payment History
- **Endpoint**: `GET /api/member/payments/history`
- **Auth**: ROLE_MEMBER
- **Response**: Array of member's payments

---

## Module: Attendance

### Member Endpoints

#### Check In (QR Scan)
- **Endpoint**: `POST /api/attendance/check-in`
- **Auth**: ROLE_MEMBER
- **Response**: Check-in confirmation with streak update

#### View Attendance History
- **Endpoint**: `GET /api/attendance/history`
- **Auth**: ROLE_MEMBER
- **Query Params**:
  - `limit`: Number of results (default: 20, max: 100)
- **Response**: Attendance history with current streak

#### View Weekly Schedule
- **Endpoint**: `GET /api/attendance/schedule`
- **Auth**: ROLE_MEMBER
- **Response**: Current weekly schedule

#### Update Weekly Schedule
- **Endpoint**: `PUT /api/attendance/schedule`
- **Auth**: ROLE_MEMBER
- **Request Body**:
```json
{
  "weekDays": ["MONDAY", "WEDNESDAY", "FRIDAY"]
}
```
- **Response**: Updated schedule

---

## Module: Hiring

### Public Endpoints

#### View Open Job Postings
- **Endpoint**: `GET /api/public/jobs`
- **Auth**: None
- **Response**: Array of open job postings

#### Submit Application
- **Endpoint**: `POST /api/public/jobs/{id}/apply`
- **Auth**: None
- **Request Body** (multipart/form-data):
```
fullName: "John Doe"
email: "john@example.com"
cv: [file upload]
```
- **Response**: Application confirmation

### Admin Endpoints

#### List Job Postings
- **Endpoint**: `GET /api/admin/job-postings`
- **Auth**: ROLE_ADMIN
- **Response**: Array of all job postings

#### Create Job Posting
- **Endpoint**: `POST /api/admin/job-postings`
- **Auth**: ROLE_ADMIN
- **Request Body**:
```json
{
  "positionType": "COACH"
}
```
- **Response**: Created job posting

#### Close Job Posting
- **Endpoint**: `PATCH /api/admin/job-postings/{id}/close`
- **Auth**: ROLE_ADMIN
- **Response**: Success message

#### List Applications
- **Endpoint**: `GET /api/admin/applications`
- **Auth**: ROLE_ADMIN
- **Query Params**:
  - `status`: Filter by status (PENDING, ACCEPTED, REJECTED)
- **Response**: Array of applications

#### Accept Application
- **Endpoint**: `POST /api/admin/applications/{id}/accept`
- **Auth**: ROLE_ADMIN
- **Response**: Staff account creation with credentials

#### Reject Application
- **Endpoint**: `POST /api/admin/applications/{id}/reject`
- **Auth**: ROLE_ADMIN
- **Response**: Success message

---

## Module: Dashboard

### Admin Endpoints

#### View Dashboard Overview
- **Endpoint**: `GET /api/admin/dashboard`
- **Auth**: ROLE_ADMIN
- **Response**: Dashboard metrics including:
  - Active members count
  - Total registered members
  - Class occupancy rates
  - Monthly revenue breakdown
  - Expiring memberships count
  - Top streak leaderboard

---

## Error Responses

### Standard Error Format
```json
{
  "error": "Error message describing the issue"
}
```

### Common HTTP Status Codes
- **200 OK**: Successful GET/PUT/PATCH
- **201 Created**: Successful POST
- **400 Bad Request**: Invalid input data
- **401 Unauthorized**: Missing or invalid authentication
- **403 Forbidden**: Insufficient permissions
- **404 Not Found**: Resource not found
- **409 Conflict**: Business rule violation (e.g., capacity reached, conflict detected)
- **422 Unprocessable Entity**: Application state prevents action

### Common Error Messages
- "Authentication required"
- "Invalid email or password"
- "This membership plan is currently deactivated"
- "Enrollment limit reached (Rule 10)"
- "Coach scheduling conflict (Rule 11)"
- "Room scheduling conflict (Rule 12)"
- "Booking conflict (Rule 13)"
- "Class is currently full. You have been placed on the waitlist"
- "You must have an active membership subscription to book a class"
- "Check-in rejected (Rule 8): You must have an active membership subscription to enter the gym"

---

## Rate Limiting
Currently not implemented. Recommended for production:
- Authentication endpoints: 5 requests per minute per IP
- Booking endpoints: 10 requests per minute per user
- Check-in endpoint: 1 request per minute per user

## Pagination
List endpoints support pagination via query parameters:
- `limit`: Number of results per page (default varies, max 100)
- Future: `page` and `offset` parameters for large datasets

## Versioning
API versioning via URL prefix (future):
- Current: `/api/`
- Future: `/api/v1/`, `/api/v2/`

## CORS Configuration
For frontend integration, configure CORS to allow:
- Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
- Headers: Authorization, Content-Type
- Origins: Frontend application URL(s)

---

## Testing Endpoints

### Example cURL Commands

#### Register Member
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"Test123!","fullName":"Test User"}'
```

#### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"Test123!"}'
```

#### Browse Classes (Authenticated)
```bash
curl -X GET http://localhost:8000/api/member/classes \
  -H "Authorization: Bearer <token>"
```

#### Book a Class
```bash
curl -X POST http://localhost:8000/api/member/classes/1/book \
  -H "Authorization: Bearer <token>"
```

#### Check In
```bash
curl -X POST http://localhost:8000/api/attendance/check-in \
  -H "Authorization: Bearer <token>"
```

---

## Security Notes

### Authentication Flow
1. User registers or logs in
2. Server returns JWT token
3. Client includes token in Authorization header
4. Server validates token on each request
5. Token expires after configured time (e.g., 1 hour)

### Role-Based Access
- Access control enforced via Symfony Security voters
- Each endpoint checks user role before processing
- Admin has access to all endpoints
- Other roles limited to their specific modules

### Input Validation
- All inputs validated at controller level
- Business rules enforced at service layer
- Database constraints as final validation layer

---

## Future Enhancements

### Planned Endpoints
- **Member Profile**: Extended profile management (phone, address, etc.)
- **Class History**: View past class attendance
- **Streak Leaderboard**: Public leaderboard (not just admin)
- **Notification Preferences**: Opt-in/out for email reminders
- **Class Search**: Advanced filtering by day, time, coach

### API Improvements
- GraphQL endpoint for flexible queries
- WebSocket for real-time updates (class availability)
- API documentation via OpenAPI/Swagger
- Request logging and analytics
