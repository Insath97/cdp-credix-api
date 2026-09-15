<?php

use App\Models\ExternalRecoveryAgent;
use App\Models\Notification;
use App\Models\RecoveryCase;
use App\Services\RecoveryCaseService;

/*
|--------------------------------------------------------------------------
| Recovery: agents, escalation and the SMS
|--------------------------------------------------------------------------
|
| An internal case is worked by one of our own officers, reached through their
| employee record. Once the arrears run past the external threshold the case is
| handed to an outside agency, which is reached on its own number.
|
| Both handovers are only real if the agent is actually told, so these tests
| check the notification row as well as the assignment.
|
*/

/**
 * Put an existing officer on the recovery agent register.
 *
 * assign-agent will not take just any user: the id has to appear in
 * recovery_agents and be active, which is what stops a case being handed to
 * someone who does not do recovery work.
 */
function registerAsAgent(\App\Models\User $officer, ?string $phone = null): \App\Models\User
{
    if ($phone !== null) {
        $officer->employee->update(['phone_primary' => $phone]);
    }

    \App\Models\RecoveryAgent::create([
        'user_id'   => $officer->id,
        'branch_id' => $officer->employee?->branch_id,
        'is_active' => true,
    ]);

    return $officer;
}

/** An external agency with a number we can send to. */
function externalAgency(array $overrides = []): ExternalRecoveryAgent
{
    return ExternalRecoveryAgent::create(array_merge([
        'name'      => 'Swift Recoveries',
        'phone'     => '0712223344',
        'is_active' => true,
    ], $overrides));
}

/*
|--------------------------------------------------------------------------
| Assigning an internal officer
|--------------------------------------------------------------------------
*/

it('assigns an internal officer to a case and moves it to in progress', function () {
    $loan = overdueLoan(dueDaysAgo: 40);
    $this->artisan('recovery:escalate-internal')->assertSuccessful();

    $case = RecoveryCase::where('loan_application_id', $loan->id)->firstOrFail();
    expect($case->status)->toBe('open');

    $chain = $this->organisationChain();
    $officer = registerAsAgent($this->officerAtBranch($chain['branch'], []));

    $this->actingAsApi($this->userWithPermissions(['Recovery Case Update']));

    $this->patchJson($this->api('/recovery-cases/') . $case->id . '/assign-agent', [
        'assigned_agent_id' => $officer->id,
    ])->assertStatus(200);

    $case->refresh();

    expect($case->assigned_agent_id)->toBe($officer->id);

    // Picking a case up is the only thing in the application that moves it out
    // of the untouched 'open' state.
    expect($case->status)->toBe('in_progress');
});

it('texts the internal officer the case was handed to', function () {
    $loan = overdueLoan(dueDaysAgo: 40);
    $this->artisan('recovery:escalate-internal')->assertSuccessful();

    $case = RecoveryCase::where('loan_application_id', $loan->id)->firstOrFail();

    $chain = $this->organisationChain();
    $officer = registerAsAgent($this->officerAtBranch($chain['branch'], []), '0759998888');

    $this->actingAsApi($this->userWithPermissions(['Recovery Case Update']));

    $this->patchJson($this->api('/recovery-cases/') . $case->id . '/assign-agent', [
        'assigned_agent_id' => $officer->id,
    ])->assertStatus(200);

    // The officer is reached through their employee record, not the user row.
    $sent = Notification::where('recipient', '0759998888')->get();

    expect($sent)->not->toBeEmpty();
    expect($sent->first()->message)->toContain($case->case_no);
});

it('refuses to assign an agent to a case that is already settled', function () {
    $loan = overdueLoan(dueDaysAgo: 40);
    $this->artisan('recovery:escalate-internal')->assertSuccessful();

    $case = RecoveryCase::where('loan_application_id', $loan->id)->firstOrFail();
    $case->update(['status' => 'resolved']);

    $this->actingAsApi($this->userWithPermissions(['Recovery Case Update']));

    $chain = $this->organisationChain();
    $officer = registerAsAgent($this->officerAtBranch($chain['branch'], []));

    $this->patchJson($this->api('/recovery-cases/') . $case->id . '/assign-agent', [
        'assigned_agent_id' => $officer->id,
    ])->assertStatus(422);
});

/*
|--------------------------------------------------------------------------
| Escalating to an outside agency
|--------------------------------------------------------------------------
*/

it('hands a case to external recovery once the arrears pass the threshold', function () {
    // The threshold is counted from the installment's DUE DATE, so 15 means
    // fifteen days after the payment was missed.
    recoverySetting('internal_recovery_threshold_days', 7);
    recoverySetting('external_recovery_threshold_days', 15);

    $loan = overdueLoan(dueDaysAgo: 15, graceDays: 7);

    $this->artisan('recovery:escalate-internal')->assertSuccessful();
    $this->artisan('recovery:escalate-external')->assertSuccessful();

    $cases = RecoveryCase::where('loan_application_id', $loan->id)->get();

    // The internal case is settled and a fresh external one opened alongside
    // it, so the history of who was chasing when survives.
    expect($cases->firstWhere('stage', 'external'))->not->toBeNull();
    expect($cases->firstWhere('stage', 'external')->status)->toBe('open');
});

