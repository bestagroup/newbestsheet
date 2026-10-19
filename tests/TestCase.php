<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        if ($connection !== 'sqlite' && ! str_ends_with((string) $database, '_test')) {
            throw new \RuntimeException('Destructive tests require a dedicated database ending in _test.');
        }
    }
}
