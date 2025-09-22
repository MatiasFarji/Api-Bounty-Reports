<?php
/**
 * run_scrapers.php
 * Executes all scrapers in this directory and inserts results into DB
 */

define('MODE_DEV', true);
define('PATH_CACHE', __DIR__ . "/../cache/");
define('PATH_TEMPLATES', __DIR__ . "/");
define('PATH_HELPERS', __DIR__ . "/../src/Utils/");
define('FIRST_SCRAPING', (file_exists(PATH_CACHE . "last_executed.txt") ? false : true));

require_once PATH_HELPERS . '/Database.php';
require_once PATH_HELPERS . 'Helpers.php';
require_once __DIR__ . '/BaseScraper.php';

$position = 0;
$requestCounter = -1;

// Load all scraper files except BaseScraper
foreach (glob(__DIR__ . '/*_scraper.php') as $file) {
    require_once $file;
}

// Initialize DB
$db = Database::getInstance()->getConnection();

// Iterate over all scraper classes
foreach (get_declared_classes() as $class) {
    if (is_subclass_of($class, 'BaseScraper')) {
        $scraper = new $class();
        $scraperName = str_replace("scraper", "", strtolower($scraper->getSourceName()));
        $requestsJsonTemplate = json_decode(file_get_contents(PATH_TEMPLATES . $scraperName . "_requests_template.json"), true);

        echo "[*] Running scraper: " . $scraperName . PHP_EOL;
        $reports = $scraper->scrape();

        // Ensure source exists
        $stmt = $db->prepare("
            INSERT INTO sources (name) VALUES (:name)
            ON CONFLICT (name) DO UPDATE SET name = EXCLUDED.name
            RETURNING id
        ");
        $stmt->execute([':name' => $scraperName]);
        $sourceId = $stmt->fetchColumn();

        $inserted = 0;
        $skipped  = 0;

        foreach ($reports as $report) {
            // Ensure category exists (if provided)
            $categoryId = null;
            if (!empty($report['category'])) {
                $stmt = $db->prepare("
                    INSERT INTO categories (name) VALUES (:name)
                    ON CONFLICT (name) DO UPDATE SET name = EXCLUDED.name
                    RETURNING id
                ");
                $stmt->execute([':name' => $report['category']]);
                $categoryId = $stmt->fetchColumn();
            }

            // Ensure program exists (if provided)
            $programId = null;
            if (!empty($report['program'])) {
                $stmt = $db->prepare("
                    INSERT INTO programs (name) VALUES (:name)
                    ON CONFLICT (name) DO UPDATE SET name = EXCLUDED.name
                    RETURNING id
                ");
                $stmt->execute([':name' => $report['program']]);
                $programId = $stmt->fetchColumn();
            }

            // Check if report already exists
            $stmt = $db->prepare("SELECT id FROM reports WHERE external_id = :external_id LIMIT 1");
            $stmt->execute([':external_id' => $report['external_id']]);
            $exists = $stmt->fetchColumn();

            if ($exists) {
                $skipped++;
                continue;
            }

            // Insert new report, generating UUIDv7 from published_at (or current time if NULL)
            $stmt = $db->prepare("
                INSERT INTO reports (
                    id, source_id, category_id, program_id,
                    external_id, title, full_text, severity, report_url, published_at
                )
                VALUES (
                    gen_uuid_v7(:published_at), :source_id, :category_id, :program_id,
                    :external_id, :title, :full_text, :severity, :report_url, :published_at
                )
            ");

            $stmt->execute([
                ':published_at' => $report['published_at'] ?? null,
                ':source_id'    => $sourceId,
                ':category_id'  => $categoryId,
                ':program_id'   => $programId,
                ':external_id'  => $report['external_id'],
                ':title'        => $report['title'],
                ':full_text'    => $report['full_text'],
                ':severity'     => $report['severity'] ?? null,
                ':report_url'   => $report['report_url'],
            ]);

            $inserted++;
        }

        echo "[✔] $inserted reports inserted, $skipped skipped for " . $scraperName . PHP_EOL;
    }
}

file_put_contents(PATH_CACHE . "last_executed.txt", date("Y-m-d H:i:s"));