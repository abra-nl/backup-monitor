# Changelog

All notable changes to `abra-nl/backup-monitor` are documented in this file.

## v1.0.0

### Added

- Initial release, extracted from the `abra-website` monorepo.
- CP page under Settings listing every configured backup disk, its health status (from `backup.monitor_backups`), and its backup files (date, size), newest first.
- A "Run backup" action per disk, restricted to super users, validated against `backup.backup.destination.disks`, and dispatched via `Artisan::queue('backup:run', ['--only-to-disk' => $disk])`.
- Auto-polling (15s) on the CP page so newly triggered or scheduled backups appear without a manual refresh.
