# Project Audit & Modernization Plan

## 🚨 Critical Architectural Issues

- [ ] **Unified Architecture**: The project is currently split between a legacy monolithic core (`src/Auth.php`, `api/index.php`) and a dormant modern core (`Slim 4`, `src/Services/`, `api/index-new.php`).
    - **Goal**: Fully migrate to the Slim 4 architecture.
- [ ] **Refactor "God Class" (`src/Auth.php`)**: This file violates SRP by handling Auth, Calendar, Attendance, Spaces, and Wiki.
    - [x] Move Calendar logic to `CalendarService`
    - [ ] Move Attendance logic to `AttendanceService` (De-prioritized by user request)
    - [x] Move Wiki logic to `WikiService`
    - [x] Move Space Booking logic to `SpaceService`
    - [x] Implement Space Management Frontend (`spaces.html`)
- [ ] **Replace Monolithic Router**: The `api/index.php` file (2700+ lines) uses a massive switch statement and manual JSON parsing. 
    - **Goal**: Replace with Slim 4 Controllers and Routing Middleware.

## 🛠 Code Quality & Testing

- [x] **Implement Test Suite**: `phpunit.xml` points to non-existent directories (`tests/Unit`, `tests/Integration`).
    - [x] Create `tests/Unit` and `tests/Integration` directories.
    - [ ] Replace ad-hoc scripts (`tests/test_auth.php`, etc.) with proper PHPUnit test classes.
    - [x] Write unit tests for the new Services.
        - *Blocked*: PHPUnit requires `mbstring` extension which is missing in the environment.
- [ ] **Remove Hardcoded Logic**: Move report definitions and business rules from `api/index.php` into configuration files or Service classes.

## 💾 Database & Data Management

- [ ] **Standardize Migrations**: The `database/migrations` folder is sparse.
    - [x] Create a "baseline" migration that captures the entire current schema (users, roles, events, etc.).
    - [ ] Deprecate `init.php` in favor of migration-based setup.
- [ ] **Database Scalability**: Monitor SQLite performance. Consider abstraction to allow seamless switching to MySQL/PostgreSQL if needed (using Doctrine or sticking with PDO abstraction).

## 🔒 Security Improvements

- [ ] **Fix CORS Configuration**: `Access-Control-Allow-Origin: *` is currently set in `api/index.php`.
    - [ ] Restrict allowed origins in the Slim middleware config.
- [ ] **Standardize Token Handling**: Stop using manual token parsing in `api/index.php`.
    - [ ] Use the `AuthMiddleware` and `JwtManager` classes provided in the new architecture.

## ⚡ Frontend & Inconsistencies

- [ ] **Decouple Frontend**: `public/js/apiService.js` relies on `window.location.origin`.
- [ ] **Fix Routing**: `index.php` manually serves HTML files.
    - [ ] Let the web server (Apache/Nginx) or the Slim app handle static file routing more efficiently.

## 📋 Action Plan (Prioritized)

1.  **Resume Migration**: Switch entry point to `api/index-new.php` and finish implementing missing Controllers for Attendance and Wiki.
2.  **Service Extraction**: Extract logic from `Auth.php` into the dedicated Service classes (which already exist but are effectively unused).
3.  **Testing**: Set up the PHPUnit environment to ensure the migration doesn't break existing features.
