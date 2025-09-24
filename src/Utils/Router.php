<?php
class Router
{
    private $routes = [];

    public function add($method, $path, $handler)
    {
        $this->routes[] = compact('method', 'path', 'handler');
    }

    public function dispatch($method, $uri)
    {
        foreach ($this->routes as $route) {
            $pattern = "@^" . preg_replace('/\{(\w+)\}/', '(?P<\1>[^/]+)', $route['path']) . "$@";

            if ($method === $route['method'] && preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Validate path params
                $params = $this->validateRouteParams($params);

                // Validate query params
                $queryParams = $_GET ?? [];
                $queryParams = $this->validateQueryParams($queryParams);

                return call_user_func_array($route['handler'], [$params, $queryParams]);
            }
        }

        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not Found']);
        exit;
    }

    private function validateRouteParams(array $params): array
    {
        foreach ($params as $key => $value) {
            switch ($key) {
                case 'id':
                    if (!preg_match(
                        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                        $value
                    )) {
                        $this->badRequest("Invalid UUID format in route param '$key'");
                    }
                    break;

                case 'source_id':
                    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
                        $this->badRequest("Invalid source_id in route");
                    }
                    break;

                case 'category_id':
                case 'program_id':
                    if (!ctype_digit($value)) {
                        $this->badRequest("Invalid numeric param '$key' in route");
                    }
                    $params[$key] = (int)$value;
                    break;
            }
        }
        return $params;
    }

    private function validateQueryParams(array $query): array
    {
        $validated = [];
        foreach ($query as $key => $value) {
            switch ($key) {
                case 'id':
                    if (preg_match('/^[0-9a-fA-F-]{36}$/', $value)) {
                        $validated[$key] = $value;
                    }
                    break;

                case 'source_id':
                    if (preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
                        $validated[$key] = $value;
                    }
                    break;

                case 'category_id':
                case 'program_id':
                case 'severity':
                    // Split by comma and validate
                    $parts = array_filter(array_map('trim', explode(',', $value)));
                    $allowed = ['P1', 'P2', 'P3', 'P4', 'P5'];

                    foreach ($parts as $part) {
                        if (!in_array(strtoupper($part), $allowed, true)) {
                            $this->badRequest("Invalid severity value: $part. Allowed: " . implode(',', $allowed));
                        }
                    }

                    // Store back normalized (uppercase) joined string
                    $validated[$key] = implode(',', array_map('strtoupper', $parts));
                    break;
                case 'limit':
                    if (ctype_digit($value)) {
                        $validated[$key] = (int)$value;
                    }
                    break;

                case 'sort_by':
                    if (preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
                        $validated[$key] = $value;
                    }
                    break;

                case 'order':
                    $upper = strtoupper($value);
                    if (in_array($upper, ['ASC', 'DESC'], true)) {
                        $validated[$key] = $upper;
                    }
                    break;

                case 'date_from':
                case 'date_to':
                    $d = DateTime::createFromFormat('Y-m-d H:i:s', $value)
                        ?: DateTime::createFromFormat('Y-m-d', $value);
                    if ($d !== false) {
                        $validated[$key] = $d->format('Y-m-d H:i:s');
                    }
                    break;
            }
        }
        return $validated;
    }

    private function badRequest(string $message): void
    {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }
}
