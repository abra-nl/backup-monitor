<?php

namespace AbraNl\BackupMonitor\Tests;

use AbraNl\BackupMonitor\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}
