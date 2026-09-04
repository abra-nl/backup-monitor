<?php

use Illuminate\Foundation\Console\QueuedCommand;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Statamic\Facades\Role;
use Statamic\Facades\User;

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

afterEach(function (): void {
    // The file-based user repository writes real fixture files to disk
    // (it isn't wrapped by PreventsSavingStacheItemsToDisk), so each test
    // must clean up the users/roles it creates to avoid tripping Statamic's
    // "Pro required for multiple users" guard in later tests.
    File::cleanDirectory(__DIR__.'/../__fixtures__/users');
});

function makeSuperUser(string $email = 'super@example.com')
{
    $user = User::make()->email($email)->makeSuper();
    $user->save();

    return $user;
}

it('renders the index page for a super user with backup data', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('testapp/backup-2024-01-01.zip', 'fake-backup-contents');

    $response = test()->actingAs(makeSuperUser())->get(cp_route('backup-monitor.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('backup-monitor::Index')
        ->has('disks', 1)
        ->where('disks.0.disk', 'local')
        ->where('disks.0.monitored', true)
        ->where('disks.0.backups.0.path', 'testapp/backup-2024-01-01.zip'),
    );
});

it('forbids the index page for a non-super user', function (): void {
    $role = Role::make()->handle('editor')->title('Editor')->addPermission('access cp');
    $role->save();

    $user = User::make()->email('user@example.com')->assignRole($role);
    $user->save();

    test()->actingAs($user)
        ->get(cp_route('backup-monitor.index'))
        ->assertForbidden();
});

it('dispatches a queued backup command for a configured disk', function (): void {
    Bus::fake();

    test()->actingAs(makeSuperUser())
        ->post(cp_route('backup-monitor.trigger'), ['disk' => 'local'])
        ->assertRedirect();

    Bus::assertDispatched(QueuedCommand::class, function (QueuedCommand $job): bool {
        $data = (fn () => $this->data)->bindTo($job, QueuedCommand::class)();

        return $data === ['backup:run', ['--only-to-disk' => 'local']];
    });
});

it('rejects an unconfigured disk', function (): void {
    Bus::fake();

    test()->actingAs(makeSuperUser())
        ->post(cp_route('backup-monitor.trigger'), ['disk' => 'not-a-real-disk'])
        ->assertSessionHasErrors('disk');

    Bus::assertNotDispatched(QueuedCommand::class);
});
