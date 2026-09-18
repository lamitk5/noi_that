# Implementation Plan - Phase 3: User Profile + Role/Permission + Admin Shell

Phase 3 introduces customer profile viewing/editing, role-based authorization (customer vs admin), and an Admin Dashboard shell.

## User Schema & Role Audit
- **Users Table Schema:**
  - `id`: bigint unsigned, PK
  - `name`: string
  - `email`: string, unique
  - `password`: string, hashed
  - `role`: string, default `'customer'`, index (created in `2026_09_14_094811_add_role_to_users_table.php`)
  - `email_verified_at`: timestamp, nullable
  - `remember_token`: string, nullable
  - `created_at`, `updated_at`
- **Role Architecture:**
  - Roles: `customer` (default) and `admin`.
  - Stored directly in `users.role`.
  - Method on `User`: `isAdmin(): bool => $this->role === 'admin'`.
  - Migration added: NONE (role column already exists).
- **Profile Edit Fields:**
  - Strictly `name` and `email`.
  - Role escalation blocked via explicit controller whitelisting.

## Proposed Changes

### Models & Middleware
- [`app/Models/User.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/app/Models/User.php):
  - Add `isAdmin(): bool` helper.
- [`database/factories/UserFactory.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/database/factories/UserFactory.php):
  - Add `admin()` state.
- [NEW] [`app/Http/Middleware/EnsureUserIsAdmin.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/app/Http/Middleware/EnsureUserIsAdmin.php):
  - If user is not authenticated or not admin: `abort(403)`.
- [`bootstrap/app.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/bootstrap/app.php):
  - Register middleware alias: `'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class`.

### Routes & Controllers
- [`routes/web.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/routes/web.php):
  - In `middleware('auth')`:
    - `GET /tai-khoan/chinh-sua` -> `AccountController::edit` (named `account.edit`)
    - `PATCH /tai-khoan` -> `AccountController::update` (named `account.update`)
  - In `middleware(['auth', 'admin'])->prefix('admin')->name('admin.')`:
    - `GET /` -> `Admin\DashboardController::index` (named `dashboard`)
- [`app/Http/Controllers/AccountController.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/app/Http/Controllers/AccountController.php):
  - Implement `edit()` and `update()`.
- [NEW] [`app/Http/Controllers/Admin/DashboardController.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/app/Http/Controllers/Admin/DashboardController.php):
  - Implement `index()` returning `admin.dashboard`.

### Views
- [NEW] [`resources/views/account/edit.blade.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/resources/views/account/edit.blade.php):
  - Edit profile form with semantic tokens and validation errors display.
- [`resources/views/account/index.blade.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/resources/views/account/index.blade.php):
  - Add "Chỉnh sửa hồ sơ" CTA button and flash message display.
- [NEW] [`resources/views/layouts/admin.blade.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/resources/views/layouts/admin.blade.php):
  - Clean admin shell layout with sidebar and topbar using semantic theme tokens.
- [NEW] [`resources/views/admin/dashboard.blade.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/resources/views/admin/dashboard.blade.php):
  - Module placeholder cards for Products, Categories, Inventory, Orders, Customers, Reviews, Vouchers, CMS, Analytics without broken links.
- [`resources/views/layouts/app.blade.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/resources/views/layouts/app.blade.php):
  - Conditionally display "Trang quản trị" link in customer Gear dropdown for admin users.

### Tests
- [`tests/Feature/AccountTest.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/tests/Feature/AccountTest.php):
  - Profile edit tests (TDD: guest protection, rendering edit screen, updating name and email, uniqueness validation, role escalation rejection, password protection).
- [NEW] [`tests/Feature/Admin/AdminAccessTest.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/tests/Feature/Admin/AdminAccessTest.php):
  - Guest redirect to login on `/admin`.
  - Customer receives 403 on `/admin`.
  - Admin receives 200 on `/admin`.
  - Admin dashboard title rendered.
- [`tests/Feature/Auth/RegistrationTest.php`](file:///c:/Users/haida/Downloads/bài%20tập%20năm%20tư/PTHT%20thương%20mại%20điện%20tử_HẢi/Web%20nội%20thất/noi_that_feature_dang/tests/Feature/Auth/RegistrationTest.php):
  - Registering with `role=admin` in payload still sets `role=customer`.

## Verification Plan
1. `php artisan test tests/Feature/AccountTest.php` (RED -> GREEN)
2. `php artisan test tests/Feature/Admin/AdminAccessTest.php` (RED -> GREEN)
3. Full test suite: `php artisan test` (73 baseline + new tests PASS, 0 fail)
4. `npm run build` PASS
5. `git diff --check` PASS
6. Commit and push to `origin/feature/dang`
