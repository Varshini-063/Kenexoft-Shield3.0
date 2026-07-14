# Kenexoft SHIELD v3.0

Traditional PHP MVC application with a MySQL-backed REST API, PHP views, HTML/CSS, and vanilla JavaScript.

## Requirements

- PHP 8.2+
- Apache with `mod_rewrite`
- MySQL 8+
- PDO MySQL and Fileinfo PHP extensions
- Composer is optional for autoload metadata; the app also includes a local PSR-4 autoloader in `index.php`.

## XAMPP Setup

1. Copy `.env.example` to `.env` and set `JWT_SECRET`.
2. Create the database by running `database/migrations/001_create_shield_schema.sql`.
3. Add auth reset storage by running `database/migrations/002_create_password_resets.sql`.
4. Add user roles by running `database/migrations/003_add_user_roles.sql`.
5. Add dataset assignment storage by running `database/migrations/004_create_datasets.sql`.
6. Seed catalogs by running `database/seeders/001_seed_reference_data.sql`.
7. Seed the default super admin by running `database/seeders/002_seed_super_admin.sql`.
8. Seed dashboard datasets by running `database/seeders/003_seed_datasets.sql`.
9. Ensure `storage/uploads/cv/` is writable by Apache.
10. Open `http://localhost/Shield3.0/`.

## Default Super Admin

- Email: `admin@shield.local`
- Password: `Password123!`
- Role: `SUPER_ADMIN`

## API

- OpenAPI: `docs/openapi.yaml`
- Postman: `docs/postman_collection.json`
- Register: `POST /api/auth/register`
- Login: `POST /api/auth/login`
- Forgot password: `POST /api/auth/forgot-password`
- Reset password: `POST /api/auth/reset-password`
- Current user: `GET /api/users/me`
- Subscriber dashboard datasets: `GET /api/dashboard`
- Admin subscribers: `GET /api/admin/subscribers`
- Admin datasets: `GET /api/admin/datasets`
- Admin subscriber datasets: `GET|PUT /api/admin/subscribers/{id}/datasets`

Login requires `email`, `password`, and `role` (`SUBSCRIBER` or `SUPER_ADMIN`).

IT Consultant registration uses `multipart/form-data` with `persona`, JSON `payload`, and a required `cv` file.

## Docker

Run:

```bash
docker compose up --build
```

Then open `http://localhost:8080`.
