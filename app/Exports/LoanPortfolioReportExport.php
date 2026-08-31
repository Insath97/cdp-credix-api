<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LoanPortfolioReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Application No', 'Customer', 'Branch', 'Loan Product', 'Requested Amount', 'Approved Amount', 'Status', 'Applied At', 'Disbursed At', 'Outstanding Balance'];
    }

    public function map($row): array
    {
        return [
            $row['application_no'],
            $row['customer_name'],
            $row['branch_name'],
            $row['loan_product'],
            $row['requested_amount'],
            $row['approved_amount'],
            $row['status'],
            optional($row['applied_at'])->format('Y-m-d'),
            optional($row['disbursed_at'])->format('Y-m-d'),
            $row['outstanding_balance'],
        ];
    }
}
