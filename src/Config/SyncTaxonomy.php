<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit(1);
}

require_once __DIR__ . '/../Models/Category.php';
require_once __DIR__ . '/../Models/Subcategory.php';

$url = "https://raw.githubusercontent.com/bugcrowd/vulnerability-rating-taxonomy/refs/heads/master/mappings/cwe/cwe.json";

$json = file_get_contents($url);
if ($json === false) {
    fwrite(STDERR, "❌ Failed to fetch taxonomy JSON\n");
    exit(1);
}

$data = json_decode($json, true);
if (!isset($data['content'])) {
    fwrite(STDERR, "❌ Invalid taxonomy JSON structure\n");
    exit(1);
}

function processNode($node, $parentCategoryId = null) {
    if ($parentCategoryId === null) {
        // Root category
        $categoryId = Category::create($node['id']);
        echo "✅ Category synced: {$node['id']} (ID=$categoryId)\n";

        if (empty($node['children'])) {
            // Create a subcategory with the same name as the category
            $subId = Subcategory::create($categoryId, $node['id']);
            echo "  ↳ Default Subcategory created: {$node['id']} (ID=$subId)\n";
        } else {
            foreach ($node['children'] as $child) {
                processNode($child, $categoryId);
            }
        }
    } else {
        // Subcategory case
        $subId = Subcategory::create($parentCategoryId, $node['id']);
        echo "  ↳ Subcategory synced: {$node['id']} (under category $parentCategoryId)\n";

        if (!empty($node['children'])) {
            foreach ($node['children'] as $child) {
                processNode($child, $parentCategoryId); // keep same category_id
            }
        }
    }
}

foreach ($data['content'] as $rootNode) {
    processNode($rootNode);
}

echo "🎉 Taxonomy sync complete\n";
