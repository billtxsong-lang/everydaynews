<?php
declare(strict_types=1);

header('Content-Type: application/atom+xml; charset=utf-8');
header('Cache-Control: public, max-age=600');

date_default_timezone_set('America/Los_Angeles');

const BASE = 'https://songtianxiang.com/everydaynews/';
const FEED_LIMIT = 10;

/** Escape for XML text and attribute values, dropping characters XML forbids outright. */
function x(string $value): string
{
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '';
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function edition_title(array $feed, string $day): string
{
    $headline = trim((string) ($feed['headline'] ?? ''));
    if ($headline !== '') {
        return $headline;
    }

    $ts = strtotime($day . ' 12:00:00');
    return 'Vancouver events for ' . ($ts ? date('l, F j', $ts) : $day);
}

function atom_time(array $feed, string $day): string
{
    $iso = $feed['generated_at'] ?? null;
    $ts = is_string($iso) ? strtotime($iso) : false;
    if ($ts === false) {
        $ts = strtotime($day . ' 12:00:00');
    }

    return date('c', $ts === false ? time() : $ts);
}

/** Plain-text body: the day's summaries plus a bulleted list of what is on. */
function entry_body(array $feed): string
{
    $parts = [];
    foreach (['summary', 'summary_zh'] as $key) {
        $text = trim((string) ($feed[$key] ?? ''));
        if ($text !== '') {
            $parts[] = $text;
        }
    }

    $lines = [];
    foreach (is_array($feed['events'] ?? null) ? $feed['events'] : [] as $event) {
        if (!is_array($event)) {
            continue;
        }
        $title = trim((string) ($event['title'] ?? ''));
        if ($title === '') {
            continue;
        }
        $bits = array_filter([
            $title,
            trim((string) ($event['when'] ?? '')),
            trim((string) ($event['venue'] ?? '')),
        ], static fn(string $bit): bool => $bit !== '');
        $lines[] = '• ' . implode(' — ', $bits);
    }
    if ($lines !== []) {
        $parts[] = implode("\n", $lines);
    }

    return implode("\n\n", $parts);
}

$dataDir = __DIR__ . '/data';

$days = [];
foreach (glob($dataDir . '/*.json') ?: [] as $file) {
    $name = basename($file, '.json');
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $name)) {
        $days[] = $name;
    }
}
rsort($days);
$days = array_slice($days, 0, FEED_LIMIT);

$entries = [];
foreach ($days as $day) {
    $raw = @file_get_contents($dataDir . '/' . $day . '.json');
    if ($raw === false) {
        continue;
    }
    $feed = json_decode($raw, true);
    if (!is_array($feed)) {
        continue;
    }

    $entries[] = [
        'day'     => $day,
        'title'   => edition_title($feed, $day),
        'updated' => atom_time($feed, $day),
        'body'    => entry_body($feed),
    ];
}

$feedUpdated = $entries[0]['updated'] ?? date('c');

echo '<?xml version="1.0" encoding="utf-8"?>', "\n";
?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title type="text">Vancouver Daily</title>
  <subtitle type="text">What's on in Vancouver, collected from the city's listings and summarised every morning.</subtitle>
  <id><?= x(BASE) ?></id>
  <updated><?= x($feedUpdated) ?></updated>
  <link rel="self" type="application/atom+xml" href="<?= x(BASE . 'feed.php') ?>"/>
  <link rel="alternate" type="text/html" href="<?= x(BASE) ?>"/>
  <author>
    <name>Song Tianxiang</name>
    <uri><?= x('https://songtianxiang.com/') ?></uri>
  </author>
<?php foreach ($entries as $entry): ?>
  <entry>
    <id><?= x(BASE . '#' . $entry['day']) ?></id>
    <title type="text"><?= x($entry['title']) ?></title>
    <link rel="alternate" type="text/html" href="<?= x(BASE . '?date=' . $entry['day']) ?>"/>
    <published><?= x($entry['updated']) ?></published>
    <updated><?= x($entry['updated']) ?></updated>
    <author><name>Song Tianxiang</name></author>
    <content type="text"><?= x($entry['body']) ?></content>
  </entry>
<?php endforeach; ?>
</feed>
