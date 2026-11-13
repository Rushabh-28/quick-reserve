# Quick Reserve — Hotel Booking System (Guest site + Admin panel)

A complete PHP + MySQL hotel booking platform:

* **Guest site** (`/Client`): search hotels by city, dates and guests, live room availability, coupons, GST pricing,
  **Razorpay checkout**, instant confirmation, **PDF tax invoice that downloads automatically after payment**,
  my bookings, cancellation with refund status, reviews and ratings.
* **Admin panel** (`/Admin`): hotels, room types, rooms, guests, bookings, payments, coupons, reviews,
  dashboard with revenue and refund alerts, PDF booking report, invoice download for any booking.
* Both sides share one database, so everything a guest does shows up in the admin panel instantly,
  and everything the admin changes (hotels, prices, rooms, coupons) is live on the guest site.

## Quick start (XAMPP / WAMP / Laragon)
1. Copy this folder to `htdocs/quick-reserve`. Start **Apache** and **MySQL**.
2. phpMyAdmin → **Import** → `database/hotel_managment.sql` (creates the tables).
3. *Optional demo data:* import `database/seed_demo.sql` as well (8 hotels, 9 room types, 96 rooms, 11 guests,
   26 bookings with payments, invoices, reviews and 5 coupons).
4. Open **http://localhost/quick-reserve/** (guest site) or **http://localhost/quick-reserve/Admin/login.php** (admin).

No MySQL password? The defaults (`root` / empty) work. Otherwise copy `config/config.sample.php`
to `config/config.local.php` and edit it.

### Demo logins (only if you imported `seed_demo.sql`)
| Who | Email | Password |
|---|---|---|
| Admin | `admin@quickreserve.test` | `Admin@1234` |
| Guest | `aarav.sharma@example.com` (and `priya.nair@`, `rohan.mehta@` … `@example.com`) | `Demo@1234` |

**Change or delete the demo admin before putting the site online.** Without demo data, open
`Admin/register.php` once to create your own first admin.

## Razorpay payments
The checkout works out of the box in **demo mode** (a built-in fake gateway, no real money) so you can test the
whole flow. To use real Razorpay:

1. Create an account at razorpay.com → **Settings → API Keys** → generate **Test** keys (`rzp_test_…`).
2. Put them in `config/config.local.php`:
   ```php
   'razorpay_key_id'     => 'rzp_test_xxxxxxxx',
   'razorpay_key_secret' => 'xxxxxxxxxxxxxxxx',
   ```
   (or set the environment variables `QR_RAZORPAY_KEY_ID` / `QR_RAZORPAY_KEY_SECRET`).
3. Optional but recommended: add a webhook in the Razorpay dashboard pointing to
   `https://your-domain/Client/razorpay_webhook.php` for events `payment.captured` and `order.paid`, and put its secret in
   `razorpay_webhook_secret`. The webhook confirms the booking even if the guest closes the browser.
4. Test with Razorpay's test cards/UPI, then switch to live keys when you are ready (needs HTTPS and PHP `curl`).

How it is secured: the order is created **server-side** with the amount from the database; the browser callback is only
accepted after the **HMAC-SHA256 signature** (`order_id|payment_id`) is verified with your secret; payments are idempotent.

## The booking flow
`Search → Hotel page (live availability) → Review & coupon → Pay (room held 15 min) → Payment verified → Booking confirmed →
invoice PDF downloads automatically → My bookings`

* Two guests can never get the same room (rooms are locked while a booking is created; unpaid holds expire).
* GST: 0% up to ₹1,000/night, 12% up to ₹7,500, 18% above (split CGST/SGST on the invoice).
* Cancelling a paid booking marks it *refund pending*; staff see an alert on the admin dashboard.
* Invoice settings (business name, address, GSTIN) are in `config/connection.php` / `config.local.php`.

## Requirements
PHP 8.1+ (`mysqli`, `mbstring`, `fileinfo`, `gd`, `iconv`, `curl` for live Razorpay), MySQL 5.7+/MariaDB 10.4+, Apache or Nginx.

## Project layout
```
Client/            guest-facing site (search, checkout, payment, invoice, my bookings)
Admin/             admin panel (Purple Admin template)
  includes/        shared libraries: bootstrap/security, pricing, booking rules, Razorpay, invoice PDF
  Reports/         booking report (FPDF)
  PHPMailer/       e-mail (password-reset codes)
  uploads/         images (demo_* images are bundled; new uploads are git-ignored)
config/            DB + payment + business settings (config.local.php is git-ignored)
database/          hotel_managment.sql (schema) and seed_demo.sql (demo data)
```

## E-mail (password reset)
Optional. Set the SMTP values in `config/config.local.php` (Gmail needs an **App Password**).

## Security notes
Prepared statements everywhere, escaped output, CSRF tokens on every form, hashed passwords, sessions, throttled logins,
validated image uploads, POST-only destructive actions. Serve over HTTPS in production and keep `config.local.php` private.

## Credits & licences
Admin template: Purple Admin (MIT, `THIRD-PARTY-LICENSE-PurpleAdmin.txt`) · PHPMailer (LGPL) · FPDF (`Admin/Reports/fpdf-license.txt`).
Hotel pictures are illustrations generated for this demo. All demo hotels, guests and payments are fictional.
Add a licence for your own code (MIT is a common choice).
