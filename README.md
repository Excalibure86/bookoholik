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
   git clone <repository-url> home_library
   cd home_library
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

   | Field    | Value      |
   |----------|------------|
   | Username | `admin`    |
   | Password | `password` |

   > ⚠️ **Change the default password immediately in production!**

---

## 📁 Project Structure

```
home_library/
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

For production deployment:

1. **Change default credentials** — Update `DB_PASSWORD` and the admin password after first login
2. **Set a strong JWT secret** — `JWT_SECRET=<random-64-char-string>`
3. **Use HTTPS** — Put a reverse proxy (nginx/Caddy/Traefik) in front with TLS
4. **Restrict database port** — Remove `ports: - "5432:5432"` from docker-compose.yml if external access isn't needed
5. **Set `APP_DEBUG=false`** — Never enable debug in production

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

This project is for personal use. All rights reserved.
