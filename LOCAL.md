# Run CAE Fix / Rhye locally (Local WP app)

Use **Local** with **MySQL**. The GitHub `Dockerfile` + `src/wp-config.php` are for **Render + PostgreSQL**; do **not** replace Local’s `wp-config.php` with the repo copy.

## One-time copy into the Local site

Open your site folder (`app/public/`).

Copy from this repo:

| From repo | Into Local `app/public/wp-content/` |
|-----------|-------------------------------------|
| `src/wp-content/themes/rhye/` | `themes/rhye/` |
| `src/wp-content/themes/rhye-child/` | `themes/rhye-child/` |
| `src/wp-content/mu-plugins/swiftfix-bootstrap.php` | `mu-plugins/swiftfix-bootstrap.php` |

**Do not** copy `src/wp-config.php` or `wp-content/db.php` (Postgres/PG4WP are Render-only).

## First visit = automatic setup

Open the site in the browser (front end, not only wp-admin). The **CAE Fix bootstrap** mu-plugin will **once per site**:

1. Activate **Rhye Child**
2. Install **Elementor** from wordpress.org if the plugin folder is missing (needs internet), or use files you add under `plugins/elementor/`
3. Activate Elementor
4. Import **`electrician-template.json`** into a **Home** page and set **Settings → Reading** (static Home + Blog)
5. Set site title / CAE Fix customizer defaults (override title with env **`SWIFTFIX_SITE_NAME`** on Render; on Local you can change in **Settings → General** after)

If something fails, check **wp-admin** for a red **CAE Fix setup** notice, or PHP/Apache logs.

## Then Render

Push the same `src/wp-content` tree; the Docker image already includes Elementor under `wp-content/plugins/`, so production usually skips the download step.
