<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Prevent real media uploads during feature tests
        $this->app->bind(
            \App\Contracts\MediaStorageInterface::class,
            \App\Infrastructure\MediaStorage\FakeMediaStorage::class
        );
    }
}
