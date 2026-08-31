@extends('reports.layout')

@section('content')
@isset($summary)
<table class="summary">
    <tr>
        <td class="label">Total Overdue Balance:</td>
        <td class="value">{{ number_format($summary['total_overdue_balance'], 2) }}</td>
        <td class="label">Total Collected in Period:</td>
        <td class="value">{{ number_format($summary['total_collected_in_period'], 2) }}</td>
    </tr>
</table>
@endisset

<table>
    <thead>
        <tr>
            <th>Case No</th>
            <th>Customer</th>
            <th>Application No</th>
            <th>Branch</th>
            <th>Status</th>
            <th>Stage</th>
            <th>Overdue Amount</th>
            <th>Assigned Agent</th>
            <th>External Agent</th>
            <th>Opened At</th>
            <th>Closed At</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
        <tr>
            <td>{{ $row['case_no'] }}</td>
            <td>{{ $row['customer_name'] }}</td>
            <td>{{ $row['application_no'] }}</td>
            <td>{{ $row['branch_name'] }}</td>
            <td>{{ ucfirst($row['status']) }}</td>
            <td>{{ ucfirst($row['stage']) }}</td>
            <td>{{ $row['overdue_amount'] !== null ? number_format($row['overdue_amount'], 2) : '-' }}</td>
            <td>{{ $row['assigned_agent_name'] ?? '-' }}</td>
            <td>{{ $row['external_agent_name'] ?? '-' }}</td>
            <td>{{ $row['opened_at']?->format('Y-m-d') ?? '-' }}</td>
            <td>{{ $row['closed_at']?->format('Y-m-d') ?? '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="11">No data for the selected filters.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
