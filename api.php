<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Access-Control-Allow-Origin: *');

date_default_timezone_set('America/Los_Angeles');

$dataDir = __DIR__ . '/data';

$dates = [];
foreach (glob($dataDir . '/*.json') ?: [] as $file) {
    $name = basename($file, '.json');
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $name)) {
        $dates[] = $name;
    }
}
rsort($dates);

$requested = isset($_GET['date']) ? (string) $_GET['date'] : '';
if ($requested !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $requested)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid date']);
    exit;
}

$selected = $requested !== '' ? $requested : ($dates[0] ?? null);
$feed = null;

if ($selected !== null && in_array($selected, $dates, true)) {
    $raw = file_get_contents($dataDir . '/' . $selected . '.json');
    $feed = json_decode((string) $raw, true);
    if (!is_array($feed)) {
        $feed = null;
    }
}

echo json_encode(
    [
        'dates'    => $dates,
        'selected' => $selected,
        'today'    => date('Y-m-d'),
        'feed'     => $feed,
    ],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
);
