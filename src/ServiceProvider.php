<?php

namespace AbraNl\BackupMonitor;

use Statamic\Facades\CP\Nav;
use Statamic\Facades\User;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    // @phpstan-ignore-next-line
    protected $vite = [
        'input' => [
            'resources/js/addon.js',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    public function bootAddon(): void
    {
        Nav::extend(function ($nav): void {
            if (! optional(User::current())->isSuper()) {
                return;
            }

            $nav->create(__('Backup Monitor'))
                ->section('Settings')
                ->route('backup-monitor.index')
                ->icon('upload-cloud');
        });
    }
}
