<p align="center">
  <img src="assets/img/logo.png" alt="ZENTRIX Logo" width="120">
</p>

<h1 align="center">ZENTRIX</h1>

<p align="center">
  <strong>Silent. Precise. Yours.</strong><br>
  Self-hosted intelligence collection platform
</p>

---

ZENTRIX is a self-hosted intelligence collection tool.  
It creates tracking links that capture IP address, location, device information, a photo, and a short audio recording — then immediately sends everything to your Telegram.

---

## Author

**Samuel Adeyinka**  
Also known as **Big Sam**

---

## Version Overview

| Version | Status      | Key Features                                                               |
| ------- | ----------- | -------------------------------------------------------------------------- |
| v1      | Completed   | Basic tracking + Telegram + Live View                                      |
| v2      | Planned     | Official NIPOST Postcode API integration                                   |
| v3      | **Current** | Photo + Audio capture, Dark/Light mode, Admin dashboard, Verification page |

---

## Features (Version 3)

- Clean interface with Dark & Light mode
- Custom tracking ID
- Real-time Telegram alerts
- IP, City, Region, ISP, and Proxy detection
- Precise GPS location (when allowed)
- Automatic silent photo capture
- Automatic short audio recording (about 3.5 seconds)
- Photos and Audio shown in Live View
- Password-protected Admin panel
- No database required (pure PHP)

---

## Folder Structure

```
zentrix/
├── config.php
├── index.php
├── track.php
├── verify.php
├── media.php
├── liveview.php
├── admin.php
├── .htaccess
├── README.md
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── img/
│   │   ├── logo.png
│   │   └── overlay.png
│   └── js/
│       ├── homepage.js
│       ├── theme.js
│       ├── track.js
│       └── utils.js
└── logs/
    ├── photos/
    └── audio/
```

---

## How to Run ZENTRIX

### Method 1: Local Testing (Kali Linux / Any Linux)

1. Open terminal and go to the project folder:
```bash
cd /path/to/zentrix
```

2. Start the PHP built-in server:
```bash
php -S 0.0.0.0:8080
```

3. Open your browser and visit:
```
http://YOUR-IP-ADDRESS:8080
```

Example:
```bash
php -S 192.168.1.25:8080
```
Then open: `http://192.168.1.25:8080`

> **Note:** Camera and Microphone only work properly on **HTTPS** or `localhost`. For full testing of photo/audio, use a real domain with SSL or test on localhost.

---

### Method 2: Shared Hosting / VPS

1. Upload all files to your web root (`public_html` or similar).
2. Make sure the folders are writable:
```bash
chmod 755 logs
chmod 755 logs/photos
chmod 755 logs/audio
```
3. Edit `config.php` (see next section).
4. Visit your domain.

---

## Configuration (`config.php`)

Open `config.php` and fill in these values:

```php
'domain' => 'https://yourdomain.com',          // Your real domain

'telegram' => [
    'enabled'   => true,
    'bot_token' => 'YOUR_BOT_TOKEN',
    'chat_id'   => 'YOUR_CHAT_ID',
],

'admin_password' => 'your_strong_password',
```

---

## Telegram Setup

1. Open Telegram and search for **@BotFather**
2. Create a new bot and copy the **Bot Token**
3. Get your **Chat ID** using @userinfobot
4. Paste both into `config.php`

---

## How to Use

1. Open the homepage
2. Enter the **Destination URL** (where the person will be redirected)
3. (Optional) Enter a Custom ID
4. Click **Generate ZENTRIX Link**
5. Copy and share the link
6. When the person opens the link:
   - They see the Verification page
   - They are asked for Location, Camera, and Microphone
   - A photo and short audio are captured automatically
   - Everything is sent to your Telegram
   - They are redirected to the destination URL
7. View results in **Live View** or **Admin Dashboard**

---

## Admin Access

- Go to: `https://yourdomain.com/admin.php`
- Password is set in `config.php` (`admin_password`)
- From the Admin panel you can:
  - See all tracking links
  - View number of hits, photos, and audio
  - Open Live View
  - Delete individual tracking IDs

---

## Important Notes

- Logs are **never** automatically deleted (for proper audit trail)
- You can manually delete individual tracking IDs from the Admin panel
- Camera & Microphone require user permission
- For best results, use HTTPS

---

## Requirements

- PHP 7.4 or higher
- `curl` extension enabled
- Writable `logs/`, `logs/photos/`, and `logs/audio/` folders

---

## Disclaimer

This tool is intended **only** for authorized assessment, research, and internal intelligence work.  

The author is not responsible for any misuse.

---

**ZENTRIX** — Built quietly. Cuts precisely.  

**Author:** Samuel Adeyinka (Big Sam)
