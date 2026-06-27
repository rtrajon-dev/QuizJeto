# bdapps CAAS PHP SDK — Documentation

This folder contains the **bdapps (Robi Axiata) CAAS** PHP SDK plus two ready-made OTP
scripts. CAAS = *Connectivity-as-a-Service* — it lets your app talk to the Robi/Airtel
network to **verify users (OTP), subscribe/unsubscribe them, charge their mobile balance,
send SMS, and run USSD menus**.

This is the **backend engine** for QuizJeto: it is what actually sends the OTP SMS,
checks the code, and deducts the 2.78৳ per session from the user's balance.

> Platform note: bdapps is built on Dialog Axiata's "Ideamart/CAAS" platform, so a few
> in-code comments mention `api.dialog.lk`. For Bangladesh you always use the
> `https://developer.bdapps.com/...` endpoints listed below.

---

## 1. Prerequisites

| Requirement | Notes |
|---|---|
| **PHP 7.x / 8.x** | With the **cURL** extension enabled (every class uses cURL). |
| **A bdapps account** | Register at <https://developer.bdapps.com>. |
| **A created App** | Each app gives you an **Application ID** and **Password**. |
| **Subscribed APIs** | In the bdapps dashboard, enable the APIs you need for the app: *Subscription, OTP, Charging (CaaS/Direct Debit), SMS, USSD.* |
| **Whitelisted callback URLs** | For Subscription Notifications / SMS-MO / USSD you must register your public HTTPS URL in the dashboard. |
| **HTTPS hosting** | Robi only calls back to public HTTPS URLs (localhost won't receive callbacks). |

Everywhere you see `applicationId` and `password`, use the values from **your app's page**
in the bdapps dashboard.

---

## 2. Credentials & key concepts

- **`subscriberId`** — the user's phone number in `tel:88<number>` format, e.g.
  `tel:8801812345678`. The SDK helpers add the `tel:88` prefix for you in `send_otp.php`.
- **`referenceNo`** — returned by the OTP *request* call; you must send it back during
  *verify*. Treat it like a short-lived session id for one OTP attempt.
- **`applicationHash` / `applicationMetaData`** — descriptive info about your app/device,
  shown to the user and used for fraud checks.
- **Status code `S1000`** — means **success**. Anything else is an error (see §9).
- **`action`** in subscription: `"1"` = subscribe, `"0"` = unsubscribe.

---

## 3. Two ways to use this SDK — pick ONE

There are **two parallel copies** of the same classes here. Do **not** load both, or PHP
will throw a *"cannot redeclare class"* error.

### Option A — The bundled single file (simplest)
[`bdapps_cass_sdk.php`](bdapps_cass_sdk.php) contains **every class in one file**:
`Core`, `SMSReceiver`, `SMSSender`, `SMSServiceException`, `UssdReceiver`, `UssdSender`,
`UssdException`, `Logger`, `DirectDebitSender` (charging), `CassException`, `Subscription`,
`SubscriptionException`.

```php
require_once __DIR__ . '/bdapps_cass_sdk.php';
use App\Classes\Subscription;
```

> ⚠️ The bundled file is **missing** `SubscriptionReceiver` and `SubscriptionNotification`
> (those exist only as standalone files), and the **standalone** files are missing
> `DirectDebitSender`. See the matrix in §4.

### Option B — The individual class files (recommended for Laravel)
Each class is its own file under namespace `App\Classes` (matching Laravel's `app/Classes`
folder). Use Composer's PSR-4 autoloader, or `require` each file you need.

```php
use App\Classes\SMSSender;
use App\Classes\Subscription;
use App\Classes\DirectDebitSender; // NOTE: only in the bundled file — copy it out if you go this route
```

---

## 4. File-by-file reference

| File | Class(es) | What it does | In bundled file? | Standalone file? |
|---|---|---|:---:|:---:|
| [`Core.php`](Core.php) | `Core` | Low-level HTTPS POST (JSON) via cURL. Base class others extend. | ✅ | ✅ |
| [`send_otp.php`](send_otp.php) | *(script)* | **Request an OTP** be sent to a phone. Returns `referenceNo`. | — | ✅ |
| [`verify_otp.php`](verify_otp.php) | *(script)* | **Verify the OTP** the user typed. Returns `subscriptionStatus`. | — | ✅ |
| [`Subscription.php`](Subscription.php) | `Subscription` | `subscribe()`, `unSubscribe()`, `getStatus()`. | ✅ | ✅ |
| [`SubscriptionReceiver.php`](SubscriptionReceiver.php) | `SubscriptionReceiver` | Parse incoming **subscription callback** (user subscribed/unsubscribed via SMS/menu). | ❌ | ✅ |
| [`SubscriptionNotification.php`](SubscriptionNotification.php) | `SubscriptionNotification` | Parse **scheduled subscription notifications** (e.g. daily renewals). | ❌ | ✅ |
| [`SubscriptionException.php`](SubscriptionException.php) | `SubscriptionException` | Error type for subscription calls. | ✅ | ✅ |
| [`SMSSender.php`](SMSSender.php) | `SMSSender` | **Send SMS** to one/many users, or `broadcast()` to all subscribers. | ✅ | ✅ |
| [`SMSReceiver.php`](SMSReceiver.php) | `SMSReceiver` | Parse an **incoming SMS** (mobile-originated) callback. | ✅ | ✅ |
| [`SMSServiceException.php`](SMSServiceException.php) | `SMSServiceException` | Error type for SMS calls. | ✅ | ✅ |
| [`UssdSender.php`](UssdSender.php) | `UssdSender` | Send a **USSD** menu/response to the user. | ✅ | ✅ |
| [`UssdReceiver.php`](UssdReceiver.php) | `UssdReceiver` | Parse an incoming **USSD** request. | ✅ | ✅ |
| [`UssdException.php`](UssdException.php) | `UssdException` | Error type for USSD calls. | ✅ | ✅ |
| [`Logger.php`](Logger.php) | `Logger` | Append a line to `LogData.log`. | ✅ | ✅ |
| [`bdapps_cass_sdk.php`](bdapps_cass_sdk.php) | *all of the above + `DirectDebitSender` + `CassException`* | The all-in-one bundle, **plus the charging/billing class**. | — | — |

**`DirectDebitSender` (charging/billing) lives ONLY inside `bdapps_cass_sdk.php`.** If you
use the individual files (Option B), copy that class out into its own `DirectDebitSender.php`.

---

## 5. API endpoints used

| Purpose | Method | URL |
|---|---|---|
| Request OTP | POST | `https://developer.bdapps.com/subscription/otp/request` |
| Verify OTP | POST | `https://developer.bdapps.com/subscription/otp/verify` |
| Subscribe / Unsubscribe | POST | `https://developer.bdapps.com/subscription/send` (`action` 1/0) |
| Subscription status | POST | `https://developer.bdapps.com/subscription/getstatus` |
| Send SMS | POST | `https://developer.bdapps.com/sms/send` *(passed into `SMSSender` constructor)* |
| Charge balance (CaaS / direct debit) | POST | your app's **Charging API URL** from the dashboard *(passed into `DirectDebitSender` constructor)* |
| Send USSD | POST | your app's **USSD API URL** from the dashboard *(passed into `UssdSender` constructor)* |

All requests are JSON with `Content-Type: application/json`. All include your
`applicationId` + `password`.

---

## 6. The OTP flow (the part QuizJeto needs first)

This is a **2-step** flow. The frontend OTP boxes you already built call these two scripts.

### Step 1 — `send_otp.php` (request the code)
Front-end POSTs `user_mobile` (just the digits, e.g. `01812345678`). The script:
1. Prefixes it to `tel:88...`.
2. POSTs to `/subscription/otp/request` with your app credentials + metadata.
3. Returns JSON `{"referenceNo": "..."}` — **save this** on the client/session.

```jsonc
// Request body it sends to bdapps:
{
  "applicationId": "YOUR_APP_ID",
  "password": "YOUR_APP_PASSWORD",
  "subscriberId": "tel:8801812345678",
  "applicationHash": "App Name",
  "applicationMetaData": { "client": "MOBILEAPP", "device": "...", "os": "...", "appCode": "..." }
}
```

### Step 2 — `verify_otp.php` (check the code)
Front-end POSTs `Otp` (the 4 digits the user typed) **and** the `referenceNo` from step 1.
The script POSTs to `/subscription/otp/verify` and returns
`{"subscriptionStatus": "REGISTERED" | ...}`.

```jsonc
// Request body it sends to bdapps:
{
  "applicationId": "YOUR_APP_ID",
  "password": "YOUR_APP_PASSWORD",
  "referenceNo": "REF_FROM_STEP_1",
  "otp": "1234"
}
```

### Wiring it to your existing UI
In [`partials`…](../partials/navbar.php) your form currently fakes this. Replace the JS in
[`index.php`](../index.php) `goToOtp()` / `verifyOtp()` with real `fetch()` calls:

```js
// Step 1
const r = await fetch('/bdappsscrapper/send_otp.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: new URLSearchParams({ user_mobile: phone })
});
const { referenceNo } = await r.json();   // keep this

// Step 2 (after user types the 4 digits)
const v = await fetch('/bdappsscrapper/verify_otp.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: new URLSearchParams({ Otp: code, referenceNo })
});
const { subscriptionStatus } = await v.json();
if (subscriptionStatus === 'REGISTERED') { /* success → start quiz */ }
```

> Before this works you **must** fill in `applicationId` and `password` inside both
> `send_otp.php` and `verify_otp.php` (they ship blank). See the security notes in §10.

---

## 7. Subscription, charging, SMS, USSD — usage examples

### Subscription (`Subscription`)
Note the **constructor order**: `($server, $password, $applicationId)`. The `$server`
arg is overwritten internally per method, so you can pass an empty string.

```php
use App\Classes\Subscription;

$sub = new Subscription('', 'YOUR_PASSWORD', 'YOUR_APP_ID');

$sub->subscribe('tel:8801812345678');     // action=1
$sub->unSubscribe('tel:8801812345678');   // action=0
$status = $sub->getStatus('tel:8801812345678'); // e.g. "REGISTERED" / "UNREGISTERED"
```

### Charge the user's balance (`DirectDebitSender` — billing)
This is how you deduct **2.78৳ per quiz session**. Comes from `bdapps_cass_sdk.php`.

```php
require_once __DIR__ . '/bdapps_cass_sdk.php';
use App\Classes\DirectDebitSender;

$charger = new DirectDebitSender($chargingApiUrl, 'YOUR_APP_ID', 'YOUR_PASSWORD');

$externalTrxId = uniqid('quiz_'); // your own unique id per charge
$result = $charger->cass($externalTrxId, 'tel:8801812345678', '2.78');
// returns 'ok' on S1000, otherwise throws CassException
```

> Charge **on the server, after** you've confirmed the user really started a paid session.
> Never trigger billing from the browser.

### Send an SMS (`SMSSender`)
```php
use App\Classes\SMSSender;

$smsUrl = 'https://developer.bdapps.com/sms/send';
$sms = new SMSSender($smsUrl, 'YOUR_APP_ID', 'YOUR_PASSWORD');

$sms->sms('আপনি আজকের লিডারবোর্ডে ১ নম্বরে আছেন! 🎉', 'tel:8801812345678');
$sms->sms('Multiple users', ['tel:88018...', 'tel:88017...']);
$sms->broadcast('নতুন কুইজ এসেছে!'); // to all subscribers
```

> ⚠️ The two copies of `SMSSender` differ: the **standalone** `SMSSender.php` parses the
> real `destinationResponses[0].statusCode` from the reply, while the one inside
> `bdapps_cass_sdk.php` **hardcodes success**. Prefer the standalone version for real
> delivery-status handling.

### Send USSD (`UssdSender`)
```php
use App\Classes\UssdSender;

$ussd = new UssdSender($ussdApiUrl, 'YOUR_APP_ID', 'YOUR_PASSWORD');
// 'mo-cont' = keep session open, 'mt-fin' = final message then end
$ussd->ussd($sessionId, 'Welcome to QuizJeto\n1. Play\n2. Score', 'tel:88018...', 'mo-cont');
```

---

## 8. Receiving callbacks (Robi → your server)

These classes parse the JSON Robi POSTs to **your** registered callback URLs. Create a
public PHP endpoint, instantiate the matching receiver, and read the getters.

```php
// e.g. subscription_callback.php  (URL registered in the dashboard)
use App\Classes\SubscriptionReceiver;

$cb = new SubscriptionReceiver();        // reads php://input automatically
$status   = $cb->getStatus();            // REGISTERED / UNREGISTERED
$user     = $cb->getsubscriberId();
$freq     = $cb->getFrequency();
// → update your database (mark user subscribed/unsubscribed)
```

| Receiver | Reads | Use for |
|---|---|---|
| `SubscriptionReceiver` | `status`, `subscriberId`, `frequency`, `applicationId`, `timeStamp` | User subscribes/unsubscribes outside the app (SMS/USSD). |
| `SubscriptionNotification` | adds `requestId` | Scheduled/renewal notifications. |
| `SMSReceiver` | `sourceAddress`, `message`, `requestId`, `encoding`… | Inbound SMS (user texts a keyword). |
| `UssdReceiver` | `sessionId`, `message`, `ussdOperation`… | Inbound USSD menu navigation. |

Each receiver expects to read the raw POST body (`php://input`), so point your route
directly at a script that constructs it.

---

## 9. Status codes & error handling

| Code | Meaning |
|---|---|
| `S1000` | Success. |
| `E1312` | Request invalid (missing required fields). |
| `E1325` | Address/format invalid. |
| `500`  | Empty/invalid server response (bad URL or network). |
| other  | Read the `statusDetail` text returned by bdapps. |

Wrap calls in try/catch — every module has its own exception with helpful getters:

```php
use App\Classes\SubscriptionException;
try {
    $sub->subscribe('tel:88018...');
} catch (SubscriptionException $e) {
    echo $e->getStatusCode();      // e.g. E1312
    echo $e->getStatusMessage();   // human-readable
    echo $e->getRawResponse();     // raw JSON, if any
}
```
(`SMSServiceException` uses `getErrorCode()` / `getErrorMessage()`; `CassException` and
`UssdException` use `getStatusCode()` / `getStatusMessage()` / `getRawResponse()`.)

---

## 10. ⚠️ Security & correctness issues to fix before production

These scripts are sample/demo quality. Fix these before going live:

1. **Empty credentials** — `send_otp.php` and `verify_otp.php` have blank `applicationId`
   and `password`. Fill them, and **don't hardcode** them — load from environment
   variables / a config file outside the web root.
2. **Secrets in source** — never commit your app password to git. Add a `.env` and a
   `.gitignore`.
3. **Plaintext logging of PII** — `send_otp.php` writes the phone number to
   `user_number.txt`, and `verify_otp.php` writes the **OTP + referenceNo** to
   `OTP+RefNo.txt`. This leaks personal data and live OTPs. **Remove these
   `file_put_contents`/`fwrite` calls** (or log to a secured store without the OTP).
4. **No input validation** — `$_POST['user_mobile']`, `Otp`, `referenceNo` are used
   unsanitized. Validate the number format (`^01[3-9]\d{8}$`) and that the OTP is 4 digits.
5. **A bug in `send_otp.php`** — in the `if ($response === null)` branch it tries to read
   `$response["referenceNo"]` from a null value (will warn/break). Only the non-null branch
   is correct; the null branch should return an error instead.
6. **`CURLOPT_SSL_VERIFYPEER = false`** everywhere disables TLS verification (MITM risk).
   Turn it **on** in production with a proper CA bundle.
7. **No rate limiting** — add throttling on OTP requests per number/IP to prevent SMS-bombing
   and cost abuse.
8. **Verify charges server-side** — only call `DirectDebitSender::cass()` from trusted
   server code after confirming a real session; reconcile with the charging callback.

---

## 11. How this maps to QuizJeto

| QuizJeto feature | Use |
|---|---|
| "OTP পাঠান" button | `send_otp.php` → get `referenceNo` |
| OTP verify boxes | `verify_otp.php` → `subscriptionStatus` |
| Keep user subscribed for daily quiz | `Subscription::subscribe()` / status checks |
| Charge 2.78৳ per session | `DirectDebitSender::cass()` (server-side only) |
| "You're #1 today!" alerts, new-quiz blasts | `SMSSender::sms()` / `broadcast()` |
| Handle unsubscribe / renewals from Robi | `SubscriptionReceiver` / `SubscriptionNotification` callback endpoints |

> Recommended next step: when you move QuizJeto to Laravel, drop the individual class files
> into `app/Classes/`, move credentials to `.env`, wrap each capability in a small service
> class, and replace the two demo OTP scripts with controller actions that validate input
> and never log the OTP.

---

*Generated as documentation for the bdapps CAAS SDK files in this folder. Verify endpoint
URLs and field names against the current official bdapps developer docs at
<https://developer.bdapps.com> before launch, as platform details can change.*
