<?php
require_once __DIR__ . '/BaseScraper.php';

class BugcrowdScraper extends BaseScraper {
    public function __construct() {
        parent::__construct("Bugcrowd");
    }

    public function scrape() {
        // Example: return multiple reports
        return [
            [
                'external_id' => 'BC-54321',
                'title'       => 'SQL Injection in login form',
                'full_text'   => "Detailed description of SQLi...",
                'severity'    => 90,
                'report_url'  => 'https://bugcrowd.com/reports/54321',
                'published_at'=> date('Y-m-d H:i:s'),
                'category'    => 'SQL Injection',
                'program'     => 'Acme Corp'
            ],
            [
                'external_id' => 'BC-54322',
                'title'       => 'CSRF in settings page',
                'full_text'   => "Details of the CSRF bug...",
                'severity'    =>  50,
                'report_url'  => 'https://bugcrowd.com/reports/54322',
                'published_at'=> date('Y-m-d H:i:s'),
                'category'    => 'CSRF',
                'program'     => 'Acme Corp'
            ]
        ];
    }
}
