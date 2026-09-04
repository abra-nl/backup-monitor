<?php

namespace AbraNl\BackupMonitor\Http\Controllers;

use AbraNl\BackupMonitor\Support\BackupStatusResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;

class BackupMonitorController extends CpController
{
    public function index(BackupStatusResolver $resolver): Response
    {
        abort_unless(optional(User::current())->isSuper(), 403);

        return Inertia::render('backup-monitor::Index', [
            'disks' => $resolver->resolve(),
            'triggerUrl' => cp_route('backup-monitor.trigger'),
        ]);
    }

    public function trigger(Request $request): RedirectResponse
    {
        abort_unless(optional(User::current())->isSuper(), 403);

        $disks = config('backup.backup.destination.disks', []);

        $validated = $request->validate([
            'disk' => ['required', 'string', Rule::in($disks)],
        ]);

        Artisan::queue('backup:run', ['--only-to-disk' => $validated['disk']]);

        return back();
    }
}
