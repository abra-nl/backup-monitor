<?php

namespace AbraNl\BackupMonitor\Support;

use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\BackupDestination\BackupDestinationFactory;
use Spatie\Backup\Config\Config;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatusFactory;

class BackupStatusResolver
{
    /**
     * @return array<int, array{
     *     disk: string,
     *     isReachable: bool,
     *     connectionError: ?string,
     *     monitored: bool,
     *     unmonitoredReason: ?string,
     *     isHealthy: ?bool,
     *     failureMessages: array<int, array{check: string, message: string}>,
     *     backups: array<int, array{path: string, date: string, sizeInBytes: float, sizeHuman: string}>,
     * }>
     */
    public function resolve(): array
    {
        $config = Config::fromArray(config('backup'));

        $statusesByDisk = BackupDestinationStatusFactory::createForMonitorConfig($config->monitoredBackups)
            ->keyBy(fn ($status): string => $status->backupDestination()->diskName());

        $monitoredDisks = collect($config->monitoredBackups->monitorBackups)
            ->flatMap(fn ($monitor): array => data_get($monitor, 'disks', []))
            ->unique()
            ->values()
            ->all();

        // @phpstan-ignore-next-line PHPStan can't infer the mapped array shape through BackupCollection's generics without Larastan.
        return BackupDestinationFactory::createFromArray($config)
            ->map(function (BackupDestination $destination) use ($statusesByDisk, $monitoredDisks): array {
                $status = $statusesByDisk->get($destination->diskName());
                $isHealthy = $status?->isHealthy();

                return [
                    'disk' => $destination->diskName(),
                    'isReachable' => $destination->isReachable(),
                    'connectionError' => $destination->connectionError()?->getMessage(),
                    'monitored' => $status !== null,
                    'unmonitoredReason' => $status === null
                        ? $this->unmonitoredReason($destination->diskName(), $monitoredDisks)
                        : null,
                    'isHealthy' => $isHealthy,
                    'failureMessages' => $status ? $status->failureMessages()->all() : [],
                    'backups' => $destination->backups()
                        ->map(fn ($backup): array => [
                            'path' => $backup->path(),
                            'date' => $backup->date()->toIso8601String(),
                            'sizeInBytes' => $backup->sizeInBytes(),
                            'sizeHuman' => $this->humanFilesize($backup->sizeInBytes()),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $monitoredDisks
     */
    private function unmonitoredReason(string $disk, array $monitoredDisks): string
    {
        if ($monitoredDisks === []) {
            return "No disks are configured under 'monitor_backups', so nothing is being monitored.";
        }

        foreach ($monitoredDisks as $monitoredDisk) {
            if (strcasecmp($monitoredDisk, $disk) === 0) {
                return "'monitor_backups' lists '{$monitoredDisk}', but this disk is named '{$disk}'. Disk names are case-sensitive.";
            }
        }

        return "This disk is not listed in any 'monitor_backups' entry (monitored: ".implode(', ', $monitoredDisks).').';
    }

    private function humanFilesize(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
