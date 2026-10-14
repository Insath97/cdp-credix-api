<?php

namespace Tests\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The base every feature test runs on.
 *
 * tests/Pest.php binds this to the Feature suite, but the class was missing
 * from the repository, so `php artisan test` failed before running anything.
 * It is rebuilt here at the smallest size that runs: a migrated database,
 * nothing else. The JWT guard helpers and organisation-hierarchy builders that
 * the endpoint tests will want are not rebuilt -- adding them is a separate
 * piece of work, and guessing at their shape would be worse than leaving the
 * gap visible.
 */
abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;
}
