# KiloWhatt Energy Management Platform

KiloWhatt is a full-stack web application designed for Philippine households to manage electricity consumption, calibrate utility rates using real bill data, and receive AI-powered energy efficiency recommendations.

## Project Overview
*   **Purpose:** Energy efficiency consulting and consumption tracking.
*   **Architecture:** 
    *   **Frontend:** Modern, mobile-first responsive web app built with Vanilla JS, Chart.js, and Lucide Icons.
    *   **Backend:** PHP 8.2+ (designed for Laravel Herd) with a PostgreSQL database.
    *   **AI Integration:** Google Gemini 3.1 Flash Lite API for generating energy audit reports.

## Building and Running
*   **Environment:** Ensure PHP 8.2+ and PostgreSQL are installed.
*   **Configuration:** Create a `.env` file from `.env.example` and populate `DB_PASS` and `GEMINI_API_KEY`.
*   **Database:** Run the scripts in `database.sql` to initialize the schema.
*   **Execution:** Serve the root directory using a local web server (e.g., Laravel Herd or `php -S localhost:8000`).

## Development Conventions
*   **Styling:** Follows the "Carbon & Volt" design system defined in `styles.css` (Deep Slate primary, Volt Yellow accent).
*   **Structure:**
    *   `/api`: Backend logic and database connectivity.
    *   `/admin`: Administrative dashboard and management interfaces.
    *   `/assets`: Static assets.
*   **Security:** Password hashing with `bcrypt` (via `password_hash()`), SQL injection protection using PDO, and role-based access control (RBAC).
*   **Responsiveness:** Mobile-first design approach using CSS Grid/Flexbox.
