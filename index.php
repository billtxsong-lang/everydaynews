<?php
declare(strict_types=1);

date_default_timezone_set('America/Los_Angeles');

const SITE = 'https://songtianxiang.com';
const BASE = SITE . '/everydaynews/';

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function long_date(string $iso): string
{
    $ts = strtotime($iso . ' 12:00:00');
    return $ts ? date('l, F j', $ts) : $iso;
}

function short_time(?string $iso): string
{
    if (!$iso) {
        return '';
    }
    $ts = strtotime($iso);
    return $ts ? date('M j, g:i a', $ts) : '';
}

$dataDir = __DIR__ . '/data';

$dates = [];
foreach (glob($dataDir . '/*.json') ?: [] as $file) {
    $name = basename($file, '.json');
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $name)) {
        $dates[] = $name;
    }
}
rsort($dates);
$latest = $dates[0] ?? null;

$requested = isset($_GET['date']) ? (string) $_GET['date'] : '';
if ($requested !== '' && !in_array($requested, $dates, true)) {
    $requested = '';
}
$selected = $requested !== '' ? $requested : $latest;
$isLatest = $selected !== null && $selected === $latest;

$feed = null;
if ($selected !== null) {
    $raw = @file_get_contents($dataDir . '/' . $selected . '.json');
    if ($raw !== false) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $feed = $decoded;
        }
    }
}

$events = is_array($feed['events'] ?? null) ? $feed['events'] : [];
$upcoming = is_array($feed['upcoming'] ?? null) ? $feed['upcoming'] : [];
$today = date('Y-m-d');
$isToday = $selected === $today;

$headline = (string) ($feed['headline'] ?? '');
$summary = (string) ($feed['summary'] ?? '');
$summaryZh = (string) ($feed['summary_zh'] ?? '');

$pageTitle = $headline !== ''
    ? $headline . ' — Vancouver Daily'
    : 'Vancouver Daily — what\'s on in Vancouver today';
if ($selected !== null && !$isToday) {
    $pageTitle = 'Vancouver events for ' . long_date($selected) . ' — Vancouver Daily';
}
$metaDesc = $summary !== ''
    ? mb_substr(preg_replace('/\s+/', ' ', $summary), 0, 155) . '…'
    : 'A daily summary of what\'s on in Vancouver — music, film, talks, comedy and markets, collected and summarised every morning.';
$canonical = $isLatest || $isToday ? BASE : BASE . '?date=' . urlencode((string) $selected);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= h($pageTitle) ?></title>
  <meta name="description" content="<?= h($metaDesc) ?>">
  <meta name="robots" content="index, follow, max-image-preview:large">
  <link rel="canonical" href="<?= h($canonical) ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="/favicon.ico" sizes="any">
  <link rel="icon" href="/icon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="/icon.png">
  <link rel="stylesheet" href="style.css">

  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Song Tianxiang">
  <meta property="og:title" content="<?= h($pageTitle) ?>">
  <meta property="og:description" content="<?= h($metaDesc) ?>">
  <meta property="og:url" content="<?= h($canonical) ?>">
  <meta property="og:image" content="<?= SITE ?>/og.png">
  <meta property="og:locale" content="en_CA">
  <meta name="twitter:card" content="summary_large_image">

