# Laravel CSV Upload & Duplicate Detection API

A Laravel 12 RESTful API for uploading CSV files, validating and parsing their contents, separating unique and duplicate entries, and exporting filtered data in CSV format. Built for efficiency, data integrity, and ease of testing and integration.

---

## 🚀 Features

- API endpoint for CSV upload and parsing (including strong validation)
- Automatic detection/separation of duplicates
- Unique and duplicate records stored in separate tables
- Batch processing for large files
- Export endpoints for all, unique, or duplicate records (CSV download)
- Robust error handling for invalid rows and uploads
- Automated PHPUnit feature tests
- Well-commented controller code and clear architecture

---

## 🛠️ Prerequisites

- PHP >= 8.1
- Composer >= 2.x
- Database: MySQL 8+ or MariaDB (adjust `.env`)
- Node.js >= 18 (only needed for frontend asset compilation)
- [Optional] Postman for API testing

---

## 📦 Installation

```bash
git clone git@github.com:prateeik/laravel-csv-api.git laravel-csv-api
cd laravel-csv-api
composer install
cp .env.example .env
```

Edit `.env` for your database credentials:

```bash
php artisan key:generate
```

Optional (for frontend assets):

```bash
npm install && npm run dev
```

---

## 🗄️ Database Setup & Migrations

```bash
php artisan migrate
```

If you change migrations for foreign key constraints:

```bash
php artisan migrate:refresh
```

---

## ▶️ Running the Application

```bash
php artisan serve
```

API base URL will be `http://127.0.0.1:8000`

---

## ✅ Running the Tests

```bash
php artisan test
# or
vendor/bin/phpunit
# or
php artisan test --filter=CSVUploadControllerTest
```

Test coverage includes CSV uploads, validation, duplicate handling, and response correctness.

---

## 📚 API Documentation

### Endpoints Overview

| Method | Endpoint                   | Description                                       |
|--------|----------------------------|---------------------------------------------------|
| POST   | `/api/csv/upload`          | Upload and process a CSV file                     |
| GET    | `/api/csv/export?type=`    | Export data as CSV (type: all, unique, duplicate) |

---

### CSV Upload

- **POST** `/api/csv/upload`
- **Body:** `file` (form-data, CSV file)
- **Response:** Status, summary counts, and arrays of unique, duplicate, and invalid rows.

Example JSON response:

```json
{
  "status": "success",
  "file_stored": "uploads/csv/abc123.csv",
  "summary": {
    "rows_processed": 6,
    "unique_count": 3,
    "duplicate_count": 2,
    "invalid_count": 1
  },
  "unique_records": [ ... ],
  "duplicates": [ ... ],
  "invalid_rows": [ ... ]
}
```

---

### CSV Export

- **GET** `/api/csv/export?type=all|unique|duplicates`
- **type:** (optional) filter parameter
- Returns a downloadable filtered CSV.

---

## 🧪 Using Postman

- Send a `POST` request to `/api/csv/upload` with a `.csv` file as `file` in **form-data**.
- Download filtered data using a `GET` request:
  - `/api/csv/export?type=duplicates`
  - `/api/csv/export?type=unique`
  - `/api/csv/export?type=all`

---

## 📝 Code Comments

- Every controller method and complex step (file parsing, batch inserting, duplicate logic) is thoroughly commented.
- Check `app/Http/Controllers/CSVUploadController.php` for inline explanations of every critical operation.

---

## 🏗️ Architecture Decisions & Trade-offs

- **Batch insert:** For performance and scalability over row-by-row inserts.
- **Separate duplicates table:** Ensures auditability and clean main data table.
- **Streamed exports:** Efficient and memory-safe even for very large datasets.
- **Storage facade everywhere:** Supports testing and environment independence.
- **Extensive feature testing:** Catches regressions and clarifies integration points.

---

## 🤝 Contributing

1. Fork this repository.
2. Create a new branch (`git checkout -b feature/your-feature`).
3. Commit and push your code.
4. Open a Pull Request.

---
