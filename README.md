# 👻 ZENTRIX

**Silent. Precise. Yours.**

Phantom is a self-hosted link tracker built for assessment and intelligence gathering.  
It captures IP, geolocation, device data, and attempts to resolve Nigerian digital postcodes — then instantly pushes a full report to your Telegram.

---

## Features

- Clean dark interface (completely different from Grabify)
- Create tracking links with custom ID
- Real-time Telegram notifications
- IP + City + Region + ISP + Proxy detection
- Browser geolocation attempt (with user permission)
- Optional scrape of `postcode.gov.ng/find` HTML response
- Live View dashboard
- Password-protected admin panel
- Lightweight pure PHP (no database required)

---

## Folder Structure
phantom/
├── config.php
├── index.php
├── track.php
├── liveview.php
├── admin.php
├── .htaccess
├── README.md
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── homepage.js
└── logs/               ← auto-created (do not commit)


---

## Quick Setup

1. Upload all files to your web server (PHP 7.4+ recommended)
2. Edit `config.php`:
   - Set your domain
   - Add Telegram Bot Token + Chat ID
   - Change admin password
3. Make sure the `logs/` folder is writable (permission 755 or 775)
4. Open your domain → create a link → test

---

## Telegram Setup

1. Create a bot via [@BotFather](https://t.me/BotFather)
2. Copy the bot token
3. Get your Chat ID (use [@userinfobot](https://t.me/userinfobot) or forward a message to the bot)
4. Paste both into `config.php`

Every click will send a formatted report to your Telegram.

---

## Usage

1. Open the homepage
2. Enter destination URL
3. (Optional) set a custom ID
4. Click **Generate Phantom Link**
5. Share the generated link
6. Open **Live View** to see all hits in real time

---

## Security Notes

- `config.php` and `.htaccess` are protected from direct access
- `/logs/` directory is blocked from the web
- Directory listing is disabled
- TRACE / DELETE / TRACK methods are blocked

---

## Requirements

- PHP 7.4 or higher
- `curl` extension enabled (for Telegram + postcode requests)
- `allow_url_fopen` or curl for IP geolocation
- Writable `logs/` directory

---

## Disclaimer

This tool is intended for **authorized assessment, research, and security testing only**.  
The author is not responsible for any misuse.

---
**Creator** - Samuel Adeyinka **[Big Sam]**
**Phantom** — Built quietly. Cuts precisely.


