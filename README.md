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

## Alerts for missing backups

The page shows a disk as **Unhealthy** when a `monitor_backups` health check fails, but sending alerts is handled by spatie/laravel-backup itself. To be notified when no new backup has been created within a set time:

1. **Add an age health check** to the monitor in `config/backup.php`:

    ```php
    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'laravel-backup'),
            'disks' => ['local', 's3'],
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class => 1,
            ],
        ],
    ],
    ```

    Every disk you want checked must be listed under `disks`. Disks that are only in `backup.destination.disks` show as **Not monitored** (with the reason on the card) and are never checked.

2. **Schedule `backup:monitor`** (for example in `routes/console.php`). The check only runs, and notifications are only sent, when this command runs:

    ```php
    Schedule::command('backup:monitor')->hourly();
    ```

3. **Configure notifications** under `notifications` in `config/backup.php`. `UnhealthyBackupWasFoundNotification` is the one sent for a failed check; set its channels (`mail`, `slack`, ...) and the `notifiable` recipient.

The Backup Monitor page polls every 15 seconds and reflects health on every load, so a stale backup appears there right away. Notifications arrive only when `backup:monitor` runs.

### Thresholds shorter than a day

`MaximumAgeInDays` only accepts whole days. For an hours threshold, add a small custom health check:

```php
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\Tasks\Monitor\HealthCheck;

class MaximumAgeInHours extends HealthCheck
{
    public function __construct(protected int $hours = 24) {}

    public function checkHealth(BackupDestination $backupDestination): void
    {
        $newest = $backupDestination->backups()->newest();

        $this->failIf($newest === null, 'The backup destination is empty.');

        $this->failIf(
            $newest->date()->lt(now()->subHours($this->hours)),
            "The latest backup ({$newest->date()->toDateTimeString()}) is older than {$this->hours} hours."
        );
    }
}
```

Then register it in place of `MaximumAgeInDays`, e.g. `\App\Backup\MaximumAgeInHours::class => 6`.

## Testing

See [TESTING.md](TESTING.md).

## Code Quality

See [CODE_QUALITY.md](CODE_QUALITY.md).
