# PROJECT REPORT: SECURITY ENHANCEMENT

* **GROUP MEMBER:** 
IRFAN HAKEEM BIN KHAIRUDIN (2318729)
ARIFF ROSTAM HAIKQAL BIN SUBAHIR (2319887)


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

* Input validation was enhanced in the Courtnest web application to ensure that users can only submit correct, safe, and logical data. The validation was applied     on both the client side and server side. Client-side validation helps guide users before they submit the form, while server-side validation ensures that            manipulated or invalid requests are rejected before being processed by the system.

* 1. Registration Input Validation
    The registration form was enhanced by adding stronger validation rules for user details such as name, email, phone number, and password. This ensures that         users provide complete and valid information before an account is created.
*Code snippet:*
```php
Validator::make($input, [
'name' => ['required', 'string', 'max:255'],
'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
'phone_number' => ['required', 'string', 'max:20'],
'password' => [ 'required', 'string', 'min:12', 'confirmed', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[^A-Za-z0-9]/', ], 'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '', ], [ 'password.min' => 'Password must be at least 12 characters.', 'password.regex' => 'Password must contain uppercase letter, lowercase letter, and special character.',
 ])->validate();
```

The password validation was strengthened by requiring a minimum of 12 characters, at least one lowercase letter, at least one uppercase letter, and at least one special character. This helps prevent users from registering with weak passwords.

2. Booking Form Client-Side Validation
Client-side validation was added to the booking form using HTML5 validation attributes. The court, booking date, duration, and start time fields are required before the form can be submitted.
<select name="court_id" id="court_id" required>
<select name="duration" id="duration" required>
<select name="start_time" id="start_time" required>

The booking date input was also restricted using the min and max attributes. This prevents users from selecting past dates or dates more than 3 months in advance.
Code snippet:

```php
<input type="date" name="booking_date"
id="booking_date"
required
min="{{ date('Y-m-d') }}"
max="{{ now()->addMonths(3)->toDateString() }}"
value="{{ date('Y-m-d') }}">
```

This enhancement makes the booking calendar more logical because users cannot book dates from the past or dates too far into the future.

3. Passed and Booked Slot Validation

The booking interface was improved by separating the labels for unavailable time slots. Previously, unavailable slots were not clearly separated. The enhanced version now shows:
1. Passed for time slots that have already passed today.
2. Booked for time slots that are already reserved by another user.

Code snippet:

```php
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

This validation improves user experience and prevents users from selecting unavailable time slots from the interface.

4. Booking Form Server-Side Validation

Server-side validation was added in BookingController.php to ensure that invalid data cannot be processed even if users manipulate the HTML form or send requests manually. This is important because client-side validation can be bypassed.
php
$request->validate([
'court_id' => 'required|integer|exists:courts,id',
'booking_date' => 'required|date|after_or_equal:today|before_or_equal:' . now()->addMonths(3)->toDateString(),
'start_time' => 'required|date_format:H:i:s|in:08:00:00,09:00:00,10:00:00,11:00:00,12:00:00,13:00:00,14:00:00,15:00:00,16:00:00,17:00:00,18:00:00,19:00:00,20:00:00,21:00:00,22:00:00',
'duration' => 'required|integer|min:1|max:3',
]);
```

The server-side validation checks that the selected court exists in the database, the booking date is valid, the start time follows the correct format, and the duration is between 1 and 3 hours. This prevents users from submitting invalid court IDs, past dates, invalid times, negative durations, or overly long bookings.

5. Past Time Validation

The system also checks if the selected booking time has already passed on the current day. This prevents users from booking a time slot that is no longer available.
Code snippet:

```php
if ($request->booking_date == now()->toDateString()) {
    if ($newStart->lt(now())) {
    return back()->withErrors([
    'error' => 'You cannot book a time slot that has already passed today.'
    ])->withInput(); }
```


6. Overlapping Booking Validation
   The system checks existing bookings to prevent two users from booking the same court at overlapping times.
   Code snippet:

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
])->withInput(); }
```


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

* **V. Database Security Principles:**

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
