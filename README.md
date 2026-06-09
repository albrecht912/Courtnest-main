# PROJECT REPORT: SECURITY ENHANCEMENT

* **GROUP MEMBER:** 
IRFAN HAKEEM BIN KHAIRUDIN (2318729)
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

* **II. Authentication:**

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
CourtNest sports court booking application to address both attack vectors.

### XSS (Cross-Site Scripting) Prevention

#### Enhancement 1 — Blade `{{ }}` Escaped Output on All User-Controlled Data

Laravel's Blade engine automatically HTML-encodes every value rendered through
`{{ }}`, converting characters like `<`, `>`, `"`, `'`, and `&` into their safe
HTML entity equivalents. This prevents stored and reflected XSS — even if an
attacker stored `<script>alert(1)</script>` as their name, Blade would render it
as the harmless literal string `&lt;script&gt;alert(1)&lt;/script&gt;`.

The unsafe alternative `{!! !!}` (raw, unescaped output) was audited across all
views and confirmed to be absent from the entire application.

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

All four forms in the application were verified to include `@csrf`

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

* **VI. File Security Principles:**

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
