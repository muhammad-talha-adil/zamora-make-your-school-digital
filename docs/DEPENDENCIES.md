# Dependencies added beyond the original starter kit

One line per package: what it is, why it was added, and when. Check this before
reaching for a new dependency — something here might already solve it, and this
is also the place to add a new entry the moment a new one goes in.

## Backend (composer.json)

- **`spatie/laravel-activitylog`** (2026-09-14) — records who changed what and
  when (audit trail) across the models that matter for a school
  admin/owner to review later (fee payments, staff employment changes, exam
  result publishing, etc.). Chosen over a hand-rolled `activity_logs` table +
  manual logging calls because it's small, well-maintained, and does the
  causer/subject/old-vs-new tracking correctly out of the box — see
  `docs/MODULE-LOG.md`/`CURRENT-MODULE.md` for which models carry the
  `LogsActivity` trait and why.

- **`endroid/qr-code`** (2026-09-21) — renders the QR code printed on
  student/staff ID cards for QR attendance (`App\Services\Attendance\QrCodeService`).
  Encodes a permanent `URL::signedRoute()` per person, never the raw
  `employee_no`/`registration_no`. Chosen over `simple-qrcode` (a thinner,
  less actively maintained wrapper around the same underlying libraries) —
  no native extension requirement beyond GD, which Laravel already needs.

## Frontend (package.json)

- **`chart.js`** (2026-09-14) — real charting for the Fee/Finance/Attendance/
  main dashboards' trend visualizations, replacing a dependency-free inline-SVG
  stopgap component that existed only because nothing else was available.
  Small footprint, no framework lock-in beyond a thin custom Vue wrapper
  (`resources/js/components/charts/LineChart.vue`), and covers everything
  these dashboards need (line/bar trends) without pulling in a heavier
  analytics library.

- **`qr-scanner`** (2026-09-21) — reads a QR code off the device camera on
  the `/attendance/scan` page (Method 1 of QR attendance). jsQR-based,
  small (~16 KB gzipped), works in all browsers including Safari/iOS where
  the native `BarcodeDetector` API isn't reliably available, so it was
  chosen over that API + a fallback library, since the fallback library
  would be needed either way.
