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

        // @phpstan-ignore-next-line PHPStan can't infer the mapped array shape through BackupCollection's generics without Larastan.
        return BackupDestinationFactory::createFromArray($config)
            ->map(function (BackupDestination $destination) use ($statusesByDisk): array {
                $status = $statusesByDisk->get($destination->diskName());
                $isHealthy = $status?->isHealthy();

                return [
                    'disk' => $destination->diskName(),
                    'isReachable' => $destination->isReachable(),
                    'connectionError' => $destination->connectionError()?->getMessage(),
                    'monitored' => $status !== null,
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

    private function humanFilesize(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
