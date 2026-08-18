<?php

namespace App\Enums;

enum LoanRevisionType: string
{
    case ReduceInstallment = 'reduce_installment';
    case ExtendTerm = 'extend_term';
    case PrincipalOnly = 'principal_only';
}
