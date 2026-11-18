# Everyday News

A daily news digest: short, dated summaries of what happened, grouped by category and rendered as
a static PHP page with one JSON file per day.

**Live:** https://songtianxiang.com/everydaynews/

## How it works

- `data/YYYY-MM-DD.json` holds one day's feed (date, sections, items with headline + link + one-line
  summary).
- `api.php` lists the available dates and returns the requested day as JSON — it is what the page
  uses to switch between dates without a reload.
- `index.php` renders the newest day server-side (so the page works without JS) and falls back to the
  API for navigation.

The feeds are produced by a separate collector script that is not part of this repo; dropping a new
`data/YYYY-MM-DD.json` in place is enough to publish a new day.

## Running locally

Requires PHP 8.0+.

```bash
php -S 127.0.0.1:8000
```

Open <http://127.0.0.1:8000/>. The API is at `/api.php` and `/api.php?date=YYYY-MM-DD`.

## Structure

```
api.php       JSON endpoint: available dates + one day's feed
index.php     page shell + server-side render of the latest day
style.css     styles
data/         one JSON file per published day
```

## License

MIT — see [LICENSE](LICENSE).
