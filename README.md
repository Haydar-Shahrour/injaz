# إنجاز - Injaz Platform

> البوابة الإلكترونية للخدمات الرسمية – جمهورية لبنان

A full-stack PHP web application for managing government service requests (identity cards, passports, judicial records, driving licenses, certifications, and more). Citizens submit requests online and track them via a unique Request ID. Employees manage, approve, or reject requests through a secure admin dashboard with WhatsApp notification integration.

---

## Features

### For Citizens
- **Service Request Submission** – Browse service categories, upload required documents, and submit requests.
- **Request ID Tracking** – Each submission generates a unique Request ID displayed upon success.
- **Status Tracking Page** – Look up request status by Request ID or phone number at `status.php`.

### For Employees
- **Secure Login Portal** – Session-based authentication at `login.php`.
- **Admin Dashboard** – View all submitted requests with full details and uploaded documents.
- **Approve / Reject** – Mark requests as accomplished or rejected with one click.
- **WhatsApp Notifications** – Automatically opens a WhatsApp message to notify the citizen upon approval or rejection.

---

## Tech Stack

| Layer       | Technology                  |
|-------------|-----------------------------|
| Backend     | PHP 8 (Apache)              |
| Database    | MySQL 8.0                   |
| Frontend    | HTML, CSS, Vanilla JS (RTL) |
| Deployment  | Docker & Docker Compose     |

---

## Getting Started

### Prerequisites
- [Docker](https://www.docker.com/) & Docker Compose installed.

### Run Locally

```bash
git clone https://github.com/Haydar-Shahrour/injaz.git
cd injaz
docker-compose up -d
```

The app will be available at **http://localhost:8000**.

### Default Admin Accounts

| Username | Password   |
|----------|------------|
| admin    | admin123   | 

---

## Project Structure

```
├── index.php            # Main landing page & service request form
├── login.php            # Employee login portal
├── admin.php            # Employee dashboard (protected)
├── status.php           # Citizen request tracking page
├── init.sql             # Database schema & seed data
├── Dockerfile           # PHP/Apache container config
├── docker-compose.yml   # Multi-container orchestration
├── uploads/             # Uploaded documents (gitignored)
└── .gitignore
```

---

## License

This project is for internal use by Injaz.