<?php if ($events !== []): ?>
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "ItemList",
    "name": <?= json_encode($headline !== '' ? $headline : 'Vancouver events for ' . (string) $selected, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    "numberOfItems": <?= count($events) ?>,
    "itemListElement": [
<?php foreach ($events as $i => $ev): ?>
      {
        "@type": "ListItem",
        "position": <?= $i + 1 ?>,
        "item": {
          "@type": "Event",
          "name": <?= json_encode((string) ($ev['title'] ?? ''), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
          "startDate": <?= json_encode((string) $selected, JSON_UNESCAPED_SLASHES) ?>,
          "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode",
          "location": {
            "@type": "Place",
            "name": <?= json_encode((string) ($ev['venue'] ?? 'Vancouver, BC'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
            "address": {
              "@type": "PostalAddress",
              "addressLocality": "Vancouver",
              "addressRegion": "BC",
              "addressCountry": "CA"
            }
          },
          "url": <?= json_encode((string) ($ev['url'] ?? ''), JSON_UNESCAPED_SLASHES) ?>
        }
      }<?= $i + 1 < count($events) ? ',' : '' ?>

<?php endforeach; ?>
    ]
  }
  </script>
<?php endif; ?>
</head>
<body>

<header class="site">
  <div class="wrap">
    <a class="back" href="/">← Song Tianxiang</a>
    <h1>Vancouver Daily</h1>
    <p class="lede">What's on in Vancouver, collected from the city's event listings and summarised every morning. Music, film, talks, comedy, markets — the things actually worth leaving the house for.</p>
  </div>
</header>

<main>
  <form class="toolbar" method="get" action="">
    <label for="date-select">Date</label>
    <select id="date-select" name="date" aria-label="Choose a date" onchange="this.form.submit()">
<?php foreach ($dates as $d): ?>
      <option value="<?= h($d) ?>"<?= $d === $selected ? ' selected' : '' ?>><?= h(long_date($d)) ?></option>
<?php endforeach; ?>
      <noscript><button type="submit">Go</button></noscript>
    </select>
<?php if ($isToday): ?><span class="badge-today">Today</span><?php endif; ?>
    <span class="updated"><?= $feed ? 'Updated ' . h(short_time($feed['generated_at'] ?? '')) : '' ?></span>
  </form>

<?php if ($feed === null): ?>
  <section class="block">
    <div class="empty">No edition for this date yet. New editions are published each morning around 8am Pacific.</div>
  </section>
<?php else: ?>

<?php if ($headline !== '' || $summary !== ''): ?>
  <section class="summary">
    <?php if ($headline !== ''): ?><h2><?= h($headline) ?></h2><?php endif; ?>
    <?php if ($summary !== ''): ?><p><?= h($summary) ?></p><?php endif; ?>
    <?php if ($summaryZh !== ''): ?><p class="zh"><?= h($summaryZh) ?></p><?php endif; ?>
  </section>
<?php endif; ?>

  <section class="block">
    <h2 class="section-title"><?= $isToday ? "Today's picks" : 'Picks for this day' ?><?php if ($events !== []): ?> <span class="count"><?= count($events) ?> listed</span><?php endif; ?></h2>
    <?php if ($events === []): ?>
      <div class="empty">Nothing confirmed for this day — a quiet one, or the listings haven't caught up yet.</div>
    <?php else: ?>
    <div class="grid">
<?php foreach ($events as $ev): ?>
      <?php $isFree = ($ev['free'] ?? false) === true || strtolower((string) ($ev['price'] ?? '')) === 'free'; ?>
      <article class="card">
        <div class="top">
          <span class="chip"><?= h($ev['category'] ?? 'Event') ?></span>
          <?php if ($isFree): ?><span class="chip free">Free</span><?php endif; ?>
        </div>
        <h3><?php if (!empty($ev['url'])): ?><a href="<?= h($ev['url']) ?>" target="_blank" rel="noopener"><?= h($ev['title'] ?? '') ?></a><?php else: ?><?= h($ev['title'] ?? '') ?><?php endif; ?></h3>
        <?php $meta = array_filter([(string) ($ev['when'] ?? ''), (string) ($ev['venue'] ?? '')]); ?>
        <?php if ($meta !== []): ?><div class="meta"><?= h(implode(' · ', $meta)) ?></div><?php endif; ?>
        <?php if (!empty($ev['note'])): ?><p class="note"><?= h($ev['note']) ?></p><?php endif; ?>
      </article>
<?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

<?php if ($upcoming !== []): ?>
  <section class="block">
    <h2 class="section-title">Coming up <span class="count"><?= count($upcoming) ?> ahead</span></h2>
    <ul class="upcoming-list">
<?php foreach ($upcoming as $u): ?>
      <li>
        <span class="when"><?= h($u['when'] ?? '') ?></span>
        <span><?php if (!empty($u['url'])): ?><a href="<?= h($u['url']) ?>" target="_blank" rel="noopener"><?= h($u['title'] ?? '') ?></a><?php else: ?><?= h($u['title'] ?? '') ?><?php endif; ?><?php if (!empty($u['venue'])): ?> <span class="meta">— <?= h($u['venue']) ?></span><?php endif; ?></span>
      </li>
<?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>

<?php endif; ?>

<?php
$others = array_values(array_filter($dates, static fn($d) => $d !== $selected));
if ($others !== []):
    $recent = array_slice($others, 0, 7);
?>
  <section class="block">
    <h2 class="section-title">Previous editions</h2>
    <ul class="upcoming-list">
<?php foreach ($recent as $d): ?>
      <li>
        <span class="when"><?= h(date('D, M j', strtotime($d . ' 12:00:00'))) ?></span>
        <span><a href="?date=<?= h($d) ?>"><?= h(long_date($d)) ?></a></span>
      </li>
<?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>
</main>

<footer class="site">
  <p>Compiled automatically from <a href="https://www.straight.com/events" target="_blank" rel="noopener">Georgia Straight</a> and <a href="https://www.eventbrite.ca/d/canada--vancouver/events/" target="_blank" rel="noopener">Eventbrite</a> listings. Always double-check time, price and availability with the venue before heading out.</p>
  <p><a href="/">songtianxiang.com</a></p>
</footer>

</body>
</html>
