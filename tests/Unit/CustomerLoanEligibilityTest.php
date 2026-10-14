<?php

use App\Enums\LoanApplicationStatus;
use App\Services\CustomerLoanEligibilityService;

it('treats old and new NIC formats as the same person', function () {
    expect(CustomerLoanEligibilityService::nicVariants(' 85340-0937v '))
        ->toContain('853400937V', '853400937X', '198534000937');

    expect(CustomerLoanEligibilityService::nicVariants('198534000937'))
        ->toContain('198534000937', '853400937V');

    // A post-1999 NIC has no old-format twin.
    expect(CustomerLoanEligibilityService::nicVariants('200012345678'))->toBe(['200012345678']);

    expect(CustomerLoanEligibilityService::nicVariants(null))->toBe([]);
});

it('counts every status except rejected, cancelled, closed and declined as live', function () {
    $released = [
        LoanApplicationStatus::Rejected,
        LoanApplicationStatus::Cancelled,
        LoanApplicationStatus::Closed,
        LoanApplicationStatus::Declined,
    ];

    foreach (LoanApplicationStatus::cases() as $status) {
        expect($status->holdsBorrowers())->toBe(!in_array($status, $released, true));
    }
});
