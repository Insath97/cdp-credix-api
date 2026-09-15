<?php

namespace Tests\Support;

use App\Models\Branch;
use App\Models\Country;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Province;
use App\Models\Region;
use App\Models\User;
use App\Models\Zonal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Shared harness for the V1 API feature tests.
 *
 * Three things every one of these tests needs, and all three are easy to get
 * subtly wrong:
 *
 * 1. The guard. Routes sit behind `auth:api`, which is the JWT guard, so a
 *    plain actingAs() against the default guard authenticates nobody and every
 *    assertion then measures a 401 instead of the thing under test.
 * 2. `password_changed_at`. EnsurePasswordChanged holds any account that has
 *    never set its own password at the door, so a user built without that
 *    column stamped gets 403 on every endpoint except /me and logout.
 * 3. Spatie's permission cache. It is process-wide and survives the database
 *    being rebuilt between tests, so a permission created in one test can be
 *    invisible in the next unless the cache is dropped first.
 */
class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * The API prefix every route in this suite hangs off.
     */
    protected const API = '/api/v1';

    /**
     * Build a full route path.
     *
     * A method rather than bare use of the constant because these tests are
     * written as Pest closures, where `self::` resolves against the closure's
     * defining scope rather than the bound test case.
     */
    protected function api(string $path): string
    {
        return self::API . '/' . ltrim($path, '/');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Spatie memoises the permission table for the life of the process.
        // RefreshDatabase rolls the rows back but cannot touch that cache, so
        // without this a permission seeded by an earlier test lingers as a
        // stale id and the middleware denies a user who genuinely holds it.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Same hazard, different cache. Setting::get() memoises every key with
        // rememberForever(), and the array store lives for the whole process,
        // so a value one test wrote would still be readable by the next one
        // after its row had been rolled back.
        Cache::flush();
    }

    /**
     * A back-office user holding exactly the permissions named, and nothing
     * else.
     *
     * Passing the exact list rather than a role is deliberate: it is what lets
     * a test assert that an endpoint is closed to someone missing one specific
     * permission, which is the half of authorisation that regressions hide in.
     */
    protected function userWithPermissions(array $permissions = [], array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'user_type'           => 'admin',
            'is_active'           => true,
            'can_login'           => true,
            // Stamped, or EnsurePasswordChanged turns every request into a 403
            // and the test measures the wrong failure.
            'password_changed_at' => now(),
        ], $attributes));

        foreach ($permissions as $name) {
            $this->permission($name);
        }

        $user->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    /**
     * Find or create one permission on the api guard.
     *
     * Spatie's own Permission::findOrCreate() cannot be used here: this schema
     * adds a NOT NULL `group_name` column to the permissions table, which that
     * helper knows nothing about, so every create through it dies on the
     * constraint. The group only labels the permission on the roles screen, so
     * anything stable will do for a test.
     */
    protected function permission(string $name): Permission
    {
        return Permission::firstOrCreate(
            ['name' => $name, 'guard_name' => 'api'],
            ['group_name' => 'Test Permissions'],
        );
    }

    /**
     * A Super Admin, for the cases where the endpoint under test is not the
     * subject of the assertion and permissions are only in the way.
     */
    protected function superAdmin(array $attributes = []): User
    {
        $user = $this->userWithPermissions([], $attributes);

        $role = Role::findOrCreate('Super Admin', 'api');
        $user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    /**
     * A staff user posted to a branch, holding exactly the permissions named.
     *
     * Branch confinement is not read off the user. ScopesToUserBranch reaches
     * through to the EMPLOYEE record behind them, and only for user_type
     * 'staff' -- an admin, or anyone with no employee row, is deliberately
     * left unconfined so head office can see every branch. So a test about
     * confinement has to build the employee too, or it quietly measures the
     * unconfined case and passes for the wrong reason.
     */
    protected function officerAtBranch(Branch $branch, array $permissions = []): User
    {
        $employee = Employee::create([
            'f_name'             => 'Branch',
            'l_name'             => 'Officer',
            'full_name'          => 'Branch Officer',
            'name_with_initials' => 'B. Officer',
            'employee_code'      => 'EMP-' . fake()->unique()->numerify('#####'),
            'id_number'          => 'EMPNIC' . fake()->unique()->numerify('#########'),
            'branch_id'          => $branch->id,
            'is_active'          => true,
        ]);

        return $this->userWithPermissions($permissions, [
            'user_type'   => 'staff',
            'employee_id' => $employee->id,
        ]);
    }

    /**
     * A customer principal, used to prove the back-office API is closed to the
     * customer portal's own tokens.
     */
    protected function customerUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'user_type'              => 'customer',
            'is_active'              => true,
            'can_login'              => true,
            'password_changed_at'    => now(),
            'two_factor_verified_at' => now(),
        ], $attributes));
    }

    /**
     * Authenticate for the rest of the test against the JWT guard the routes
     * actually use.
     *
     * A real token on a real Authorization header, not actingAs(). The routes
     * sit behind jwt-auth's Authenticate middleware, which parses the token off
     * the request rather than reading whatever user the test set on the guard,
     * so actingAs() leaves every request answering 401. Minting the token the
     * way the login endpoint does also means these tests exercise the same
     * path production does.
     */
    protected function actingAsApi(User $user): static
    {
        return $this->withToken(auth('api')->login($user));
    }

    /**
     * The organisation hierarchy, built top down.
     *
     * Country, province, zone, region and branch are a strict chain of
     * required foreign keys, so almost every test in this suite needs the
     * whole chain before it can create the one row it actually cares about.
     *
     * @return array{country: Country, province: Province, zonal: Zonal, region: Region, branch: Branch}
     */
    protected function organisationChain(): array
    {
        $country = Country::create([
            'name'      => 'Sri Lanka',
            'code'      => 'LK',
            'is_active' => true,
        ]);

        $province = Province::create([
            'name'       => 'Western',
            'code'       => 'WP',
            'country_id' => $country->id,
            'is_active'  => true,
        ]);

        $zonal = Zonal::create([
            'name'        => 'Colombo Zone',
            'code'        => 'CZ',
            'province_id' => $province->id,
            'is_active'   => true,
        ]);

        $region = Region::create([
            'name'      => 'Colombo Central',
            'code'      => 'CC',
            'zonal_id'  => $zonal->id,
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'name'          => 'Head Office',
            'code'          => 'HO-001',
            'address_line1' => '1 Galle Road',
            'city'          => 'Colombo',
            'zone_id'       => $zonal->id,
            'region_id'     => $region->id,
            'province_id'   => $province->id,
            'phone_primary' => '0112345678',
            'opening_date'  => '2020-01-01',
            'branch_type'   => 'main',
            'is_active'     => true,
        ]);

        return compact('country', 'province', 'zonal', 'region', 'branch');
    }

    /**
     * A department and a designation hanging off it.
     *
     * @return array{department: Department, designation: Designation}
     */
    protected function departmentChain(): array
    {
        $department = Department::create([
            'name'      => 'Credit',
            'code'      => 'CRD',
            'is_active' => true,
        ]);

        $designation = Designation::create([
            'name'          => 'Credit Officer',
            'code'          => 'CO',
            'department_id' => $department->id,
            'level'         => 'mid',
            'is_active'     => true,
        ]);

        return compact('department', 'designation');
    }

    /**
     * Assert a 422 that names every field given.
     *
     * This API answers a rejected request in two different shapes, so
     * assertJsonValidationErrors() cannot be used directly. Most form requests
     * override failedValidation() and throw their own response, in which
     * `errors` is a LIST of {field, messages} objects. The handful that do not
     * fall through to the ValidationException renderer in bootstrap/app.php,
     * which emits Laravel's usual map of field => messages. Both are accepted
     * here so a test states what it means rather than which shape it happens
     * to meet.
     */
    protected function assertValidationFailed(TestResponse $response, array $fields): void
    {
        $response->assertStatus(422);

        expect($this->validationFields($response))->toContain(...$fields);
    }

    /**
     * Assert a 422 that does NOT name the given field.
     */
    protected function assertValidationPassedFor(TestResponse $response, string $field): void
    {
        expect($this->validationFields($response))->not->toContain($field);
    }

    /**
     * The field names a 422 response complained about, whichever shape it used.
     *
     * @return array<int, string>
     */
    protected function validationFields(TestResponse $response): array
    {
        $errors = $response->json('errors') ?? [];

        // The overridden shape: a list of {field, messages} objects.
        if (array_is_list($errors)) {
            return array_values(array_filter(array_map(
                fn ($entry) => is_array($entry) ? ($entry['field'] ?? null) : null,
                $errors,
            )));
        }

        // Laravel's own shape: field => messages.
        return array_keys($errors);
    }
}
