# Bounty Hunting Reports API

Centralized API to scrape, normalize, categorize, and query public bug bounty reports from multiple sources.

**Public Postman collection:**  
👉 https://www.postman.com/matt-kingst/matt-kingst/collection/kdrw1ow/bounty-hunting-reports?action=share&creator=26615491

> Use the collection to explore endpoints and run requests quickly.

---

## 🌐 Public instance

A public, already-initialized deployment of this repository is available at:

**Base URL:** https://api-bounty-reports.mattkingst.com

- Planned: add more scrapers over time.
- Data refresh cadence: **every 24 hours**.

> You can use this hosted API directly if you prefer not to set up locally.

---

## 🚀 Quick start

```bash
# 1) Clone the repository
git clone https://github.com/MatiasFarji/Api-Bounty-Reports.git
cd Api-Bounty-Reports

# 2) Configure PostgreSQL (installs PG, creates role/db, applies schema, exports env vars)
bash configure/database_configure.sh

# 3) Sync taxonomy (categories + subcategories with priorities)
php src/Config/SyncTaxonomy.php

# 4) Run scrapers (collects reports; may take hours on first run)
php scrapers/run_scrapers.php

# 5) Start the API (PHP built‑in server with multiple workers)
PHP_CLI_SERVER_WORKERS=20 php -S 0.0.0.0:8000 router.php
```

**Environment variables** (set by the installer script in your `~/.bashrc`):
- `DB_USER`
- `DB_PASS`
- `DB_NAME`

---

## 🧱 Project structure

```
.
├── configure/
│   └── database_configure.sh           # Installs PG + PHP, creates DB/user, applies schema, exports env vars
├── public/
│   └── index.php                       # Entry point for API requests
├── src/
│   ├── Config/
│   │   └── SyncTaxonomy.php            # CLI script: sync Bugcrowd/CWE taxonomy (categories + subcategories)
│   ├── Controllers/                    # HTTP controllers
│   ├── Models/                         # Database models (Report, Category, Subcategory, ...)
│   ├── Routes/                         # Route definitions (reports, sources, categories, programs, ...)
│   └── Utils/                          # Database connection, router, helpers, taxonomy keywords, etc.
├── scrapers/
│   ├── BaseScraper.php                 # Base class for scrapers
│   ├── hackerone_scraper.php           # Current scraper implementation
│   └── run_scrapers.php                # Orchestrates all scrapers and inserts into DB
├── router.php                          # Global router for PHP built‑in server
└── LICENSE.md                          # GPL v3
```

---

## 🔎 API usage

After starting the server, call endpoints under `http://localhost:8000/` (or use the public instance base URL).

**Example (cURL): get recent P1/P2 HackerOne reports (limit 50) – Public instance**

```bash
curl "https://api-bounty-reports.mattkingst.com/api/v1/reports?severity=P1,P2&source_id=1&limit=50"   -H "Accept: application/json"
```

**Example (cURL): local instance**

```bash
curl "http://localhost:8000/api/v1/reports?severity=P1,P2&source_id=1&limit=50"   -H "Accept: application/json"
```

**Filtering capabilities** (via query params; validated by the router):
- `source_id` — comma‑separated numeric IDs (e.g., `1,2,3`)
- `subcategory_id` — comma‑separated numeric IDs
- `program_id` — comma‑separated numeric IDs
- `severity` — comma‑separated: `P1,P2,P3,P4,P5`
- `date_from`, `date_to` — `YYYY-mm-dd` or `YYYY-mm-dd HH:ii:ss`
- `limit` — max rows (default 200, capped to 1000)
- `sort_by` — one of: `published_at`, `title`, `scraped_at`
- `order` — `ASC` or `DESC`

---

## 🧠 Taxonomy

- The taxonomy sync pulls from Bugcrowd's VRT → CWE mapping and populates `categories` and `subcategories`.
- A keywords map is used for naïve text classification when a scraper cannot supply a subcategory explicitly.
- Subcategory priority (P1…P5) is stored at the subcategory level and used for filtering.

Run the sync any time you want to refresh taxonomy:

```bash
php src/Config/SyncTaxonomy.php
```

---

## 🧪 Scrapers

- ✅ **HackerOne** (initial data source)
- ⏳ Planned: Bugcrowd, Intigriti, YesWeHack, etc.

Scrapers should:
1. Fetch raw items from the source.
2. Normalize to the internal schema.
3. Provide `external_id`, `title`, `full_text`, `report_url`, `published_at`, and optionally `program` & `subcategory`.
4. If `subcategory` is missing, the main runner will try to classify using the taxonomy keywords.

Run all scrapers:

```bash
php scrapers/run_scrapers.php
```

---

## ⚖️ License

This project is licensed under **GNU General Public License v3.0 (GPL‑3.0)**.  
See [`LICENSE.md`](./LICENSE.md) for details.

---

## 🤝 Contributing

1. Fork the repo  
2. Create a feature branch  
3. Commit changes with clear messages  
4. Open a Pull Request

Please keep the project GPL‑compliant and preserve author attribution.

---

## 💡 Tips & Troubleshooting

- If PostgreSQL authentication fails (`Peer authentication failed`), ensure you connect with the created role and that the installer finished successfully.
- The first scraping run may take a long time; subsequent runs are incremental.
- The PHP built‑in server is fine for development. For production, consider a multi‑process SAPI (e.g., FPM + nginx) if you need higher concurrency and stability.

---

## 📌 Disclaimer & Opt-out

This API and repository are based on **publicly available information** collected from bug bounty programs and related sources.  
Data is gathered through **responsible scraping**, with delays and limitations to avoid impacting third-party infrastructure.  

The project is **open source** and strictly non-commercial.  
There is no official affiliation with HackerOne, Bugcrowd, or any other platforms whose data may appear here.  

### Opt-out
If you are the owner of any of the data published in this API and wish to have it **removed or excluded**, you can request it by contacting:  

📧 `matiasfarji@gmail.com`  

Requests will be handled promptly and with full commitment.  

---