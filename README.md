# Backup Monitor

> A Statamic Control Panel page for monitoring [spatie/laravel-backup](https://github.com/spatie/laravel-backup) backups, and triggering new ones, without leaving the CP.

## Features

- Shows every disk configured under `backup.destination.disks`, with each disk's backup files (date, size), newest first.
- Surfaces the health status (and failure reasons) for any disk configured under `backup.monitor_backups`.
- Lets a super user trigger `backup:run --only-to-disk={disk}` for a specific disk directly from the CP, dispatched via `Artisan::queue()` so it runs asynchronously once a real queue driver + worker are configured (it still runs inline on the `sync` driver).
- The page polls every 15 seconds so newly triggered or scheduled backups show up automatically.

## Requirements

- PHP ^8.3
- `statamic/cms` ^6.0
- `spatie/laravel-backup` ^10.3

This addon has no configuration of its own — it reads the host application's existing `config/backup.php`.

## How to Install

```bash
composer require abra-nl/backup-monitor
php artisan vendor:publish --tag=backup-monitor
```

The `vendor:publish` step copies the addon's compiled CP assets into `public/vendor/backup-monitor`. Re-run it (`--force`) any time the addon is updated.

## How to Use

Once installed, a super user will see a **Backup Monitor** item under **Settings** in the Control Panel nav. The page is restricted to super users, since it exposes storage/disk details and can trigger backup jobs.

## Testing

See [TESTING.md](TESTING.md).

## Code Quality

See [CODE_QUALITY.md](CODE_QUALITY.md).
