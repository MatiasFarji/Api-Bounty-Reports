<?php
require_once __DIR__ . '/BaseScraper.php';

class HackerOneScraper extends BaseScraper {
    public function __construct() {
        parent::__construct("HackerOne");
    }

    public function scrape() {
        // Example static data (replace with real scraping logic)
        return [
            [
                'external_id' => 'H1-12345',
                'title'       => 'Reflected XSS in profile page',
                'full_text'   => "Details of the vulnerability...",
                'severity'    => 50,
                'report_url'  => 'https://hackerone.com/reports/12345',
                'published_at'=> date('Y-m-d H:i:s'),
                'category'    => 'XSS',
                'program'     => 'Yahoo'
            ]
        ];
    }
}
