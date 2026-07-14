# 🛡️ Kenexoft SHIELD – Multi-Persona Registration & Authentication System

An enterprise-grade multi-persona registration and authentication system developed using **PHP 8.2+, MySQL 8, HTML5, CSS3, and Vanilla JavaScript**.

The application provides secure registration, authentication, profile management, GST classification, company management, consultant CV uploads, and an extensible REST API following the MVC architecture.

---

# Features

## Multi-Persona Registration

Supports the following personas:

- MSP (Managed Service Provider)
- Company
- IT Consultant

> **Note:** The Individual persona has been completely removed from the application.

---

## Authentication

- Secure Login
- Logout
- JWT Authentication
- Password Hashing (bcrypt)
- Forgot Password
- Password Reset
- Authentication Middleware
- Protected API Routes

---

## Registration

Supports:

- Personal Information
- Company Information
- Registered Office
- Branch Management
- Billing Address
- GST Information
- Service Selection
- Consultant Expertise
- Secure CV Upload (IT Consultant)

---

## IT Consultant Module

- Professional Profile
- Skills & Expertise
- Secure Resume/CV Upload
- PDF/DOC/DOCX Support
- Maximum Upload Size: 10 MB
- Files stored outside the public web root

---

## Company & MSP Module

- Company Details
- Registered Office
- Multiple Branches
- Subscription Location
- Billing Address
- Managed Services

---

## GST Classification

Automatic GST classification based on:

- Same Country
- Same State

Tax Types:

- CGST + SGST
- IGST

---

## Security

- JWT Authentication
- bcrypt Password Hashing
- Prepared Statements (PDO)
- SQL Injection Protection
- CSRF Protection Ready
- Input Validation
- File Upload Validation
- Audit Logging
- Rate Limiting Ready

---

# Technology Stack

## Backend

- PHP 8.2+
- MVC Architecture
- PDO
- REST API
- JWT Authentication

## Frontend

- HTML5
- CSS3
- Vanilla JavaScript (ES6)

## Database

- MySQL 8+

## Web Server

- Apache (XAMPP Compatible)

---

# Project Structure

```
Shield3.0/
│
├── app/
│   ├── Controllers/
│   ├── Core/
│   ├── Middleware/
│   ├── Models/
│   ├── Repositories/
│   ├── Services/
│   ├── Views/
│   └── config/
│
├── database/
│   ├── migrations/
│   └── seeders/
│
├── public/
│   └── assets/
│       ├── css/
│       ├── js/
│       └── img/
│
├── storage/
│   ├── uploads/
│   │   └── cv/
│   ├── sessions/
│   └── tmp/
│
├── docs/
│   ├── openapi.yaml
│   └── postman_collection.json
│
├── docker/
├── index.php
├── Dockerfile
├── docker-compose.yml
└── README.md
```

---

# Database

The application uses **MySQL 8**.

Main tables include:

- users
- companies
- addresses
- branches
- subscription_settings
- managed_services
- user_services
- expertise
- consultant_expertise
- consultant_cv
- gst_classification
- audit_logs
- password_resets

---

# Installation

## 1. Clone Repository

```bash
git clone https://github.com/your-username/shield3.0.git
```

Move the project into:

```
xampp/htdocs/
```

---

## 2. Start XAMPP

Start:

- Apache
- MySQL

---

## 3. Create Database

Open phpMyAdmin

Create database:

```
shield3
```

---

## 4. Import Database

Import:

```
database/migrations/*.sql
```

Then import:

```
database/seeders/*.sql
```

---

## 5. Configure Environment

Copy

```
.env.example
```

to

```
.env
```

Update database credentials:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shield3
DB_USERNAME=root
DB_PASSWORD=
```

---

## 6. Open Application

```
http://localhost/Shield3.0/
```

---

# API Endpoints

## Authentication

| Method | Endpoint |
|----------|---------------------------|
| POST | /api/auth/register |
| POST | /api/auth/login |
| POST | /api/auth/logout |
| POST | /api/auth/forgot-password |
| POST | /api/auth/reset-password |

---

## User

| Method | Endpoint |
|----------|-------------------|
| GET | /api/users/me |
| PUT | /api/users/me |

---

## Address

| Method | Endpoint |
|----------|----------------------|
| GET | /api/addresses |
| POST | /api/addresses |
| PUT | /api/addresses/{id} |
| DELETE | /api/addresses/{id} |

---

## Branches

| Method | Endpoint |
|----------|----------------------|
| GET | /api/branches |
| POST | /api/branches |
| PUT | /api/branches/{id} |
| DELETE | /api/branches/{id} |

---

## Services

| Method | Endpoint |
|----------|------------------|
| GET | /api/services |
| POST | /api/services |

---

## Consultant

| Method | Endpoint |
|----------|-----------------------------|
| GET | /api/expertise |
| POST | /api/expertise |
| GET | /api/consultant/cv |
| POST | /api/consultant/cv |
| DELETE | /api/consultant/cv |

---

# Security

- JWT Authentication
- Password Hashing (bcrypt)
- CSRF Protection
- SQL Injection Prevention
- Input Validation
- File Upload Validation
- Audit Logging
- Repository Pattern
- MVC Architecture

---

# Documentation

API documentation is available in:

```
docs/openapi.yaml
```

Postman Collection:

```
docs/postman_collection.json
```

---

# Docker

Run using Docker:

```bash
docker-compose up --build
```

---

# Future Enhancements

- Email Verification
- Two-Factor Authentication (2FA)
- Admin Dashboard
- User Activity Reports
- Analytics Dashboard
- Notification System
- Multi-language Support
- Dark Mode

---

# Author

**Developed as part of the Kenexoft SHIELD Project**

Enterprise Multi-Persona Registration & Authentication System

---

# License

This project is intended for educational and demonstration purposes.

© 2026 Kenexoft SHIELD. All rights reserved.
