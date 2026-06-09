# PROJECT REPORT: SECURITY ENHANCEMENT

* **GROUP MEMBER:** 
FOFANA MAMOUDOU KADER (1725503)
IRFAN HAKEEM BIN KHAIRUDIN (2318729)
ARIFF ROSTAM HAIKQAL BIN SUBAHIR (2319887)
MUHAMMAD SYAQEER IMAN BIN ZAINAL (2318495)
MOHAMMAD MUKHRIZ BIN MISBAHUDDIN (2219587)

* **PROJECT TITLE:** COURTNEST - COURT BOOKING SYSTEM

---

## a. Introduction of Web Application
The developed web application is an online Court Booking System designed to allow users to easily view real-time court availability, reserve time slots, and manage bookings for various sports facilities. The system streamlines the reservation process, moving away from conventional manual tracking to a seamless and automated digital platform accessible to all users.

## b. Objective of the Enhancements
The main goal of these security updates is to protect our website and users from attackers by adding strong security checks at every step. 

The specific goals are:
* **1. Better Form Input Checking (Input Validation):** To make sure users cannot type bad data or trick the system. We added strong password rules (at least 12 characters with symbols and capitals) and blocked users from changing the browser HTML to book past dates, negative hours, or dates too far in the future. We use both browser and server checks to double-verify everything.
* **2. Stronger Login Security (Authentication):** To stop attackers from guessing user passwords. The system now blocks a user's IP address after 5 failed login attempts. We also make sure inactive sessions close automatically and password reset links expire after 60 minutes.
* **3. Restricting Access (Authorization):** To make sure regular visitors cannot look at private pages. We use Laravel code to block unlogged-in users from the dashboard. We also fixed IDOR bugs so a user cannot change numbers in the web link (like changing `/profile/2` to `/profile/3`) to spy on another person's account.
* **4. Stopping Web Attacks (XSS and CSRF):** To stop bad scripts from breaking our site. We use special Laravel code `{{ }}` to safely display data and protect names from running bad codes. We also use `@csrf` tokens on every form so attackers cannot fake a user's button clicks.
* **5. Database Protection:** To keep our database safe. We use Laravel Eloquent instead of raw database commands so attackers cannot inject bad SQL commands to steal information. We also blocked users from forcing updates on secret database columns.
* **6. Server and File Protection:** To stop our private settings files from leaking online. We point our web server only to the `public/` folder, hide our secret database password file (`.env`), and turn off error debug messages so visitors never see our backend code structure.

---

## c. Web Application Security Enhancements

* **I. Input Validation:**

* **I. Input Validation:**

#### ✅ Input Validation Features

The application implements input validation mechanisms to ensure that users can only submit correct, safe, and logical data. Validation is applied on both the client side and server side. Client-side validation helps guide users before submission, while server-side validation ensures that manipulated or invalid requests are rejected before being processed by the system.

---

1. Strong Registration Validation

* **Implementation:** `app/Actions/Fortify/CreateNewUser.php`
* **Mechanism:** The registration form validates user details such as name, email, phone number, and password before a new account is created.
* **Behavior:** Users must provide a valid name, valid email address, phone number, and strong password. The password must be at least **12 characters**, contain both uppercase and lowercase letters, and include at least one special character.

**Code snippet:**

```php
Validator::make($input, [
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
    'phone_number' => ['required', 'string', 'max:20'],
    'password' => [
        'required',
        'string',
        'min:12',
        'confirmed',
        'regex:/[a-z]/',
        'regex:/[A-Z]/',
        'regex:/[^A-Za-z0-9]/',
    ],
    'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
], [
    'password.min' => 'Password must be at least 12 characters.',
    'password.regex' => 'Password must contain uppercase letter, lowercase letter, and special character.',
])->validate();
```

---

2. Booking Date Range Validation

* **Implementation:** `resources/views/bookings/create.blade.php`
* **Mechanism:** The booking form uses HTML5 date validation attributes to restrict the date selection.
* **Behavior:** Users cannot select past dates. The booking calendar is also limited to a maximum of **3 months in advance** to prevent users from booking illogical dates far into the future.

