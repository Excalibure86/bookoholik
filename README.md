# 📚 Bookoholik — Home Library Management System

A modern, multilingual web application for managing your personal home library. Track your books, manage writers, lend books to friends, and generate reports — all from a clean, responsive interface.

![Vue.js](https://img.shields.io/badge/Vue.js-3.4-4FC08D?logo=vuedotjs&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)

---

## ✨ Features

### 📖 Book Management
- Full CRUD for your book collection
- Search by title, author, genre, language, year
- Filter by read status, borrowed status, location
- ISBN lookup via Open Library API
- Track book location (room / shelf)
- Mark books as read/unread

### ✍️ Writers Management
- Dedicated writers/authors registry
- Multilingual names (English, Arabic, French)
- Nationality, birth/death year, biography
- Link writers to books when adding a book
- View book count per writer

### 📖 Genres Management
- Create, edit, and delete genres
- Multilingual genre names (English, Arabic, French)
- Select genres when adding books

### 🤝 Lending System
- Lend books to friends with due dates
- Track overdue books with alerts
- Mark books as returned
- Full lending history per book

### 📊 Reports & Analytics
- Dashboard with reading statistics
- Reports by genre, author, year, location
- Language distribution visualization
- Export to CSV and PDF

### 👥 User Management (Admin)
- Role-based access control (Admin, User, Viewer)
- Create/disable/delete users
- JWT-based authentication

### 💾 Backup System
- Automatic daily database backups (2:00 AM)
- Manual backup creation
- Download and manage backup files

### 🌍 Multilingual Interface
- **English** 🇬🇧
- **Arabic** 🇸🇦 (with full RTL support)
- **French** 🇫🇷
- Language switcher in the UI, preference saved per user

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────┐
│                   Frontend                       │
│            Vue.js 3 + Vite + Tailwind           │
│                 (nginx:alpine)                   │
│                   Port 3000                      │
└─────────────────┬───────────────────────────────┘
                  │ HTTP API calls
┌─────────────────▼───────────────────────────────┐
│                   Backend                        │
│          PHP 8.3 + Apache + Composer             │
│              Custom REST API                     │
│                   Port 8080                      │
└─────────────────┬───────────────────────────────┘
                  │ PostgreSQL protocol
┌─────────────────▼───────────────────────────────┐
│                  Database                        │
│              PostgreSQL 16 Alpine                │
│                   Port 5432                      │
└─────────────────────────────────────────────────┘
```

---

## 🚀 Quick Start

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) or [Podman](https://podman.io/getting-started/installation) with Compose support
- No other dependencies required — everything runs in containers

### Installation

1. **Clone the repository**

   ```bash
   git clone <repository-url> bookoholik
   cd bookoholik
   ```

2. **Configure environment (optional)**

   Create a `.env` file in the project root to override defaults:

   ```env
   # Database
   DB_DATABASE=home_library
   DB_USERNAME=library_user
   DB_PASSWORD=library_secret
   DB_PORT=5432

   # Backend
   BACKEND_PORT=8080
   APP_ENV=production
   APP_DEBUG=false
   JWT_SECRET=your-secure-jwt-secret-change-this

   # Frontend
   FRONTEND_PORT=3000
   API_URL=http://localhost:8080/api
   ```

   > If no `.env` file is provided, the defaults shown above are used automatically.

3. **Build and start the application**

   ```bash
   docker compose up -d --build
   ```

   Or with Podman:

   ```bash
   podman compose up -d --build
   ```

4. **Access the application**

   | Service  | URL                        |
   |----------|----------------------------|
   | Frontend | http://localhost:3000       |
   | Backend API | http://localhost:8080/api |
   | Database | localhost:5432             |

5. **Login with default credentials**

   | Field    | Value              |
   |----------|--------------------|
   | Username | `admin`        |
   | Password | `Admin1234!`   |

   > ⚠️ **Change this password immediately after first login!**

---

## 📁 Project Structure

```
bookoholik/
├── docker-compose.yml          # Container orchestration
├── .env                        # Environment variables (create manually)
├── docker/
│   ├── Dockerfile.frontend     # Vue.js multi-stage build
│   ├── Dockerfile.backend      # PHP 8.3 + Apache
│   ├── Dockerfile.db           # PostgreSQL + init script
│   ├── Dockerfile.backup       # Backup cron service
│   ├── nginx.conf              # Frontend nginx configuration
│   ├── apache.conf             # Backend Apache vhost
│   ├── init.sql                # Database schema & seed data
│   └── backup-cron.sh          # Automated backup script
├── backend/
│   ├── composer.json           # PHP dependencies
│   ├── public/
│   │   ├── index.php           # Application entry point
│   │   └── .htaccess           # Apache URL rewriting
│   ├── routes/
│   │   └── api.php             # API route definitions
│   └── app/
│       ├── Config/
│       │   └── Database.php    # PDO connection singleton
│       ├── Controllers/
│       │   ├── AuthController.php
│       │   ├── BooksController.php
│       │   ├── WritersController.php
│       │   ├── GenresController.php
│       │   ├── LendingController.php
│       │   ├── ReportsController.php
│       │   ├── UsersController.php
│       │   └── BackupController.php
│       ├── Middleware/
│       │   ├── AuthMiddleware.php
│       │   └── AdminMiddleware.php
│       └── Router.php
├── frontend/
│   ├── package.json
│   ├── vite.config.js
│   ├── tailwind.config.js
│   ├── index.html
│   └── src/
│       ├── main.js             # App entry + i18n setup
│       ├── App.vue             # Root layout + nav + lang switcher
│       ├── i18n/
│       │   ├── index.js        # i18n configuration
│       │   ├── en.js           # English translations
│       │   ├── ar.js           # Arabic translations
│       │   └── fr.js           # French translations
│       ├── router/
│       │   └── index.js        # Vue Router + guards
│       ├── services/
│       │   └── api.js          # Axios HTTP client
│       ├── store/
│       │   ├── auth.js         # Authentication store (Pinia)
│       │   └── toast.js        # Toast notifications store
│       └── views/
│           ├── LoginView.vue
│           ├── DashboardView.vue
│           ├── BooksView.vue
│           ├── BookFormView.vue
│           ├── BookDetailView.vue
│           ├── WritersView.vue
│           ├── GenresView.vue
│           ├── LendingView.vue
│           ├── ReportsView.vue
│           ├── UsersView.vue
│           └── BackupView.vue
└── storage/
    └── backups/                # (created by container)
```

---

## 🔌 API Endpoints

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/login` | Login (public) |
| POST | `/api/auth/register` | Register (public) |
| GET | `/api/auth/me` | Get current user |
| PUT | `/api/auth/password` | Change password |

### Books
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/books` | List books (paginated, filterable) |
| GET | `/api/books/{id}` | Get book details |
| POST | `/api/books` | Create book |
| PUT | `/api/books/{id}` | Update book |
| DELETE | `/api/books/{id}` | Delete book |
| POST | `/api/books/{id}/toggle-read` | Toggle read status |
| POST | `/api/books/isbn-lookup` | Lookup by ISBN |

### Writers
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/writers` | List all writers |
| GET | `/api/writers/{id}` | Get writer + books |
| POST | `/api/writers` | Create writer |
| PUT | `/api/writers/{id}` | Update writer |
| DELETE | `/api/writers/{id}` | Delete writer |

### Genres
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/genres` | List all genres |
| POST | `/api/genres` | Create genre |
| PUT | `/api/genres/{id}` | Update genre |
| DELETE | `/api/genres/{id}` | Delete genre (admin) |

### Lending
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/lending` | List lending records |
| POST | `/api/lending` | Lend a book |
| PUT | `/api/lending/{id}` | Update lending record |
| POST | `/api/lending/{id}/return` | Mark as returned |
| DELETE | `/api/lending/{id}` | Delete record |
| GET | `/api/lending/overdue` | Get overdue books |

### Reports
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/reports/summary` | Dashboard stats |
| GET | `/api/reports/by-genre` | Books by genre |
| GET | `/api/reports/by-author` | Books by author |
| GET | `/api/reports/by-year` | Books by year |
| GET | `/api/reports/by-location` | Books by location |
| GET | `/api/reports/export/csv` | Export CSV |
| GET | `/api/reports/export/pdf` | Export PDF |

### Admin — Users
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/users` | List users |
| PUT | `/api/users/{id}` | Update user |
| DELETE | `/api/users/{id}` | Delete user |

### Admin — Backup
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/backup/create` | Create backup |
| GET | `/api/backup/list` | List backups |
| GET | `/api/backup/download/{file}` | Download backup |
| DELETE | `/api/backup/{file}` | Delete backup |

---

## 🔧 Common Operations

### Stop the application
```bash
docker compose down
```

### Reset database (fresh start)
```bash
docker compose down -v
docker compose up -d --build
```

### View logs
```bash
docker compose logs -f              # All services
docker compose logs -f backend      # Backend only
docker compose logs -f frontend     # Frontend only
```

### Rebuild a single service
```bash
docker compose up -d --build frontend
```

### Change API URL (if running on a different host)
```bash
API_URL=http://your-server:8080/api docker compose up -d --build frontend
```

---

## 🔒 Security Notes

This application has been security-hardened against the OWASP Top 10. Below are the administrator responsibilities for production deployment.

### ⚠️ MANDATORY Before Going to Production

1. **Generate strong secrets** — Copy `.env.example` to `.env` and generate all secrets:
   ```bash
   cp .env.example .env
   # Generate each secret:
   sed -i "s|CHANGE_ME_GENERATE_STRONG_PASSWORD|$(openssl rand -base64 24)|" .env
   sed -i "s|CHANGE_ME_GENERATE_WITH_openssl_rand_base64_48|$(openssl rand -base64 48)|" .env
   sed -i "s|CHANGE_ME_GENERATE_WITH_openssl_rand_base64_32|$(openssl rand -base64 32)|" .env
   ```

2. **Change default admin password** — The default admin password is `Admin1234!`. Login and change it immediately via the Settings page.

3. **Set `APP_DEBUG=false`** — Never enable debug mode in production.

4. **Configure CORS origin** — Set `CORS_ORIGIN` in `.env` to your actual frontend URL:
   ```env
   CORS_ORIGIN=https://your-domain.com
   ```

5. **Use HTTPS** — Deploy behind a reverse proxy (nginx/Caddy/Traefik) with TLS certificates. Update `API_URL` to use `https://`.

6. **Database port is NOT exposed by default** — If you need direct DB access for development, uncomment the ports section in `docker-compose.yml`. Never expose in production.

### 🔐 Backup Encryption (Recommended)

Backups are stored as `.sql.gz` files. For production, encrypt them at rest:

```bash
# Generate a GPG key for backup encryption
gpg --gen-key

# Modify docker/backup-cron.sh to pipe through gpg:
# pg_dump ... | gzip | gpg --symmetric --cipher-algo AES256 --batch --passphrase-file /run/secrets/backup_key > ${BACKUP_FILE}.gpg
```

### 🔄 Disable Open Registration (Optional)

Registration is rate-limited (3 per hour per IP) but publicly accessible. To restrict it to admin-only:

1. Edit `backend/routes/api.php`
2. Change:
   ```php
   $router->post('/api/auth/register', [AuthController::class, 'register']);
   ```
   To:
   ```php
   $router->post('/api/auth/register', [AuthController::class, 'register'], [AdminMiddleware::class]);
   ```

### 🛡️ Additional Hardening (Recommended)

| Action | How |
|--------|-----|
| Enable HSTS | Add `Strict-Transport-Security: max-age=31536000; includeSubDomains` in your reverse proxy |
| Rotate JWT secret | Change `JWT_SECRET` quarterly — all users will need to re-login |
| Monitor failed logins | Check container logs: `docker compose logs backend \| grep 401` |
| Update dependencies | Run `composer audit` and `npm audit` weekly |
| Scan container images | Use `trivy image bookoholik_backend` before deploying |
| Restrict registration | Move `/api/auth/register` behind `AdminMiddleware` (see above) |
| Database SSL | Enable SSL in PostgreSQL for encrypted connections between backend and DB |

### 📋 Security Features Implemented

| Feature | Status |
|---------|--------|
| SQL Injection protection (PDO prepared statements) | ✅ |
| XSS prevention (Vue.js auto-escaping + htmlspecialchars) | ✅ |
| CORS restricted to configured origin | ✅ |
| JWT authentication with expiry (8h) | ✅ |
| Rate limiting on login (5/15min) and registration (3/hr) | ✅ |
| Password policy (10+ chars, uppercase, lowercase, number) | ✅ |
| Role-based access control (Admin/User/Viewer) | ✅ |
| Security headers (X-Content-Type-Options, X-Frame-Options, CSP, etc.) | ✅ |
| No hardcoded secrets (fails fast if env not set) | ✅ |
| Command injection prevention (escapeshellarg) | ✅ |
| Container resource limits (memory/CPU) | ✅ |
| Database port not exposed by default | ✅ |
| Backup file permissions restricted (chmod 600) | ✅ |
| Secure .pgpass usage (not env var in process) | ✅ |
| HTML escaping in PDF exports | ✅ |
| Request timeout (30s) | ✅ |
| Token expiry check on frontend | ✅ |
| Generic error messages (no stack traces to client) | ✅ |

### ⚠️ Known Advisory

- `firebase/php-jwt ^6.10` has advisory `PKSA-y2cr-5h3j-g3ys`. This is acknowledged in `composer.json`. Monitor for a patched version and upgrade when available.

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | Vue.js 3, Vite 5, Tailwind CSS 3, Pinia, Vue Router, Vue I18n, Axios |
| Backend | PHP 8.3, Apache, Custom Router, Firebase PHP-JWT, DomPDF |
| Database | PostgreSQL 16 |
| Containerization | Docker / Podman Compose |
| Web Server (Frontend) | Nginx Alpine |
| Backup | pg_dump + cron |

---

## 📄 License

This project is licensed under the [MIT License](LICENSE). You are free to use, modify, and distribute this software. See the [LICENSE](LICENSE) file for details.
