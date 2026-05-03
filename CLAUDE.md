# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands
- **Environment**: PHP 8.2+, PostgreSQL
- **Setup**: `composer install`
- **Configuration**: Copy `.env.example` to `.env` and configure `DB_*` and `GEMINI_API_KEY`.
- **Database**: Run `database.sql` against your PostgreSQL instance to initialize the schema.
- **Run**: `php -S localhost:8000` (or use Laravel Herd)
- **Tests**: No automated test suite currently exists. Manual testing of PHP endpoints via `test_ai.php` or direct API calls.

## Architecture & Structure
### Frontend
- **Entry Point**: `index.html` + `index.js` + `styles.css`.
- **State Management**: Handled in `index.js` via a global `state` object.
- **Icons**: Lucide Icons (CDN).
- **UI Components**: Custom "Carbon & Volt" design system in `styles.css`.

### Backend (PHP)
- **API**: Found in `/api/`.
  - `db.php`: Database connection (PDO).
  - `generate.php`: Handles Gemini AI report generation.
  - `session_check.php`: Auth & session management.
- **Admin**: Found in `/admin/` for user and appliance management.
- **PDF Generation**: Uses `dompdf` (vendor) via `api/export_pdf.php`.

### Database Schema (PostgreSQL)
- `users`: Authentication and roles (user, admin).
- `appliance_presets`: Reference data for common appliances.
- `user_appliances`: User-specific appliance entries.
- `analysis_reports`: History of AI-generated reports.

## AI Integration (Lektric)
- Uses **Google Gemini 1.5 Flash** (via `api/generate.php`).
- Prompting logic defines "Lektric" as a Senior Energy Efficiency Consultant.
- Returns HTML reports with embedded CSS and hidden JSON for Chart.js.