**Code snippet:**

```html
<input type="date" 
       name="booking_date"
       id="booking_date"
       required
       min="{{ date('Y-m-d') }}"
       max="{{ now()->addMonths(3)->toDateString() }}"
       value="{{ date('Y-m-d') }}">
```

---

3. Required Booking Form Fields

* **Implementation:** `resources/views/bookings/create.blade.php`
* **Mechanism:** HTML5 `required` attributes are used on important booking form fields.
* **Behavior:** Users must select a court, duration, and start time before the form can be submitted.

**Code snippet:**

```html
<select name="court_id" id="court_id" required>
```

```html
<select name="duration" id="duration" required>
```

```html
<select name="start_time" id="start_time" required>
```

---

4. Passed and Booked Slot Validation

* **Implementation:** `resources/views/bookings/create.blade.php`
* **Mechanism:** JavaScript is used to check unavailable time slots and label them clearly.
* **Behavior:** The system separates unavailable slots into two categories. **Passed** means the time slot has already passed today, while **Booked** means the time slot has already been reserved by another user.

**Code snippet:**

```javascript
const isPast = (selectedDate === todayStr && optionHour <= currentHour);
const selectedEndHour = optionHour + selectedDuration;

const isBooked = bookedSlots.some(slot => {
    if (slot.court_id != selectedCourt || slot.booking_date !== selectedDate) return false;

    const bookedStartHour = parseInt(slot.start_hour);
    const bookedEndHour = bookedStartHour + parseInt(slot.duration);

    return optionHour < bookedEndHour && selectedEndHour > bookedStartHour;
});

if (!option.dataset.originalText) {
    option.dataset.originalText = option.text;
}

option.text = option.dataset.originalText;

if (isPast) {
    option.disabled = true;
    option.text += ' (Passed)';
} else if (isBooked) {
    option.disabled = true;
    option.text += ' (Booked)';
} else {
    option.disabled = false;
}
```

---

5. Server-Side Booking Validation

* **Implementation:** `app/Http/Controllers/BookingController.php`
* **Mechanism:** Laravel `$request->validate()` is used to validate booking data on the backend before the booking is processed.
* **Behavior:** Even if users manipulate the HTML form or send manual requests, the server will reject invalid court IDs, past dates, dates more than 3 months ahead, invalid start times, negative durations, and durations longer than 3 hours.

**Code snippet:**

```php
$request->validate([
    'court_id' => 'required|integer|exists:courts,id',
    'booking_date' => 'required|date|after_or_equal:today|before_or_equal:' . now()->addMonths(3)->toDateString(),
    'start_time' => 'required|date_format:H:i:s|in:08:00:00,09:00:00,10:00:00,11:00:00,12:00:00,13:00:00,14:00:00,15:00:00,16:00:00,17:00:00,18:00:00,19:00:00,20:00:00,21:00:00,22:00:00',
    'duration' => 'required|integer|min:1|max:3',
]);
```

---

6. Operating Hours Validation

* **Implementation:** `app/Http/Controllers/BookingController.php`
* **Mechanism:** Additional backend validation checks whether the selected booking duration exceeds the court operating hours.
* **Behavior:** Users cannot book a time slot that continues beyond the allowed closing time. For example, a booking at **10:00 PM for 3 hours** will be rejected because it exceeds the operating hours.

**Code snippet:**

```php
$latestEndTime = Carbon::parse('23:00:00');

if ($newEnd->gt($latestEndTime)) {
    return back()->withErrors([
        'error' => 'Booking cannot exceed the court operating hours.'
    ])->withInput();
}
```

---

7. Past Time Validation

* **Implementation:** `app/Http/Controllers/BookingController.php`
* **Mechanism:** The system compares the selected booking time with the current time if the booking date is today.
* **Behavior:** Users cannot book a time slot that has already passed on the current day.

**Code snippet:**

```php
if ($request->booking_date == now()->toDateString()) {
    if ($newStart->lt(now())) {
        return back()->withErrors([
            'error' => 'You cannot book a time slot that has already passed today.'
        ])->withInput();
    }
}
```

---

8. Overlapping Booking Validation

