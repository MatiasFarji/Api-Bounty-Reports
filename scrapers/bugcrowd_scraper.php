<?php
require_once __DIR__ . '/BaseScraper.php';

class BugcrowdScraper extends BaseScraper {
    public function __construct() {
        parent::__construct("Bugcrowd");
    }

    public function scrape() {
        // Example: return multiple reports
        return [
        ];
    }
}
