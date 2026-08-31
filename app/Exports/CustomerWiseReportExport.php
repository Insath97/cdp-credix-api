<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomerWiseReportExport implements FromCollection, WithHeadings, WithMapping
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
        return ['Customer Code', 'Customer Name', 'Total Loans', 'Total Disbursed', 'Outstanding Balance', 'Total Paid', 'Overdue Amount'];
    }

    public function map($row): array
    {
        return [
            $row['customer_code'],
            $row['full_name'],
            $row['total_loans'],
            $row['total_disbursed'],
            $row['outstanding_balance'],
            $row['total_paid'],
            $row['overdue_amount'],
        ];
    }
}