* **Implementation:** `app/Http/Controllers/BookingController.php`
* **Mechanism:** The system checks existing bookings in the database before confirming a new booking.
* **Behavior:** If another user has already booked the same court during the selected time range, the system rejects the request to prevent double booking.

**Code snippet:**

```php
$overlap = Booking::where('court_id', $request->court_id)
    ->where('booking_date', $request->booking_date)
    ->where('status', '!=', 'cancelled')
    ->get()
    ->filter(function ($existing) use ($newStart, $newEnd) {
        $existingStart = Carbon::parse($existing->start_time);
        $existingEnd = (clone $existingStart)->addHours((int) $existing->duration);

        return $newStart->lt($existingEnd) && $newEnd->gt($existingStart);
    })->isNotEmpty();

if ($overlap) {
    return back()->withErrors([
        'error' => 'Booked! The court is already reserved during this time range.'
    ])->withInput();
}
```

---

#### Summary

The input validation enhancement improves the security and reliability of Courtnest by validating user input before it is accepted by the system. The main improvements include strong registration password rules, required form fields, date range restriction, maximum 3-month booking limit, valid start time checking, duration limits, operating hour validation, past time prevention, and overlapping booking prevention.



* **II. Authentication:**
#### 🔒 Security & Authentication Features
The application implments security mechanism built upon Laravel Fortify to protect user data and prevent unauthorized access. For session lifetime, user must set duration in their .env file first.

1. Brute-Force Login Protection
* **Implementation:** `app/Providers/FortifyServiceProvider.php`
* **Mechanism:** To prevent automated brute-force attacks, the application implements strict IP-based and credential-based rate limiting. 
* **Behavior:** If a user or malicious actor attempts to log in and fails **5 consecutive times**, their IP address/account is automatically blocked from further attempts for a designated cooldown period (1 minute by default).

**Code snippet:**
```php
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
```

2. Automated Session Management
* **Implementation:** `.env` (`SESSION_LIFETIME`)
* **Mechanism:** To safeguard user accounts against session hijacking or unauthorized access on shared devices, the application enforces automated session expiration.
* **Behavior:** User sessions are continuously monitored for inactivity. If a user remains idle for a predefined period (e.g., 10 minutes), the session token is automatically invalidated, forcing the user to re-authenticate upon their next action.

3. Secure Password Reset Expiration
* **Implementation:** `config/auth.php` (`passwords.users.expire`)
* **Mechanism:** To mitigate the risk of intercepted email links or long-term token exposure, password reset tokens are tightly constrained by time.
* **Behavior:** When a user requests a password reset link, the generated token remains valid for a strict window of **2 minutes**. Any attempt to use the link after this period will be rejected, requiring a fresh request.

**Code snippet:**
```php
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 2,
            'throttle' => 2,
        ],
    ],
```


* **III. Authorization:**
#### A. Initial Security Measures
Before making any changes, the system was already built with two strong, built-in walls to block attackers:

**1. Route Protection (Login Guard):** We wrapped all important pages—like the Dashboard, Checkout, and Payment pages—inside Laravel Jetstream’s login protection (auth:sanctum). If someone who is not logged in tries to type http://localhost:8000/dashboard directly into their browser, the website blocks them instantly and forces them to go to the login page.

**Code snippet:**
```php
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Protected routes go here
});
```
2. Automatic Identity Binding (Basic IDOR Prevention): When saving a booking to the database, the code completely ignores any identity information coming from the browser. Instead, it locks the booking to whoever is currently logged in using Auth::id():

Code snippet:

