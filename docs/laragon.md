# Local Development with Laragon (Windows)

> **Laragon** is an all-in-one portable development environment for Windows.  It bundles Apache or Nginx, PHP, MySQL/MariaDB, Node.js, and many helpful utilities in a single installer.  If you are accustomed to XAMPP or WAMP, Laragon is a modern alternative that boots faster, isolates projects, and offers easy SSL support out of the box.

## Why Laragon?

| Feature | Laragon | XAMPP |
|---------|---------|-------|
| One-click enable/disable services | ✅ | ➖ |
| Automatic virtual hosts per project | ✅ | ➖ |
| Portable (no registry footprint) | ✅ | ➖ |
| Built-in **ngrok** & SSL generator | ✅ | ➖ |
| Lightweight memory usage | ✅ | ➖ |
| Ships with **Composer**, **npm**, **git** | ✅ | ➖ |

## Prerequisites

* **Windows 10/11** (64-bit)
* 1 GB free disk space
* Administrator privileges (first-time install only)

> **Alternative**: If you prefer manual installation of individual components, see our [Prerequisites Installation Guide](installation.md) for detailed instructions.

## Installation Steps

1. **Download Laragon**  
   Visit the official site and grab the *Full* installer (includes PHP, MySQL & Node):  
   <https://laragon.org/download>

2. **Run the Installer**  
   * Accept defaults or choose a custom path (e.g. `C:\laragon`).  
   * Keep the option *Add Laragon to PATH* checked for CLI convenience.

3. **Start Services**  
   Launch *Laragon* → click **Start All**.  Apache (or Nginx), MySQL and other tools will boot within seconds.

4. **Enable Automatic Virtual Host (optional but recommended)**  
   *Menu → Preferences → General → Auto virtual hosts*  
   With this enabled, any folder inside `C:\laragon\www\` becomes addressable at `http://<folder>.test`.

5. **Create the Project Folder**  
   ```powershell
   cd C:\laragon\www
   git clone https://github.com/angelo-domingo118/d-agriventory.git
   cd d-agriventory
   ```

6. **Install PHP Dependencies**  
   Laragon ships with *Composer* pre-installed:
   ```bash
   composer install
   ```

7. **Configure the Environment**  
   ```bash
   copy .env.example .env
   # Edit .env and set DB_ credentials (see below)
   php artisan key:generate
   ```

8. **Create the Database**  
   * Use **HeidiSQL** (bundled) or MySQL CLI:
   ```sql
   CREATE DATABASE `d-agriventory` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
   * Update `.env` → `DB_DATABASE=d-agriventory`, `DB_USERNAME=root`, `DB_PASSWORD=` (empty by default).

9. **Run Migrations & Seeders**  
   ```bash
   php artisan migrate --seed
   ```

10. **Install Front-end Assets & Start Vite**  
    ```bash
    npm install
    npm run dev
    ```

11. **Access the App**  
    With auto-vhosts enabled: <http://d-agriventory.test>  
    Without: <http://localhost/d-agriventory/public>

## Common Laragon Tips

* **Switch PHP versions** – *Menu → PHP → Version*.  Laragon downloads versions on-demand.
* **Change Apache ↔ Nginx** – *Menu → Preferences → Services & Ports*.
* **SSL for `.test` domain** – Right-click the project folder in Laragon panel → *SSL* to get `https://d-agriventory.test`.
* **Database Admin** – Click *MySQL* button → launches **HeidiSQL** connected to local server.

## First-time Admin Login

Run the `AdminUserSeeder` (step 9) and then log in with:

```text
Email : admin@example.com
Password : password
```

> Credentials can be changed in `database/seeders/AdminUserSeeder.php` if needed.

## Troubleshooting

| Issue | Fix |
|-------|-----|
| *404 at `http://d-agriventory.test`* | Ensure auto-vhosts are enabled and Laragon has been restarted. |
| *MySQL port conflict* | Menu → MySQL → Port — assign a free port (e.g. 3308) and update `.env`. |
| *"Class not found" after pulling updates* | Run `composer install` and `php artisan optimize:clear`. |

---

### Further Reading

* Laragon Docs: <https://laragon.org/docs>
* Laravel Installation: <https://laravel.com/docs/12.x/installation>
* Vite (Laravel): <https://laravel.com/docs/12.x/vite> 