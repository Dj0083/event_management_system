# Event Management System

A Laravel 11 application for institution events, competitions, seminars, participation, venue bookings, judging, awards, and certificates.

## Included workflows

- Sign in, sign out, password reset, profile and password changes, and role-protected user administration
- Role-aware dashboard, in-app notifications, and an administrator activity history
- Event covers, event categories, event lifecycle, schedules, and search/filtering
- Venue availability, booking requests, overlap prevention, and approval/rejection
- Seminar topics, lecturer/guest speaker records, and scheduled sessions
- Student records, department records, individual and team registration, approvals, and attendance
- Judge assignment, weighted scoring criteria, score entry, provisional rankings, and locked final results
- Filtered participant name lists with CSV export
- Cash award generation from finalized rankings and distribution tracking
- Refreshment planning, quantity/cost estimates, and distribution tracking
- Staff leave requests with private supporting documents and approval/rejection
- CSV student import, finalized result export for MIS, and logged transfer history
- Guest-teacher leave request letter template, supporting document uploads, and approval/rejection
- CSV reports for events, registrations, attendance, results, awards, venues, refreshments, leave, and seminars

MIS exchange is implemented as a CSV workflow because the institution's actual MIS API or import format has not been provided. The CSV columns are documented on the MIS page.

## Local setup

1. Install the project dependencies with Composer and npm.
2. Copy `.env.example` to `.env`, configure the database, and generate the Laravel application key with `php artisan key:generate`.
3. Run `php artisan migrate` to create the application tables.
4. Run `php artisan evently:make-admin` and choose an administrator name, email, and password (minimum 12 characters).
5. Start the Laravel app with `php artisan serve`.

On the current XAMPP setup, use `C:\xampp\php\php.exe artisan migrate`, `C:\xampp\php\php.exe artisan evently:make-admin`, and `C:\xampp\php\php.exe artisan serve` if `php` is not on your PATH.

The starter environment uses SQLite. Set `DB_CONNECTION=mysql` and configure `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env` to use MySQL instead. Configure a real mail transport in production to deliver password reset messages; local development uses Laravel's log mailer by default.

## Roles and sign-in

Only active `admin` and `staff` accounts can sign in. Public account registration is disabled. Create the first administrator using `php artisan evently:make-admin`; after signing in, the administrator can create staff accounts from **Users & access**. Student participation and judge assignments are managed by staff inside the system; students and judges do not get login accounts.

Open the local sign-in page at `http://127.0.0.1:8000/login` after starting the Laravel development server. For a deployed site, set `APP_URL` in `.env` to that site's actual base URL and clear Laravel's cached configuration (`php artisan config:clear`).
