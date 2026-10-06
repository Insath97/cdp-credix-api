<?php

namespace App\Traits;

trait CalculatesMortgageAmount
{
    /**
     * Calculate the maximum loan / capable pledged amount based on security value and percentage.
     *
     * Example:
     *   Security Value = 500,000
     *   Percentage     = 25%
     *   Formula        = Security Value * (Percentage / 100)
     *   Result         = 125,000
     *
     * @param float $securityValue The appraised or collateral value of the security
     * @param float $percentage    The mortgage plan percentage (e.g. 25 for 25%)
     * @return float
     */
    public function calculatePledgedAmount(float $securityValue, float $percentage): float
    {
        return round($securityValue * ($percentage / 100), 2);
    }
}
