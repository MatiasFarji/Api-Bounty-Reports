<?php

/**
 * Logs a message to the console with ANSI color codes based on the given type.
 *
 * Works only if the constant MODE_DEV is defined and set to true.
 * Supported color types:
 *   - 'rg' : Black text on white background
 *   - 'rp' : White text on black background
 *   - 'w'  : Yellow text on black background (warning)
 *   - 'i'  : Blue text on black background (info)
 *   - 'e'  : Red text on black background (error/important)
 *   - 'reset' : Reset to default colors
 *
 * @param string $message The message to display in the console.
 * @param string $type    The color type key (e.g., "w", "i", "e", "rg", "rp").
 *
 * @return void
 */

function logWithColor($message, $type)
{
  if (defined('MODE_DEV') && MODE_DEV) {
    $colors = [
      'rg' => "\033[30;47m",
      'rp' => "\033[37;40m",
      'w' => "\033[33;40m",
      'i' => "\033[34;40m",
      'e' => "\033[31;40m",
      'reset' => "\033[0m"
    ];

    if (isset($colors[$type])) {
      $colorCode = $colors[$type];
    } else {
      $colorCode = $colors['reset'];
    }

    echo $colorCode . $message . $colors['reset'] . PHP_EOL;
  }
}

/**
 * Displays information about a network request from the request template.
 *
 * This function retrieves a request at the given position within the
 * global $requestsJsonTemplate array and logs its details in color-coded
 * format to the console. Information includes position, counter, HAR number,
 * description, method, URL, and optionally POST data if present.
 *
 * Globals:
 *   - $requestsJsonTemplate : array containing the decoded request templates.
 *
 * @param int $position       The index of the request within the template array.
 * @param int $requestCounter The current counter or iteration number of the request.
 *
 * @return void
 */
function showInfoNetworkRequest($position, $requestCounter)
{
  global $requestsJsonTemplate;

  // Verify if the position exists
  if (!isset($requestsJsonTemplate[$position])) {
    logWithColor("Position {$position} does not exist in requestsJsonTemplate.", "e");
    return;
  }

  // Get the request at the given position
  $request = $requestsJsonTemplate[$position];

  logWithColor("Position: $position  |  Counter: $requestCounter", "i");
  logWithColor("Description: " . $request["description"], "i");
  logWithColor("METHOD:  " . $request['method'], 'w');

  // Show the URL with white background and black text
  logWithColor("URL: " . $request['url'], "rg");

  // Check if there is POST data
  if (isset($request['postData']) && !empty($request['postData']['text'])) {
    // Show postData as raw text
    logWithColor("Post Data Text: " . $request['postData']['text'], "rp");
  } elseif (isset($request['postData']['params']) && !empty($request['postData']['params'])) {
    // Show POST params formatted with http_build_query
    $params = http_build_query($request['postData']['params']);
    logWithColor("Post Data Params: " . $params, "rp");
  }
}

/**
 * Executes a network request at a given position in the request sequence.
 *
 * @param int $position        The index/position of the request in the queue.
 * @param int $requestCounter  The current request counter (e.g., iteration or ID).
 *
 * @return array{
 *     httpCode: int,
 *     responseBody: string,
 *     responseHeaders: string,
 *     responseCookies: string
 * } Returns an associative array containing:
 *         - httpCode:        The HTTP status code of the response.
 *         - responseBody:    The body/content of the response.
 *         - responseHeaders: The raw response headers.
 *         - responseCookies: The response cookies, if any.
 */