it('does not hand a case over before the threshold', function () {
    recoverySetting('internal_recovery_threshold_days', 7);
    recoverySetting('external_recovery_threshold_days', 15);

    $loan = overdueLoan(dueDaysAgo: 14, graceDays: 7);

    $this->artisan('recovery:escalate-internal')->assertSuccessful();
    $this->artisan('recovery:escalate-external')->assertSuccessful();

    $cases = RecoveryCase::where('loan_application_id', $loan->id)->get();

    expect($cases->firstWhere('stage', 'external'))->toBeNull();
    expect($cases->firstWhere('stage', 'internal'))->not->toBeNull();
});

it('does not hand over a case that never reached internal recovery', function () {
    // The external job only ever promotes an existing live internal case.
    recoverySetting('external_recovery_threshold_days', 15);

    $loan = overdueLoan(dueDaysAgo: 30, graceDays: 7);

    $this->artisan('recovery:escalate-external')->assertSuccessful();

    expect(RecoveryCase::where('loan_application_id', $loan->id)->count())->toBe(0);
});

it('does not hand the same case over twice', function () {
    recoverySetting('internal_recovery_threshold_days', 7);
    recoverySetting('external_recovery_threshold_days', 15);

    $loan = overdueLoan(dueDaysAgo: 20, graceDays: 7);

    $this->artisan('recovery:escalate-internal')->assertSuccessful();
    $this->artisan('recovery:escalate-external')->assertSuccessful();
    $this->artisan('recovery:escalate-external')->assertSuccessful();
    $this->artisan('recovery:escalate-external')->assertSuccessful();

    expect(RecoveryCase::where('loan_application_id', $loan->id)->where('stage', 'external')->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Assigning the outside agency, and telling them
|--------------------------------------------------------------------------
*/

it('texts the external agency the case was handed to', function () {
    recoverySetting('internal_recovery_threshold_days', 7);
    recoverySetting('external_recovery_threshold_days', 15);

    $loan = overdueLoan(dueDaysAgo: 20, graceDays: 7);

    $this->artisan('recovery:escalate-internal')->assertSuccessful();
    $this->artisan('recovery:escalate-external')->assertSuccessful();

    $case = RecoveryCase::where('loan_application_id', $loan->id)->where('stage', 'external')->firstOrFail();
    $agency = externalAgency(['phone' => '0776665544']);

    $this->actingAsApi($this->userWithPermissions(['Recovery Case Update']));

    $this->patchJson($this->api('/recovery-cases/') . $case->id . '/assign-agent', [
        'external_agent_id' => $agency->id,
    ])->assertStatus(200);

    expect($case->fresh()->external_agent_id)->toBe($agency->id);

    // An external case is reached on the agency's own number, not through an
    // employee record.
    $sent = \App\Models\Notification::where('recipient', '0776665544')->get();

    expect($sent)->not->toBeEmpty();
    expect($sent->first()->message)->toContain($case->case_no);
});

it('carries the overdue amount and the customer into the agency message', function () {
    // The agency is being sent to collect, so the text has to say how much and
    // from whom, or it is an instruction with no content.
    recoverySetting('internal_recovery_threshold_days', 7);
    recoverySetting('external_recovery_threshold_days', 15);

    $loan = overdueLoan(dueDaysAgo: 20, graceDays: 7);

    $this->artisan('recovery:escalate-internal')->assertSuccessful();
    $this->artisan('recovery:escalate-external')->assertSuccessful();

    $case = RecoveryCase::where('loan_application_id', $loan->id)->where('stage', 'external')->firstOrFail();
    $agency = externalAgency(['phone' => '0770001111']);

    $this->actingAsApi($this->userWithPermissions(['Recovery Case Update']));

    $this->patchJson($this->api('/recovery-cases/') . $case->id . '/assign-agent', [
        'external_agent_id' => $agency->id,
    ])->assertStatus(200);

    $message = \App\Models\Notification::where('recipient', '0770001111')->value('message');

    expect($message)->toContain('10,000.00');
    expect($message)->toContain($loan->customer->full_name);
});

it('does not fail the assignment when the agency has no phone number', function () {
    // A missing number is a data gap, not a reason to refuse the handover. The
    // assignment is already saved by the time the SMS is attempted.
    recoverySetting('internal_recovery_threshold_days', 7);
    recoverySetting('external_recovery_threshold_days', 15);

    $loan = overdueLoan(dueDaysAgo: 20, graceDays: 7);

    $this->artisan('recovery:escalate-internal')->assertSuccessful();
    $this->artisan('recovery:escalate-external')->assertSuccessful();

    $case = RecoveryCase::where('loan_application_id', $loan->id)->where('stage', 'external')->firstOrFail();
    $agency = externalAgency(['phone' => null]);

    $this->actingAsApi($this->userWithPermissions(['Recovery Case Update']));

    $this->patchJson($this->api('/recovery-cases/') . $case->id . '/assign-agent', [
        'external_agent_id' => $agency->id,
    ])->assertStatus(200);

    expect($case->fresh()->external_agent_id)->toBe($agency->id);
});
