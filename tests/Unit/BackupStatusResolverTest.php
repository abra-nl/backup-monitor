<?php

use AbraNl\BackupMonitor\Support\BackupStatusResolver;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config([
        'backup.backup.name' => 'testapp',
        'backup.backup.destination.disks' => ['local'],
        'backup.monitor_backups' => [
            [
                'name' => 'testapp',
                'disks' => ['local'],
                'health_checks' => [],
            ],
        ],
    ]);
});

it('resolves an unreachable disk as unreachable with no backups', function (): void {
    config([
        'filesystems.disks.broken' => ['driver' => 'does-not-exist'],
        'backup.backup.destination.disks' => ['broken'],
        'backup.monitor_backups' => [],
    ]);

    $disk = app(BackupStatusResolver::class)->resolve()[0];

    expect($disk['disk'])->toBe('broken');
    expect($disk['isReachable'])->toBeFalse();

    expect($disk['connectionError'])->not->toBeNull();
    expect($disk['backups'])->toBe([]);
});

it('resolves a reachable, monitored disk with its backups sorted newest first', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('testapp/2024-01-01-00-00-00.zip', str_repeat('a', 1024));
    Storage::disk('local')->put('testapp/2024-06-01-00-00-00.zip', str_repeat('a', 1024));

    $disk = app(BackupStatusResolver::class)->resolve()[0];

    expect($disk['disk'])->toBe('local');
    expect($disk['isReachable'])->toBeTrue();
    expect($disk['monitored'])->toBeTrue();
    expect($disk['isHealthy'])->toBeTrue();
    expect($disk['backups'])->toHaveCount(2);
    expect($disk['backups'][0]['path'])->toBe('testapp/2024-06-01-00-00-00.zip');
    expect($disk['backups'][0]['sizeHuman'])->toBe('1 KB');
});

it('marks a disk not present in monitor_backups as unmonitored', function (): void {
    config(['backup.monitor_backups' => []]);

    Storage::fake('local');

    $disk = app(BackupStatusResolver::class)->resolve()[0];

    expect($disk['monitored'])->toBeFalse();
    expect($disk['isHealthy'])->toBeNull();
    expect($disk['failureMessages'])->toBe([]);
});
