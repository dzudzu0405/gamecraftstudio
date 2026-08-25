# GameCraft Studio

A web platform that helps parents, teachers and content creators design **printable adventure board games** for children.

Built with **plain PHP and MySQL** — no Composer, no Node.js, no SSH. Drag the whole folder into `public_html` through the cPanel File Manager and it runs.

---

## Contents

1. [Installing on cPanel](#1-installing-on-cpanel)
2. [Running it locally](#2-running-it-locally)
3. [Adding real artwork](#3-adding-real-artwork)
4. [Email and Google sign-in](#4-email-and-google-sign-in)
5. [Folder structure](#5-folder-structure)
6. [How the product works](#6-how-the-product-works)
7. [Coverage against the SRS](#7-coverage-against-the-srs)
8. [Troubleshooting](#8-troubleshooting)

---

## 1. Installing on cPanel

### Requirements

| Component | Requirement |
|---|---|
| PHP | 8.1 or newer (8.2 / 8.3 recommended) |
| Required extensions | `pdo_mysql`, `mbstring` |
| Recommended extension | `gd` (resizes uploaded images) |
| Database | MySQL 5.7+ or MariaDB 10.3+ |
| Disk space | ~5 MB for the code, the rest for artwork |

### Steps

**Step 1 — Create the database**

In **cPanel → MySQL® Databases**:
1. Under *Create New Database*, enter a name such as `gamecraft`. cPanel turns it into `youraccount_gamecraft`.
2. Under *Add New User*, create a user and password (use *Password Generator* and **save the password**).
3. Under *Add User To Database*, pick that user and database, tick **ALL PRIVILEGES**, then *Make Changes*.

Note down three things: the **database name**, the **user name** and the **password**.

**Step 2 — Upload the files**

In **cPanel → File Manager → public_html**:
1. Click *Upload* and choose `gamecraft.zip`.
2. Once it finishes, right-click the file and choose *Extract*.
3. Delete the `.zip` afterwards to keep things tidy.

> Want it in a sub-folder such as `example.com/gamecraft`? Extract into `public_html/gamecraft` — the app works out its own path.

**Step 3 — Fill in the database details**

In File Manager, right-click `config.php` → *Edit*, and change three lines:

```php
'name'     => 'youraccount_gamecraft',   // Database name
'user'     => 'youraccount_gamecraft',   // Database user
'pass'     => 'your-password-here',      // Database password
```

Also replace the secret key with any long random string:

```php
'app_key' => 'some-long-random-string-of-your-own',
```

Click *Save Changes*.

**Step 4 — Set folder permissions**

Right-click the `uploads` folder → *Change Permissions* → set **755**. Do the same for `storage`.

**Step 5 — Run the installer**

Open your domain in a browser. The app redirects to the setup page:

- **Step 1** checks the server. Anything flagged in red has the fix written directly underneath it.
- **Step 2** confirms the database connection.
- **Step 3** creates your administrator account.

Click **Install**. It creates 12 tables and seeds 36 maps, 30 character sets, 15 mission templates and 54 game templates.

**Step 6 — Remove the installer (important)**

Once installed, delete the `install` folder in File Manager. The installer refuses to run twice on its own, but deleting it removes any doubt.

---

### Alternative: import through phpMyAdmin

If the web installer gives you trouble, import the schema yourself:

1. Open **cPanel → phpMyAdmin** and select your database.
2. Go to the **Import** tab, choose `install/install.sql`, then *Go*.
3. Reload the site — the installer will only ask for an administrator account.

`install.sql` contains the table structure and the content library only. It holds **no** user accounts and **no** projects.

---

### If the host has mod_rewrite disabled

By default the app uses clean URLs such as `/projects`. Without `mod_rewrite` every page returns 404. The fallback form still works:

```
https://example.com/index.php?r=projects
```

For a proper fix, ask your host to enable `mod_rewrite`, or uncomment the `RewriteBase` line in `.htaccess` and set it to match your folder.

---

## 2. Running it locally

Optional — only useful if you want to look before uploading.

```bash
php -S localhost:8000 dev-server.php
```

Then open `http://localhost:8000`. On Windows you can double-click `RUN-LOCAL-TEST.bat` instead.

`dev-server.php` exists purely for local testing: PHP's built-in web server ignores `.htaccess`, so this script does the routing in its place. **cPanel never uses it.**

To test without setting up MySQL, edit `config.php`:

```php
'driver' => 'sqlite',
```

Data then lives in `storage/gamecraft.sqlite`. **Switch back to `mysql` before going live** — SQLite locks the whole file on every write, so it stalls once several people use the site at once.

---

## 3. Adding real artwork

Right now every illustration is **drawn as SVG** inside PHP. That means the app is fully working from day one, with nothing waiting on artwork.

When real artwork arrives you **do not need to touch the code** — just put files in the right folder with the right names.

### Naming rules

| Kind | Folder | Example file name |
|---|---|---|
| Maps | `uploads/library/maps/` | `map-18-01.jpg` |
| Characters | `uploads/library/characters/` | `char-01-1.jpg` (pose 1) |
| Move cards | `uploads/library/moves/` | `move-01.jpg` |
| Hero cards | `uploads/library/rewards/` | `reward-01.jpg` |
| Template covers | `uploads/library/templates/` | `tpl-forest-standard.jpg` |

Accepted extensions: `.jpg` `.jpeg` `.png` `.webp`

Every folder already contains an **`ARTWORK-FILE-LIST.txt`** listing every file name needed, with its description and plan. Open it to see exactly how many images are required and what to call them.

### The quickest way to do it

1. Open **Asset Library** in the app to see which items still show the *Placeholder* badge.
2. Prepare the images and name them from the list.
3. Zip them, upload into the right folder through File Manager, then *Extract*.
4. Reload the page — real artwork replaces the placeholders automatically.

The **Asset Library** page tracks progress against the content production targets (36 maps, 30 character sets, 20 move card designs, 30 hero cards).

### Recommended image sizes

| Kind | Size | Aspect |
|---|---|---|
| Map backgrounds | 1600 × 1100 px | 16:11 landscape |
| Characters | 600 × 600 px | square, white background |
| Cards | 750 × 1050 px | 5:7 portrait |
| Template covers | 800 × 550 px | 16:11 landscape |

---

## 4. Email and Google sign-in

Both are optional. The app runs perfectly well with neither, but email is what
makes **Forgot password** work, so it is worth setting up before real customers
arrive.

### Setting up email

The app talks SMTP directly, using a mailbox on your own domain. That is what
keeps the mail out of the spam folder — messages sent through PHP's `mail()`
have nothing authenticating them and get filtered aggressively.

**Step 1 — create the mailbox**

In cPanel go to **Email Accounts → Create** and make something like
`noreply@yourdomain.com`. Save the password.

**Step 2 — find the SMTP settings**

On that new account click **Connect Devices**. cPanel shows the exact host and
ports. They usually look like:

| Setting | Typical value |
|---|---|
| Host | `mail.yourdomain.com` |
| Port | `465` with `ssl`, or `587` with `tls` |
| Username | the full address, `noreply@yourdomain.com` |

**Step 3 — fill in `config.php`**

```php
'mail' => [
    'driver'     => 'smtp',
    'enabled'    => true,
    'host'       => 'mail.yourdomain.com',
    'port'       => 465,
    'encryption' => 'ssl',
    'username'   => 'noreply@yourdomain.com',
    'password'   => 'the mailbox password',
    'from_email' => 'noreply@yourdomain.com',
    'from_name'  => 'GameCraft Studio',
    'reply_to'   => '',
],
```

That is it. New accounts get a welcome email, and **Forgot password** starts
working straight away.

**Testing without a mailbox.** Set `'driver' => 'log'` and nothing is sent —
every message is written to `storage/logs/mail.log` instead. Useful while you
are still setting the site up.

### Setting up Google sign-in

Leave the `google` block empty and the button never appears. To switch it on:

**Step 1 — your site must be on HTTPS.** Google refuses plain `http://`
redirect addresses. In cPanel check **Security → SSL/TLS Status** and run
AutoSSL if the domain has no certificate yet.

**Step 2 — create the OAuth client**

1. Open [console.cloud.google.com/apis/credentials](https://console.cloud.google.com/apis/credentials)
2. **Create Credentials → OAuth client ID → Web application**
3. Under **Authorised redirect URIs** add this exactly, with your own domain:

```
https://yourdomain.com/auth/google/callback
```

If the app lives in a sub-folder, include it:
`https://yourdomain.com/gamecraft/auth/google/callback`

4. Copy the **Client ID** and **Client secret**

**Step 3 — fill in `config.php`**

```php
'google' => [
    'client_id'     => '1234567890-abcdef.apps.googleusercontent.com',
    'client_secret' => 'GOCSPX-your-secret-here',
],
```

The **Continue with Google** button now appears on the sign-in and registration
pages.

### How Google accounts are matched

| Situation | What happens |
|---|---|
| Signed in with Google before | Signed straight in |
| Same email already registered with a password | The two are linked; either way works from now on |
| Nobody with that email | A new account is created on the Starter plan |

Linking by email is only allowed when Google reports the address as verified.
An unverified Google account is refused, because otherwise anybody could claim
somebody else's email address and walk into their account.

Google accounts get a random unknown password. If that person later wants a
password of their own, they use **Forgot password** to set one.

### Upgrading a site that is already installed

These features need one new table and two new columns. Nothing is lost and no
reinstall is needed:

1. Upload the new files over the old ones
2. Upload the `install` folder too, if you deleted it
3. Sign in as your administrator account
4. Open `https://yourdomain.com/install/upgrade.php`
5. Delete the `install` folder again

The upgrade only ever adds what is missing, and is safe to run twice.

---

## 5. Folder structure

```
gamecraft/
├── index.php               Single entry point, holds all the routing
├── config.php              ← You edit this (database details)
├── config.example.php      Reference copy
├── .htaccess               Apache config and security rules
├── dev-server.php          Local testing only
├── RUN-LOCAL-TEST.bat      Local testing only (Windows)
│
├── app/
│   ├── bootstrap.php       Startup: config, autoloader, session
│   ├── Core/               Router, Database, Auth, View, CSRF, Validator...
│   ├── Controllers/        One per feature area
│   ├── Models/             Project queries
│   ├── Services/           The business logic (see below)
│   └── Views/              Templates
│
├── assets/
│   ├── css/app.css         Interface styles
│   ├── css/print.css       Print layout
│   └── js/app.js           Interactions (no external libraries)
│
├── install/                ← DELETE after installing
│   ├── Schema.php          The 12 table definitions
│   ├── Seeder.php          Starting content
│   ├── install.sql         Dump for phpMyAdmin
│   └── export-sql.php      Regenerates install.sql
│
├── uploads/                Images (needs write permission, 755)
│   ├── library/            Library artwork — your files go here
│   └── backgrounds/        What users upload
│
└── storage/                Logs and cache (needs write permission, 755)
```

### The business logic (`app/Services/`)

| File | What it does |
|---|---|
| `Tiers.php` | The three plans and how entitlements inherit |
| `Difficulty.php` | Difficulty presets: 12/18/24 spaces, 60/90/120 cards |
| `MissionMatcher.php` | Auto-matches mission cards and generates variations |
| `MapComposer.php` | Composes the background with the map frame |
| `PromptGenerator.php` | Writes the map background prompts |
| `PrintBundle.php` | Assembles the print bundle in the required order |
| `Library.php` | Library queries plus real-artwork detection |
| `Uploader.php` | Safe image uploads |
| `Art.php` | Draws the SVG placeholder artwork |
| `Mailer.php` | Sends email over SMTP, written against the protocol directly |
| `GoogleAuth.php` | The OAuth 2.0 calls behind Sign in with Google |

---

## 6. How the product works

The product is an **assembler**.

```
1. Pick from library   Difficulty, theme, question subjects, map frame
        ↓
2. Copy the prompt     We write it; run it in an image generator
        ↓
3. Upload the image    Your background gets composed with the map frame
        ↓
4. Match the cards     Mission cards are pulled from the library automatically
        ↓
5. Export              Preview, swap anything you dislike, print to PDF
```

### Print order

The export always follows this sequence:

1. Game map *(A4 landscape)*
2. Story
3. How to play
4. Move cards — 8 of them
5. Mission cards — 60 / 90 / 120 depending on difficulty
6. Winner hero card
7. Player tokens *(accessory)*

### Exporting to PDF

Click **Export for print** → the print page opens in a new tab → click **Print / Save as PDF**.

In the browser's print dialog, set:
- **Destination**: Save as PDF
- **Margins**: None
- **Background graphics**: on ✔

Without *Background graphics* the colours and cut lines will not appear.

### How mission cards are generated

The library holds **15 base templates**, each with blanks for numbers and words:

```
Pattern:  "There are {a} rabbits in the meadow, and {b} more hop over..."
Answer:   "{a+b} rabbits"
Ranges:   a = 2..12,  b = 1..8
```

Random values are drawn for every blank, so 15 templates produce thousands of distinct questions. If one template runs out of variations, the system moves to another so that **no question ever repeats inside a single game**.

> The more subjects you select, the more variations you get. Step 4 shows exactly how many distinct questions are available and warns you if there are not enough.

---

## 7. Coverage against the SRS

### Implemented

| ID | Requirement |
|---|---|
| FR-01 → FR-03 | Sidebar navigation, plan display |
| FR-04 → FR-08 | Dashboard, banner, create button, blueprint import, *How it works* |
| FR-09 → FR-16 | Project list: cards, statuses, search, sort, grid/list, action menu, count |
| FR-17, FR-18 | 54 ready-made templates (the requirement is "over 50") |
| FR-19, FR-20 | Community |
| FR-21, FR-22 | Plan display and management |
| FR-23 | Question subjects and question count |
| FR-24 | Automatic mission card matching |
| FR-25 | Custom questions and sticker selection |
| FR-26 | Preview and card swapping |
| FR-27 | Print bundle in the required order |
| FR-28, FR-29 | Three plans with inherited entitlements |
| FR-30 | Background prompts per plan |
| FR-31 | Map composition from an uploaded background |
| FR-32 | Amazon / Etsy listings (Publisher plan) |
| FR-33 | Difficulty presets |
| FR-34 | Content production progress tracking |
| FR-35 | Mission variation algorithm |

### Out of scope (per SRS section 7)

- **Real-time AI Designer chat** — explicitly excluded by the SRS. The dashboard's *How it works* block was rewritten to describe the actual assembler flow from section 2.3, rather than reusing the four steps from the reference screenshot.
- **Payment gateway** — the Billing page switches plans directly; PayPal, Stripe and similar are not wired up.
- **Marketplace / community moderation** — no review workflow exists.

### Two contradictions in the SRS

**1. Move card count.** Section 8 says *"10 move cards"*, while section 10 and FR-33 specify a fixed **8 cards** per game.

This build follows **8 cards** (FR-33), because that is the numbered, binding functional requirement. To switch to 10, change one constant in `app/Services/Difficulty.php`:

```php
public const MOVE_CARDS_PER_GAME = 8;   // change to 10 if needed
```

**2. Cards per move card set.** Section 9 says *"5 designs × 8 cards"*; section 11 says *"20 designs — 12 cards each"*.

The build reads this as: each design set holds up to 12 artworks, but any single game uses 8 of them per FR-33. If that reading is wrong, it needs confirming with the client.

---

## 8. Troubleshooting

**The page is blank**

Turn on debug mode in `config.php`:
```php
'debug' => true,
```
Reload to see the actual error. **Set it back to `false` afterwards** — leaving it on exposes system details to visitors.

Also check `storage/logs/error.log`.

**Every page returns 404 except the home page**

`mod_rewrite` is not enabled. Use `index.php?r=projects` in the meantime, or ask your host to switch it on.

**"Could not connect to the database"**

- Re-check the database and user names in `config.php` — cPanel adds an account prefix such as `abcxyz_gamecraft`, and it has to match exactly.
- Confirm the user has **ALL PRIVILEGES** on that database.
- A few hosts use a host name other than `localhost`; ask your provider.

**Uploads fail**

- The `uploads` folder is not writable → set permissions to **755**.
- The image exceeds the server limit → in **cPanel → Select PHP Version → Options**, raise `upload_max_filesize` and `post_max_size`.

**Printed pages lose their colours or cut lines**

In the print dialog, enable **Background graphics** and set **Margins: None**.

**Password reset emails never arrive**

- Check `storage/logs/error.log` — the exact reply from the mail server is recorded there.
- `535` in the log means the username or password is wrong. The username is normally the full email address.
- Some hosts block outbound port 465. Try port `587` with `'encryption' => 'tls'`.
- Look in the recipient's spam folder, and make sure `from_email` is a real mailbox on your own domain.
- To check the rest of the flow without sending anything, set `'driver' => 'log'` and read `storage/logs/mail.log`.

**Google sign-in returns "redirect_uri_mismatch"**

The address registered with Google has to match what the app sends, character for character.
Turn on `'debug' => true` briefly and the error message will print the exact URI to paste into
the Google console. Remember it must be `https://`, and must include any sub-folder.

**Artwork still shows placeholders after uploading**

- Check the file name matches the code in `ARTWORK-FILE-LIST.txt` exactly (Linux hosting is case sensitive).
- Check the file is in the right sub-folder.
- Clear your browser cache (Ctrl+F5).

---

## Security built in

- Passwords hashed with `password_hash()` (bcrypt)
- CSRF protection on every state-changing request
- Every query uses prepared statements — no SQL injection
- All user data is escaped on output — no XSS
- Uploads are verified with `getimagesize()`, renamed randomly, and re-encoded to strip hidden data
- PHP execution blocked inside `uploads`
- Direct access blocked to `app`, `storage` and `config.php`
- Sign-in throttled to 6 failed attempts per minute
- Password reset links are stored only as a SHA-256 hash, expire after an hour and work once
- The reset page never reveals whether an email address has an account
- Google sign-in is guarded by a one-time state value and refuses unverified email addresses
- Project ownership enforced at the query level — nobody can open another user's project, even knowing its ID

---

*GameCraft Studio 1.0.0*
