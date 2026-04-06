# Run SwiftFix / Rhye locally (Local WP app)

Use **Local** with **MySQL** first. The GitHub `Dockerfile` + `src/wp-config.php` target **Render + PostgreSQL (PG4WP)**; they are not what Local generates, so **do not replace Local’s `wp-config.php`** with the repo copy.

## 1. Create a site in Local

- **PHP** 8.2+ (match production if you can).
- **Web server** Apache or nginx (either is fine).
- **Database** MySQL (Local default).

Finish the WordPress install in the browser (language, admin user, etc.).

## 2. Copy theme files from this repo into the Local site

Open the site folder: **Local → site → right‑click → Open site shell** or **Reveal in Finder**. Your WordPress root is usually `app/public/`.

Copy into `app/public/wp-content/`:

| From repo | Into Local `app/public/wp-content/` |
|-----------|-------------------------------------|
| `src/wp-content/themes/rhye/` | `themes/rhye/` (merge / replace) |
| `src/wp-content/themes/rhye-child/` | `themes/rhye-child/` |
| `src/wp-content/mu-plugins/*.php` | `mu-plugins/` (create folder if missing) |

**Do not** copy `src/wp-config.php` over Local’s `wp-config.php`.

**Do not** add `wp-content/db.php` on Local. That file is only for **PostgreSQL + PG4WP** on Render; with MySQL it would break the site.

## 3. Activate the theme

In **wp-admin → Appearance → Themes**, activate **Rhye Child** (parent **Rhye** must stay in `themes/rhye/`).

## 4. Match the “real” ThemeForest look

- Install **Elementor** (**Plugins → Add New**).
- Import **demo / kit** from your ThemeForest package (Merlin wizard, XML, or Elementor templates), or build a **Home** page in Elementor.
- **Settings → Reading**: set homepage to a **static page** (e.g. Home) once that page exists.

**Appearance → Customize → SwiftFix Settings** controls business name, phone, etc.

## 5. When Local is good → Render again

- Commit and push the same `src/wp-content/themes/` and `mu-plugins/` (and any plugin code you add under `src/wp-content/plugins/` if you choose to vendor them).
- Recreate the stack from **`render.yaml`** (Blueprint) so `DATABASE_URL`, disk, and Docker build match the repo.

The Render image will keep using **Postgres + PG4WP** and the **Docker** `wp-config` path; Local stays on **MySQL + stock `wp-config`**.
