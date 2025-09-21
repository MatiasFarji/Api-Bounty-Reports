<?php
/**
 * BaseScraper.php
 * Abstract class that defines what every scraper must implement
 */

abstract class BaseScraper {
    protected $sourceName; // Example: "HackerOne", "Bugcrowd"

    public function __construct($sourceName) {
        $this->sourceName = $sourceName;
    }

    /**
     * Each scraper must return an array of reports.
     * Report structure:
     * [
     *   'external_id' => string,
     *   'title'       => string,
     *   'full_text'   => string,
     *   'severity'    => string|null,
     *   'report_url'  => string,
     *   'published_at'=> string (Y-m-d H:i:s),
     *   'category'    => string|null,
     *   'program'     => string|null
     * ]
     */
    abstract public function scrape();

    public function getSourceName() {
        return $this->sourceName;
    }
}
