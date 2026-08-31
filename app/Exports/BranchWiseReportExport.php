<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BranchWiseReportExport implements FromCollection, WithHeadings, WithMapping
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
        return ['Branch Code', 'Branch Name', 'Applications', 'Total Disbursed', 'Outstanding Portfolio', 'Total Collected'];
    }

    public function map($row): array
    {
        return [
            $row['branch_code'],
            $row['branch_name'],
            $row['applications_count'],
            $row['total_disbursed'],
            $row['outstanding_portfolio'],
            $row['total_collected'],
        ];
    }
}
