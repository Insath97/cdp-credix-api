<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RecoveryReportExport implements FromCollection, WithHeadings, WithMapping
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
        return ['Case No', 'Customer', 'Application No', 'Branch', 'Status', 'Stage', 'Overdue Amount', 'Assigned Agent', 'External Agent', 'Opened At', 'Closed At'];
    }

    public function map($row): array
    {
        return [
            $row['case_no'],
            $row['customer_name'],
            $row['application_no'],
            $row['branch_name'],
            $row['status'],
            $row['stage'],
            $row['overdue_amount'],
            $row['assigned_agent_name'],
            $row['external_agent_name'],
            optional($row['opened_at'])->format('Y-m-d'),
            optional($row['closed_at'])->format('Y-m-d'),
        ];
    }
}
