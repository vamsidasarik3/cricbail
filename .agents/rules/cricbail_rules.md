# CRICBAIL Project Rules & Architecture Guidelines

## 1. Technology Stack & Constraints
- **Framework**: Laravel (Standard MVC architecture).
- **Template Engine**: Laravel Blade templates (`resources/views`).
- **Database**: MySQL / MariaDB (hosted via local XAMPP).
- **Styling**: Bootstrap 5 (CSS & JS components).
- **Client Scripting**: jQuery and Vanilla JavaScript only when interactive behavior is needed.
- **Strict Architecture Restrictions**:
  - NO Single Page Application (SPA) architecture.
  - NO React, Vue, Livewire, Inertia, or Tailwind CSS.
  - NO unnecessary frontend build tooling (Vite/npm build pipelines should be avoided or kept minimal; prefer direct static assets in `public/` linked via Blade layouts).

## 2. Server & Environment Setup
- **Web Server**: Apache 2.4 (XAMPP).
- **Port / Host**: Served on port `8084` (`http://cricbail.vkrishna.in:8084/` or `http://cricbail.vkrshna.in:8084/`).
- **Document Root**: `G:/xampp/htdocs/cricbail/public_html/public`.
- **PHP Version**: PHP 8.4.x with standard extensions (`pdo_mysql`, `mbstring`, `curl`, `openssl`, `tokenizer`, `xml`, etc.).
- **URL Rewriting**: Managed via standard Laravel `.htaccess` in the `public/` directory with `AllowOverride All`.

## 3. Database Safety & Integrity
- **Database Names**: Verify exact database target (`cricbail` vs existing legacy `cric_bail`).
- **No Destructive Operations**: Never run `migrate:fresh`, `migrate:reset`, or `DROP DATABASE` without explicit instruction.
- **Migration Best Practice**: Always use structured Laravel migrations with appropriate foreign keys, indexes, and soft deletes where relevant.
- **Eloquent Models**: Use Eloquent models with guarded/fillable attributes properly defined.

## 4. Coding Conventions & Best Practices
- **Controllers**: Thin controllers, adhering to standard resourceful methods (`index`, `show`, `create`, `store`, `edit`, `update`, `destroy`).
- **Requests**: Use Laravel Form Requests for request validation rather than inline controller validation for complex forms.
- **Blade Views**:
  - Keep a clean layout hierarchy: `resources/views/layouts/app.blade.php`, with partials for navigation, footer, alerts, and modals.
  - Semantic HTML5 structure with Bootstrap 5 utility and component classes.
  - Clean separation of page-specific scripts and styles using `@push('scripts')` and `@push('styles')`.
- **Security Practices**:
  - Always enforce CSRF token protection (`@csrf`) on all POST/PUT/DELETE forms.
  - Use prepared statements (standard Eloquent / Query Builder) to prevent SQL injection.
  - Sanitize and escape all user outputs (`{{ $var }}`).
  - Enforce authentication and role middleware on administrative routes.

## 5. Testing & Verification
- Test all web routes through browser / HTTP requests targeting port 8084.
- Validate responsive design across standard viewports (mobile, tablet, desktop) using Bootstrap 5 grid.
- Ensure all forms have client-side validation and server-side validation error displays.
