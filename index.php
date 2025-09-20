<?php
// ~/www/root_api_bounty_reports/index.php

// Forzar salida en JSON siempre
header('Content-Type: application/json; charset=utf-8');

// Datos de ejemplo de tu API
$response = [
    "api" => "Bounty Reports",
    "version" => "1.0.0",
    "description" => "API informativa para consultar reportes de bug bounty.",
    "endpoints" => [
        "/reports" => "Lista de reportes disponibles",
        "/reports/{id}" => "Detalle de un reporte específico",
        "/stats" => "Estadísticas generales de reportes"
    ],
    "status" => "ok",
    "timestamp" => date(DATE_ATOM)
];

// Imprimir JSON con pretty print si está habilitado
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
