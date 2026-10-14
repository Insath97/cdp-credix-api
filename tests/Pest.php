<?php

use Tests\Support\ApiTestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Every feature test is bound to ApiTestCase, which brings the JWT guard
| helpers, the organisation-hierarchy builders and a freshly migrated
| in-memory database with it. Binding it here rather than per file is what
| Pest requires: a directory may only have one base test case, so a `uses()`
| inside an individual file would collide with this one.
|
*/

uses(ApiTestCase::class)->in('Feature');
