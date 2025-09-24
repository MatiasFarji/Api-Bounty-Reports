<?php
require_once __DIR__ . '/BaseScraper.php';

class HackerOneScraper extends BaseScraper
{
    public function __construct()
    {
        parent::__construct("HackerOne");
    }

    public function scrape()
    {
        global $requestCounter, $requestsJsonTemplate;
        $totalReportsCount = 0;
        $reports = [];

        $sortDirection = (FIRST_SCRAPING ? "ASC" : "DESC");
        $fromIndex = 0;

        do {
            //Gets and paginates the main information of reports
            $position = 0;
            $requestCounter++;
            require PATH_HELPERS . '/RequestVariablesSetFunction.php';
            showInfoNetworkRequest($position, $requestCounter);
            $text = executeNetworkRequest($position, $requestCounter);
            resetVariablesJsonTemplate();

            $text = json_decode($text['responseBody'], true);
            if (is_array($text)) {
                $totalReportsCount = $text['data']['search']['total_count'] ?? 0;
    
                if (isset($text['data']['search']['nodes'])) {
                    $fromIndex += count($text['data']['search']['nodes']);
                    foreach ($text['data']['search']['nodes'] as $reportData) {
                        $reportId = $reportData['_id'];
    
                        //Get details of a report
                        $position = 1;
                        $requestCounter++;
                        require PATH_HELPERS . '/RequestVariablesSetFunction.php';
                        showInfoNetworkRequest($position, $requestCounter);
                        $text = executeNetworkRequest($position, $requestCounter);
                        $text = json_decode($text['responseBody'], true);
                        resetVariablesJsonTemplate();
                        $report = [
                            'external_id' => $reportData['_id'],
                            'title'       => $reportData['report']['title'],
                            'full_text'   => $text['vulnerability_information'],
                            'report_url'  => $reportData['report']['url'],
                            'published_at' => date('Y-m-d H:i:s', strtotime($reportData['report']['disclosed_at'])),
                            'category'    => null,
                            'program'     => $reportData['team']['name']
                        ];

                        if (strtotime($report['published_at']) < strtotime(DATE_LAST_SCRAPING)) break 2;
    
                        $reports[] = $report;

                        usleep(500000);
                    }
                } else break;
            } else logWithColor("No es array la rta de la web " . json_encode($text), "i");
        } while ($totalReportsCount > $fromIndex);
        return $reports;
    }
}