```php
Booking::create([
    'user_id' => Auth::id(), // Pulls directly from the secure server session
    'court_id' => $data['court_id'],
    ...
]);
```
B. New Security Enhancements
To make the system bulletproof against more advanced tricks (like a user tampering with data mid-transaction or trying to look at someone else's checkout session), we added three explicit upgrades to BookingController.php:

1. Stamping the Session Owner (In the store function)

Code snippet:

'user_session_id' => Auth::id(), // Save logged-in user ID to session for validation, inside the temporary booking data.
Explanation: The very second a user selects a court slot, we don't just temporarily save the court and time. We also write down their exact User ID into that temporary server memory pool. This securely attaches that specific checkout process to that specific logged-in user from the start.

2. The Checkout ID Check (In the checkout function)
What we added: An if statement that checks if the current user matches the stamped owner, otherwise it triggers a 403 Access Denied.

Code snippet:

```php
// Check if the session belongs to the currently logged-in user
if ($booking['user_session_id'] !== Auth::id()) {
    session()->forget('pending_booking');
    abort(403, 'Unauthorized action: Session mismatch.');
}
```
Explanation: Before showing the checkout summary page, the server checks: "Is the person trying to look at this page the exact same person who started the booking?" If a hacker tries to sneak into or view someone else's active checkout session, the server instantly clears the memory and shows an "Unauthorized Action" error page.

3. Double-Check and Price Tampering Protection (In the pay function)
What we added: A second identity check, plus a fresh database lookup (Court::findOrFail) to recalculate the price right before saving.

Code snippet:

```php
// Verify the checkout session belongs to the logged-in user
if ($data['user_session_id'] !== Auth::id()) {
    session()->forget('pending_booking');
    abort(403, 'Unauthorized action.');
```

// Fresh database lookup to calculate the correct price right before saving
$court = Court::findOrFail($data['court_id']);
$validatedPrice = $court->price_per_hour * (int)$data['duration'];
Explanation: At the final step when the user pays, we double-check their identity one last time for safety. At the same time, instead of blindly trusting the price saved in the session memory, the server reaches into the database to check the real, current price of the court. This means even if someone tries to hack the system memory to change the court price to RM0, the server recalculates it automatically using the real database values, completely stopping payment fraud.


* **IV. XSS and CSRF:**
Cross-Site Scripting (XSS) allows attackers to inject malicious scripts into
pages viewed by other users. Cross-Site Request Forgery (CSRF) tricks an
authenticated user's browser into submitting forged requests to the application
without their knowledge. The following enhancements were applied to the
**CourtNest** sports court booking application to address both attack vectors.

### XSS (Cross-Site Scripting) Prevention

#### Enhancement 1 — Blade `{{ }}` Escaped Output on All User-Controlled Data

Laravel's Blade engine automatically HTML-encodes every value rendered through
`{{ }}`, converting characters like `<`, `>`, `"`, `'`, and `&` into their safe
HTML entity equivalents. This prevents stored and reflected XSS — even if an
attacker stored `<script>alert(1)</script>` as their name, Blade would render it
as the harmless literal string `&lt;script&gt;alert(1)&lt;/script&gt;`.

The unsafe alternative `{!! !!}` (raw, unescaped output) was audited across all
views and confirmed to be absent from the entire application.

**`resources/views/dashboard.blade.php` — User profile and all booking data:**
```blade
{{-- User identity --}}
{{ explode(' ', auth()->user()->name)[0] ?? 'Guest' }}
{{ auth()->user()->email ?? '' }}
{{ auth()->user()->phone_number ?? '+60 --' }}

{{-- Booking table rows — all database fields escaped --}}
{{ $booking->court->sport_type ?? 'Sport' }}
{{ $booking->court->name ?? 'Court' }}
{{ $booking->id }}
{{ \Carbon\Carbon::parse($booking->booking_date)->format('d/m/Y') }}
{{ \Carbon\Carbon::parse($booking->start_time)->format('h:i A') }}
{{ $booking->duration }}
{{ number_format($booking->total_price, 2) }}
```

**`resources/views/profiles/show.blade.php` — Profile page display:**
```blade
{{ auth()->user()->name ?? 'Guest' }}
{{ auth()->user()->email ?? '—' }}
{{ auth()->user()->phone_number ?? '—' }}
```

**`resources/views/bookings/checkout.blade.php` — Payment summary:**
```blade
{{ $booking->court_name }}
{{ \Carbon\Carbon::parse($booking->booking_date)->format('D, d M Y') }}
{{ \Carbon\Carbon::parse($booking->start_time)->format('h:i A') }}
{{ $booking->duration }}
{{ number_format($booking->total_price, 2) }}
```

---

#### Enhancement 2 — Safe JavaScript Context Output Using `json_encode` with Hex-Escape Flags

**File:** `resources/views/bookings/create.blade.php`

A XSS vulnerability existed where booked slot data from the server was embedded
into an HTML attribute using `@json()`. The `@json()` directive calls PHP's
`json_encode()` with default flags, which does **not** HTML-encode special
characters for attribute context. A value containing single quotes (`'`) or angle
brackets could break out of the attribute boundary and inject into the page.

**Before (Original — Vulnerable):**
```blade
<div id="booking-data-bridge" data-booked='@json($bookedSlots)'></div>
```

**After (Enhanced — Secure):**
```blade
<div id="booking-data-bridge" data-booked="{{ json_encode($bookedSlots, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"></div>
```

The `JSON_HEX_TAG`, `JSON_HEX_APOS`, `JSON_HEX_QUOT`, and `JSON_HEX_AMP` flags
force PHP to hex-encode `<`, `>`, `'`, `"`, and `&` as Unicode escape sequences
(`\u003C`, `\u0027`, etc.), making the output safe for use inside HTML attributes
regardless of what values the `$bookedSlots` collection contains. The JavaScript
reading side (`JSON.parse(bridge.getAttribute('data-booked'))`) is unaffected as
it decodes the JSON normally.

---

### CSRF (Cross-Site Request Forgery) Prevention

#### Enhancement 3 — `@csrf` Directive in All State-Changing Forms

The `@csrf` Blade directive inserts a hidden `_token` field into every form.
Laravel's CSRF middleware validates this token on every incoming POST, PATCH,
and DELETE request, comparing it against the value stored in the user's server-side
session. Since an attacker's forged form on a third-party domain cannot read the
victim's session token (blocked by the browser's Same-Origin Policy), forged
requests are automatically rejected with a `419 Page Expired` response.

