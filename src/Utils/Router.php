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

                $params = $this->validateRouteParams($params);
                $queryParams = $this->validateQueryParams($_GET ?? []);

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
            if ($key === 'id') {
                if (!preg_match(
                    '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                    $value
                )) {
                    $this->badRequest("Invalid UUID format in route param '$key'");
                }
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
                case 'program_id':
                case 'subcategory_id':
                    if (strlen($value) <= 500 && preg_match('/^\d+(,\d+)*$/', $value)) {
                        $validated[$key] = $value;
                    } else {
                        $this->badRequest("Invalid format for $key. Must be comma-separated numbers, max 500 chars.");
                    }
                    break;

                case 'severity':
                    $parts = array_filter(array_map('trim', explode(',', $value)));
                    $allowed = ['P1', 'P2', 'P3', 'P4', 'P5'];
                    foreach ($parts as $part) {
                        if (!in_array(strtoupper($part), $allowed, true)) {
                            $this->badRequest("Invalid severity value: $part. Allowed: " . implode(',', $allowed));
                        }
                    }
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