function executeNetworkRequest($position, $requestCounter)
{
  global $executedRequests, $requestsJsonTemplate, $scraperName;

  // Initialize
  $response = [
    "httpCode" => 0,
    "responseBody" => "",
    "responseHeaders" => "",
    "responseCookies" => ""
  ];

  // Verify if request template exists
  if (!isset($requestsJsonTemplate[$position])) {
    return $response; // Return failure / empty
  }

  // Get Request template properties
  $request = $requestsJsonTemplate[$position];
  $url = $request['url'];
  $method = $request['method'];
  $headers = $request['headers'] ?? [];
  $postData = $request['postData'] ?? null;

  $ch = curl_init($url);

  // Set Curl properties (Body in case is POST)
  if (strtoupper($method) === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    if ($postData) {
      if (isset($postData['params'])) {
        // For application/x-www-form-urlencoded
        $postFields = [];
        foreach ($postData['params'] as $param) {
          $postFields[$param['name']] = $param['value'];
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
      } else {
        // For application/json or text
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData['text']);
        $headers[] = ["name" => "Content-Type", "value" => "application/json"];
      }
    }
  }

  if (!empty($headers)) {
    $curlHeaders = [];
    foreach ($headers as $header) {
      $curlHeaders[] = "{$header['name']}: {$header['value']}";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
  }

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

  // Cookies Handle with File Netscape, it validates if has previous requests to use it or create it
  $cookieFile = PATH_CACHE . $scraperName . ".txt";
  if (!empty($executedRequests)) {
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
  } else {
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
  }

  curl_setopt($ch, CURLOPT_HEADER, 1);
  $response['responseBody'] = curl_exec($ch);

  $response['httpCode'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $response['responseHeaders'] = substr($response['responseBody'], 0, $headerSize);
  $response['responseBody'] = substr($response['responseBody'], $headerSize);

  curl_close($ch);

  $headersArray = explode("\r\n", trim($response['responseHeaders']));
  $contentType = '';
  $contentEncoding = '';
  foreach ($headersArray as $header) {
    if (stripos($header, 'Content-Type:') === 0) {
      $contentType = trim(substr($header, strlen('Content-Type:')));
    }

    if (stripos($header, 'Content-Encoding:') === 0) {
      $contentEncoding = trim(substr($header, 17));
    }
  }

  // Handle compressed content
  logWithColor("Response ContentEncoding: $contentEncoding", "i");
  logWithColor("Response ContentType: $contentType", "i");

  switch (strtolower($contentEncoding)) {
    case 'gzip':
      $decoded = gzdecode($response['responseBody']);
      if ($decoded === false) {
        logWithColor("Failed to decompress gzip response.", "e");
      } else {
        $response['responseBody'] = $decoded;
      }
      break;

    case 'deflate':
      $decoded = gzinflate($response['responseBody']);
      if ($decoded === false) {
        logWithColor("Failed to decompress deflate response.", "e");
      } else {
        $response['responseBody'] = $decoded;
      }
      break;

    case 'compress':
      $decoded = gzuncompress($response['responseBody']);
      if ($decoded === false) {
        logWithColor("Failed to decompress compress response.", "e");
      } else {
        $response['responseBody'] = $decoded;
      }
      break;

    case 'br':
      logWithColor("Brotli compression detected but not supported natively by PHP.", "w");
      break;

    case '':
    case 'identity':
      // No compression
      break;

    default:
      logWithColor("Unknown or unsupported Content-Encoding: $contentEncoding", "w");
      break;
  }


  // Get extension based in Content-Type
  $extension = 'html'; // Default
  switch (true) {
    case stripos($contentType, 'application/pdf') !== false:
      $extension = 'pdf';
      break;
    case stripos($contentType, 'application/zip') !== false:
      $extension = 'zip';
      break;
    case stripos($contentType, 'application/json') !== false:
      $extension = 'json';
      break;
    case stripos($contentType, 'text/plain') !== false:
      $extension = 'txt';
      break;
    case stripos($contentType, 'application/x-www-form-urlencoded') !== false:
      $extension = 'txt';
      break;
    case stripos($contentType, 'text/css') !== false:
      $extension = 'css';
      break;
    case stripos($contentType, 'application/javascript') !== false:
    case stripos($contentType, 'text/javascript') !== false:
      $extension = 'js';
      break;
    case stripos($contentType, 'image/jpeg') !== false:
      $extension = 'jpg';
      break;
    case stripos($contentType, 'image/png') !== false:
      $extension = 'png';
      break;
    case stripos($contentType, 'image/gif') !== false:
      $extension = 'gif';
      break;
    case stripos($contentType, 'image/svg+xml') !== false:
      $extension = 'svg';
      break;
    case stripos($contentType, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') !== false:
      $extension = 'xlsx';
      break;
    case stripos($contentType, 'application/vnd.ms-excel') !== false:
      $extension = 'xls';
      break;
    case stripos($contentType, 'text/csv') !== false:
      $extension = 'csv';
      break;
    default:
      break;
  }


  logWithColor("Response HTTPCode: " . $response['httpCode'], 'rg');

  $executedRequests[] = $requestCounter;

  // Return response Array
  return $response;
}

/**
 * Restores the request JSON template from file.
 *
 * This function reloads the template for the current scraper,
 * undoing any variable replacements applied during requests.
 * The template is read from a JSON file located in PATH_TEMPLATES
 * and decoded into a PHP array.
 *
 * Globals:
 *   - $requestsJsonTemplate : array containing the decoded request template.
 *   - $scraperName          : string used to locate the template file.
 *
 * @return void
 */
function resetVariablesJsonTemplate()
{
  global $requestsJsonTemplate, $scraperName;
  $requestsJsonTemplate = file_get_contents(PATH_TEMPLATES . $scraperName . "_requests_template.json");
  $requestsJsonTemplate = json_decode($requestsJsonTemplate, true);
}


/**
 * Classify a report into the best matching subcategory using a keyword taxonomy.
 *
 * The function counts literal keyword occurrences for each subcategory across the
 * report title and content, then returns the subcategory with the highest score.
 * It handles:
 *  - Alphanumeric tokens with word boundaries (e.g., "sqli", "csrf")
 *  - Hyphen/underscore tokens with safe non-word boundaries
 *  - “Special” tokens containing punctuation via literal matches (e.g., "alert(1)", "%0d%0a", "php://filter")
 *
 * Expected $taxonomyMap format (json_decode(true) of your file):
 * [
 *   "subcategory_name" => [
 *     "keywords" => ["token1","token2", ...]
 *   ],
 *   ...
 * ]
 *
 * @param string $title         Report title (plain text).
 * @param string $content       Report body/content (plain text).
 * @param array  $taxonomyMap   Associative array of subcategory => ['keywords' => [...]].
 *
 * @return string|null          Best matching subcategory name, or null if no matches.
 */
function classifyByTaxonomyKeywords(string $title, string $content, array $taxonomyMap): ?string
{
    // Prepare haystacks (lowercased for case-insensitive matching)
    $titleLc   = mb_strtolower($title, 'UTF-8');
    $contentLc = mb_strtolower($content, 'UTF-8');

    // Helper: count occurrences of a token in a lowercase haystack
    $countToken = static function (string $token, string $haystack): int {
        if ($token === '') {
            return 0;
        }

        // Decide matching strategy based on token characters
        $isAlphaNumUnderscore = (bool)preg_match('/^[a-z0-9_]+$/i', $token);
        $isAlphaNumUnderscoreHyphen = (bool)preg_match('/^[a-z0-9_-]+$/i', $token);

        // Use regex patterns with boundaries when we safely can
        if ($isAlphaNumUnderscore) {
            // \b works well for [A-Za-z0-9_]
            $pattern = '/\b' . preg_quote($token, '/') . '\b/u';
            return preg_match_all($pattern, $haystack, $m) ?: 0;
        }

        if ($isAlphaNumUnderscoreHyphen) {
            // Hyphen is not a word char; use custom "not word-char" boundaries (letters/digits/_)
            $pattern = '/(?<![A-Za-z0-9_])' . preg_quote($token, '/') . '(?![A-Za-z0-9_])/u';
            return preg_match_all($pattern, $haystack, $m) ?: 0;
        }

        // Fallback: literal substring match via regex (safe-quoted), anywhere in text
        $pattern = '/' . preg_quote($token, '/') . '/u';
        return preg_match_all($pattern, $haystack, $m) ?: 0;
    };

    $bestSubcategory = null;
    $bestScore       = 0;

    foreach ($taxonomyMap as $subcategory => $data) {
        if (!isset($data['keywords']) || !is_array($data['keywords'])) {
            continue;
        }

        $score = 0;

        // Count occurrences in both title and content (equal weight)
        foreach ($data['keywords'] as $rawToken) {
            $token = mb_strtolower((string)$rawToken, 'UTF-8');
            if ($token === '' || mb_strlen($token, 'UTF-8') < 2) {
                continue; // skip empty/too-short noise tokens
            }
            $score += $countToken($token, $titleLc);
            $score += $countToken($token, $contentLc);
        }

        if ($score > $bestScore) {
            $bestScore       = $score;
            $bestSubcategory = $subcategory;
        }
    }

    return $bestSubcategory;
}