All four forms in the application were verified to include `@csrf`:

**`resources/views/bookings/create.blade.php` — Booking submission (POST):**
```blade
<form action="{{ route('bookings.store') }}" method="POST" id="bookingForm">
    @csrf
    {{-- court_id, booking_date, start_time, duration --}}
</form>
```

**`resources/views/bookings/checkout.blade.php` — Payment confirmation (POST):**
```blade
<form action="{{ route('bookings.pay') }}" method="POST">
    @csrf
    {{-- payment_method radio inputs --}}
</form>
```

**`resources/views/auth/login.blade.php` — User login (POST):**
```blade
<form method="POST" action="{{ route('login') }}">
    @csrf
    {{-- email, password --}}
</form>
```

**`resources/views/auth/register.blade.php` — User registration (POST):**
```blade
<form method="POST" action="{{ route('register') }}">
    @csrf
    {{-- name, phone_number, email, password, password_confirmation --}}
</form>
```

#### Enhancement 4 — Explicit CSRF Middleware Configuration

**File:** `app/Http/Middleware/VerifyCsrfToken.php`

An explicit `VerifyCsrfToken` middleware class was created to formally define
which routes are subject to CSRF verification. The `$except` array is kept
intentionally empty, confirming that **no routes bypass CSRF protection**.

```php
<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * URIs excluded from CSRF verification.
     * Empty — all POST, PATCH, and DELETE routes are CSRF-protected.
     *
     * @var array
     */
    protected $except = [
        //
    ];
}
```

A common developer mistake is adding routes to `$except` to quickly fix `419`
errors during development and forgetting to remove them before deployment.
By maintaining this file with an empty `$except` array, the project explicitly
documents its zero-exclusion CSRF policy.

* **V. Database Security Principles:**
* The Problem: SQL Injection occurs when user input is directly concatenated into SQL queries, allowing attackers to execute malicious database commands.

**Before (Vulnerable approach):**
```php
$results = DB::select("SELECT * FROM users WHERE id = $userId");
// Attacker could pass: ?id=1; DROP TABLE users; --
```

**After (Enhanced — Eloquent ORM with PDO parameter binding):**

All database queries in Courtnest use Laravel's **Eloquent ORM**, which internally uses **PDO prepared statements**. User input is always treated as data, never as executable SQL code.

