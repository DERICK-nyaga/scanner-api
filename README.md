# Package Scanner (PWA + Laravel API)

Scan QR codes and barcodes with your phone, edit them in a spreadsheet-style table, flag duplicates, export CSV, share through Gmail.

- `frontend/`: Vue 3 + Vite PWA. This is the app you install on your phone. It works offline.
- `backend/`: Laravel API files (optional). Syncs your rows to a server and exports CSV there.

## Why these choices
| Need | Tool |
|---|---|
| Phone app without the Play Store | **PWA**: opens in Chrome, then "Install app". No APK needed |
| Fast, reactive spreadsheet table | **Vue 3** (Vite) |
| QR + barcode decoding | **html5-qrcode** (uses Android's built-in detector when available) |
| Works with no signal | **vite-plugin-pwa** (service worker) and localStorage |
| API format | **Laravel 11**. The phone sends JSON to `/api/scans/bulk` |

## Install the tools (on your laptop)
1. Node.js 20 or newer: https://nodejs.org
2. For the API only: PHP 8.2+, Composer, and MySQL or SQLite (XAMPP or Laragon on Windows covers all three).

## Run the frontend
```bash
cd frontend
npm install
npm run dev        # test on your laptop
npm run build      # produces dist/ (the deployable app)
```

## Getting it onto your phone
The camera only works over **HTTPS** (or `localhost`). Pick one:
1. **Free hosting (easiest):** upload `frontend/dist` to Netlify, Vercel or Cloudflare Pages. Open the HTTPS link in Chrome on your phone, then menu, **Install app / Add to Home screen**.
2. **Quick test:** `npm run dev`, then `npx localtunnel --port 5173` (or ngrok) gives you an HTTPS link to open on the phone.

## Laravel API (optional)
```bash
composer create-project laravel/laravel scanner-api
cd scanner-api
php artisan install:api
```
Copy the contents of `backend/` into `scanner-api/` (merge folders; `routes/api.php` is replaced by the one here). Then:
```bash
echo "SCANNER_API_TOKEN=pick-a-long-random-string" >> .env
php artisan migrate
php artisan serve --host=0.0.0.0
```
In the app, tap **⚙ Settings**, enter the API URL and token, then tap **☁ Sync**. Deploy the API on HTTPS, because an HTTPS app cannot call plain-HTTP APIs.

| Method | URL | Purpose |
|---|---|---|
| POST | `/api/scans/bulk` | Upsert rows (safe to repeat) |
| GET | `/api/scans` | List rows (JSON, paginated) |
| GET | `/api/scans/export` | Download CSV with the Duplicate column |
| DELETE | `/api/scans/{client_id}` | Delete one row |

All calls need the header `Authorization: Bearer <SCANNER_API_TOKEN>`.

## Using the app
- **Scan**: continuous scanning. You hear a high beep for a new code and a low double-buzz for a duplicate.
- **Type**: enter a number and press Enter or Add.
- **Table**: tap any cell to edit it. Enter moves down one row, like Excel. Columns are #, Tracking Number, Timestamp, Method (scanned or typed), Duplicate check, and Notes.
- **Duplicates**: every group of matching codes gets its own highlight colour and shows `DUPLICATE ×N`. Matching ignores case and spaces at the ends. The counter in the header shows the duplicate total. Use "Duplicates only" to filter.
- **CSV**: **⬇ CSV** saves to your phone's Downloads. **⬆ Import** loads a CSV back (for example after you edit it on the laptop).
- **Share**: opens Android's share sheet with the CSV attached, so choose Gmail. **✉ Gmail** opens a Gmail compose window and downloads the CSV so you can attach it. Browsers cannot attach files through a Gmail link, which is why Share is the better route.

## Tips
- Excel turns long numbers into `1.23E+15`. When opening the CSV on the laptop, use Data, From Text/CSV and set the Tracking Number column to Text.
- Data is stored in the browser's local storage. Export or sync regularly, because clearing Chrome's site data erases it.
