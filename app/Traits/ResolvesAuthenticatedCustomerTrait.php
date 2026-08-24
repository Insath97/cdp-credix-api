<?php

namespace App\Traits;

use App\Models\Customer;

trait ResolvesAuthenticatedCustomerTrait
{
    /**
     * Resolve the Customer row for the authenticated customer user, or abort
     * with 404 rather than leaking whether the account has a linked customer.
     */
    protected function myCustomer(): Customer
    {
        $customer = auth('api')->user()?->customer;

        abort_if(!$customer, 404, 'Customer profile not found.');

        return $customer;
    }

    protected function myCustomerId(): int
    {
        return $this->myCustomer()->id;
    }
}