```php
// BookingController.php — All queries use Eloquent

// Retrieving courts and bookings
$courts = Court::all();
$bookedSlots = Booking::where('booking_date', '>=', now()->toDateString())
    ->where('status', '!=', 'cancelled')
    ->get();

// Creating a booking
Booking::create([
    'user_id'      => Auth::id(),
    'court_id'     => $data['court_id'],
    'booking_date' => $data['booking_date'],
    'start_time'   => $data['start_time'],
    'duration'     => $data['duration'],
    'total_price'  => $data['total_price'],
    'status'       => 'upcoming',
]);

// Finding records
$court = Court::findOrFail($request->court_id);

// Updating records
$booking->update(['status' => 'cancelled']);

// User's booking history
$bookings = $user->bookings()->with('court')->latest()->get();
```

All values passed to Eloquent are automatically **parameter-bound** by PDO, preventing SQL injection entirely.

---

*Credential Security
*The Problem: Hardcoding database credentials in config files exposes them if code is pushed to public repositories.

**Before (Hardcoded credentials):**
```php
'mysql' => [
    'username' => 'root',
    'password' => 'secret123',   // Exposed!
];
```

**After (Credentials in `.env` file):**

Credentials are stored in `.env` (listed in `.gitignore`, never committed) and accessed via Laravel's `env()` helper:

```env
# .env (NOT committed to GitHub, each developer has their own)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=courtnest_db
DB_USERNAME=root
DB_PASSWORD=
```

```php
// config/database.php — Reads from .env
'mysql' => [
    'host'     => env('DB_HOST', '127.0.0.1'),
    'port'     => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'laravel'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
];
```

A template file `.env.example` with placeholder values is committed instead, and `.env` is excluded from version control via `.gitignore`.


### VI. File Security Principles

#### A. Protecting the Folder Structure (Directory Isolation)
We make sure our web server (like Apache or Nginx) points its main document root strictly to Laravel's `public/` folder. This acts as a protective firewall. 

Because of this layout, regular internet visitors can only see front-end files like images, CSS, and basic JavaScript. All our core backend system files, controllers, models, and secret configurations are kept safely one level above this folder, making them completely hidden and impossible to reach from a web browser.

#### B. Hiding Secret Settings and Turning Off Debug Mode
* **Blocking .env Access:** Our database settings and passwords live inside the `.env` file. We configure our server settings (using a `.htaccess` file for Apache) to instantly block anyone who tries to type `http://localhost:8000/.env` into their browser by showing a `403 Forbidden` error.
* **Disabling Error Debugging:** In our final configuration, we turn off Laravel's debug mode (`APP_DEBUG=false`). If a system error happens, the website will show a simple, safe error page instead of showing our private backend code, database folder paths, or secret keys to strangers.

**Code snippet from web configuration (.htaccess / server rules):**

# Disable directory browsing so users cannot see list of files in a folder
Options -Indexes

# Block anyone from reading the secret environment settings file directly
```apache
<Files .env>
    Order allow,deny
    Deny from all
</Files>
```

Code snippet from settings file (.env):

# Turn off detailed debug screens to stop code leaking during errors
APP_DEBUG=false

---

## d. References

* **Laravel Official Documentation (Security, Validation, & Authentication):** https://laravel.com/docs/11.x/security
* **Laravel Eloquent ORM & Parameter Binding Protection:** https://laravel.com/docs/11.x/eloquent
* **OWASP Top 10 Reference Guide (SQL Injection, IDOR, and Broken Authentication Mitigation):** https://owasp.org/www-project-top-ten/
* **PHP Carbon Documentation (Secure Date & Time Parsing):** https://carbon.nesbot.com/docs/
* **MDN Web Docs (Client-Side HTML5 Form Input Validation):** https://developer.mozilla.org/en-US/docs/Learn/Forms/Form_validation

## steps installation

1. run xampp apache and mysql
2. add new db courtnest_db
3. open project in vscode
4. open .env and set db_database = courtnest_db
5. run these commands :
    - composer install
    - php artisan key:generate
    - php artisan migrate --seed
    - npm install
    - npm run dev
    - php artisan serve
