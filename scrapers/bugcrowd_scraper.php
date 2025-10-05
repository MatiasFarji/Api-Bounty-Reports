<?php
require_once __DIR__ . '/BaseScraper.php';

class BugcrowdScraper extends BaseScraper
{
    public function __construct()
    {
        parent::__construct("Bugcrowd");
    }

    public function scrape()
    {
        global $requestCounter, $requestsJsonTemplate;
        $totalReportsCount = 0;
        $reports = [];

        $firstSourceScraping = (FIRST_SCRAPING || !file_exists(PATH_CACHE . 'bugcrowd.txt') ? true : false);
        $pageId = 1;

        do {
            //Get and paginate reports
            $position = 0;
            $requestCounter++;
            require PATH_HELPERS . '/RequestVariablesSetFunction.php';
            showInfoNetworkRequest($position, $requestCounter);
            $text = executeNetworkRequest($position, $requestCounter);
            resetVariablesJsonTemplate();

            $text = json_decode($text['responseBody'], true);
            if (is_array($text)) {
                $totalReportsCount = $text['pagination_meta']['total_pages'] ?? 0;
                if (isset($text['results'])) {
                    $pageId++;
                    foreach ($text['results'] as $reportData) {
                        if ($reportData['priority'] > 4) {
                            continue;
                        }
                        $reportPath = $reportData['disclosure_report_url'];

                        //Get details of a report
                        $position = 1;
                        $requestCounter++;
                        require PATH_HELPERS . '/RequestVariablesSetFunction.php';
                        showInfoNetworkRequest($position, $requestCounter);
                        $text = executeNetworkRequest($position, $requestCounter);
                        resetVariablesJsonTemplate();

                        $dom = new DOMDocument();
                        $dom->loadHTML($text["responseBody"], LIBXML_DTDVALID | LIBXML_NOERROR);
                        $reportDetailsList = htmlDomSearcher($dom, 'attribute', 'bc-stats bc-disclosure-stats', 'class', true);
                        $reportFullText = "";
                        if (!empty($reportDetailsList)) {
                            $reportDetailsList = conversionHtmlToArray($dom->saveHTML($reportDetailsList[0]), 'ul');

                            foreach ($reportDetailsList['li'] as $reportDetailRow) {
                                $reportDetailRowKey = null;
                                $reportDetailRowValue = null;
                                switch (true) {
                                    case isset($reportDetailRow['span']['0']['attributes']['innerHtml']):
                                        $reportDetailRowKey = trim($reportDetailRow['span']['0']['attributes']['innerHtml']);
                                        break;
                                    default:
                                        continue 2;
                                }

                                switch (true) {
                                    case isset($reportDetailRow['span']['1']['a']['0']['attributes']['innerHtml']):
                                        $reportDetailRowValue = trim($reportDetailRow['span']['1']['a']['0']['attributes']['innerHtml']);
                                        break;
                                    case isset($reportDetailRow['span']['1']['time']['0']['attributes']['innerHtml']):
                                        $reportDetailRowValue = trim($reportDetailRow['span']['1']['time']['0']['attributes']['innerHtml']);
                                        break;
                                    case isset($reportDetailRow['span']['1']['span']['0']['attributes']['innerHtml']):
                                        $reportDetailRowValue = trim($reportDetailRow['span']['1']['span']['0']['attributes']['innerHtml']);
                                        break;
                                    default:
                                        break;
                                }

                                switch ($reportDetailRowKey) {
                                    case 'VRT':
                                        $reportData['title'] .= " | " . $reportDetailRowValue;
                                        break;
                                    case 'Bug URL':
                                        $reportFullText = "Bug URL: " . $reportDetailRowValue . "\n";
                                        break;
                                    case 'Description':
                                        $reportFullText .= "Description: \n" . $reportDetailRowValue . "\n";
                                        break;
                                    default:
                                        break;
                                }
                            }
                        } else {
                            logWithColor("Not found details list", "i");
                        }

                        $fullReportSection = htmlDomSearcher($dom, 'tagName', 'section');
                        if (!empty($fullReportSection)) {
                            $reportFullText = $dom->saveHTML($fullReportSection[0]);
                        }

                        $report = [
                            'external_id' => $reportData['id'],
                            'title'       => $reportData['title'],
                            'full_text'   => $reportFullText,
                            'report_url'  => $reportPath,
                            'published_at' => date('Y-m-d H:i:s', strtotime($reportData['disclosed_at'])),
                            'category'    => null,
                            'program'     => $reportData['engagement_name']
                        ];

                        if ((strtotime($reportData['disclosed_at']) < strtotime(DATE_LAST_SCRAPING)) && !$firstSourceScraping) {
                            break 2;
                        }

                        $reports[] = $report;

                        usleep(500000);
                    }
                } else break;
            } else logWithColor("No es array la rta de la web " . json_encode($text), "i");
        } while ($totalReportsCount > $pageId);
        return $reports;
    }
}
