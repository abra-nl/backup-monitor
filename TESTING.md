# Testing Guide

This package uses [Pest](https://pestphp.com/) for testing, built on top of PHPUnit and Orchestra Testbench (via Statamic's `AddonTestCase`).

## Running Tests

```bash
# Run all tests
composer test
# or
./vendor/bin/pest

# Run only unit tests
composer test:unit

# Run only feature tests
composer test:feature

# Run specific test file
./vendor/bin/pest tests/Unit/BackupStatusResolverTest.php

# Run tests matching a pattern
./vendor/bin/pest --filter="BackupMonitorController"

# Run with coverage
composer test:coverage
```

## Test Structure

```
tests/
├── Feature/
│   └── BackupMonitorControllerTest.php   # CP routes: auth, rendering, trigger validation/dispatch
├── Unit/
│   └── BackupStatusResolverTest.php      # BackupStatusResolver in isolation
├── Pest.php                              # binds AddonTestCase to Feature/Unit
└── TestCase.php                          # Statamic\Testing\AddonTestCase
```

## A quirk worth knowing

Statamic's file-based user and role repositories write real fixture files to disk under `tests/__fixtures__/` — they aren't wrapped by the stache-content cleanup Statamic's `AddonTestCase` otherwise provides. Every test that creates a user cleans up `tests/__fixtures__/users` in an `afterEach` — if you add tests that create users or roles, make sure they get cleaned up too, or you'll eventually trip Statamic's "Pro required for multiple users" guard in unrelated tests.

## Writing Tests

### Example Unit Test

```php
it('marks a disk not present in monitor_backups as unmonitored', function () {
    config(['backup.monitor_backups' => []]);
    Storage::fake('local');

    $disk = app(BackupStatusResolver::class)->resolve()[0];

    expect($disk['monitored'])->toBeFalse();
});
```

### Example Feature Test

```php
it('forbids the index page for a non-super user', function () {
    $user = User::make()->email('user@example.com')->assignRole($role);
    $user->save();

    test()->actingAs($user)
        ->get(cp_route('backup-monitor.index'))
        ->assertForbidden();
});
```
