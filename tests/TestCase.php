<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests exercise the backend, not built assets — skip the Vite manifest so
        // page-rendering tests don't require `npm run build` (e.g. in CI).
        $this->withoutVite();
    }
}
