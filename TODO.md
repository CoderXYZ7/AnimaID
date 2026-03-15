# AnimaID — TODO

## 🔴 Critical / Security

- [x] **Fix CORS**: `api/index.php:13` sets `Access-Control-Allow-Origin: *` — restrict to configured origins from `configDefault.php`
- [x] **Remove hardcoded admin credentials** from `config/configDefault.php` (lines 24–28) — force password change on first login instead
- [x] **Enforce JWT secret from env** — `configDefault.php:16` has a committed fallback secret that would be used in production if `.env` is missing
- [x] **Disable debug mode by default** — `src/Config/ConfigManager.php:69` defaults `APP_DEBUG` to `true`
- [x] **Remove token-in-query-string support** — `api/index.php:96–97` accepts `?token=` which exposes tokens in logs and browser history
- [x] **Implement rate limiting** — configured in `configDefault.php` but never applied anywhere; login endpoint is unprotected against brute force

---

## 🟠 Architecture Migration (Slim 4)

The migration is now complete. All modules run through the Slim 4 entry point.

- [x] **Migrate Attendance module** to `AttendanceController` + `AttendanceService`
- [x] **Migrate Communications module** to `CommunicationsController` + `CommunicationsService`
- [x] **Migrate Media module** to `MediaController` + `MediaService`
- [x] **Migrate Reports module** to `ReportsController` + `ReportsService`
- [x] **Switch entry point** from legacy `api/index.php` to Slim 4 app — migration complete
- [ ] **Delete `src/Auth.php`** — still referenced by `scripts/check_permissions.php`; defer until that script is updated
- [x] **Delete `api/index-legacy.php`** — deleted (had no external references)
- [x] **Delete `database/init.php`** — deleted (only a comment reference in migration file)
- [x] **Retire `src/JWT.php`** — deleted; all JWT handling now via `src/Security/JwtManager.php`

---

## 🟡 Code Quality & Testing

- [x] **Replace ad-hoc test scripts** (`tests/test_auth.php`, etc.) with proper PHPUnit classes
- [x] **Write unit tests for Services** — unit test coverage added for all major services
- [ ] **Write integration tests** covering API endpoints end-to-end
- [x] **Fix password field name inconsistency** — schema uses `password_hash`, `AuthService` uses `password`, `UserService` uses both — pick one
- [ ] **Standardize error responses** — partially done via exception classes in `src/Exceptions/`; HTTP status codes still inconsistent in some places
- [x] **Remove/guard 59+ `console.log` statements** in frontend JS before production builds
- [x] **Add database indexes** on frequently queried columns: `email`, `username`, `status`, dates, and foreign keys

---

## 🟡 Database

- [ ] **Deprecate `init.php`** — `database/init.php` has been deleted; ensure `scripts/check_permissions.php` and any other scripts no longer reference `src/Auth.php`
- [ ] **Consider soft deletes** — current schema uses hard `ON DELETE CASCADE`, no recovery possible
- [ ] **Database abstraction** — SQLite is fine for now but PDO abstraction should allow switching to MySQL/PostgreSQL if needed

---

## 🟢 Frontend

- [x] **Similarity resolver** for `children.html` and `animators.html` — detect and flag near-duplicate entries
- [x] **New child quick-add button** in `attendance.html` for fast insertion without leaving the page
- [x] **Functional counters on dashboard** — previously static/empty
- [x] **Remake wiki top bar** with new AnimaID logo icon
- [x] **Complete `apiService.js`** — all pages now use centralized API service
- [x] **Remove hardcoded production URL** from `public/dashboard.html` — use `config.js.php` consistently on all pages

---

## 🔵 Missing Features

- [x] **Email notifications** — `EmailService` created; SMTP integration complete
- [x] **Audit logging** — `AuditService` + `AuditMiddleware` created; audit migration added; middleware registered in `api/index.php`
- [ ] **Two-factor authentication** — present as a feature flag, not implemented
