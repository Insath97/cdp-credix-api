@extends('reports.layout')

@section('content')
<table>
    <thead>
        <tr>
            <th>Application No</th>
            <th>Customer</th>
            <th>Branch</th>
            <th>Loan Product</th>
            <th>Requested Amount</th>
            <th>Approved Amount</th>
            <th>Status</th>
            <th>Applied At</th>
            <th>Disbursed At</th>
            <th>Outstanding Balance</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
        <tr>
            <td>{{ $row['application_no'] }}</td>
            <td>{{ $row['customer_name'] }}</td>
            <td>{{ $row['branch_name'] }}</td>
            <td>{{ $row['loan_product'] }}</td>
            <td>{{ number_format($row['requested_amount'], 2) }}</td>
            <td>{{ $row['approved_amount'] !== null ? number_format($row['approved_amount'], 2) : '-' }}</td>
            <td>{{ ucfirst($row['status']) }}</td>
            <td>{{ $row['applied_at']?->format('Y-m-d') ?? '-' }}</td>
            <td>{{ $row['disbursed_at']?->format('Y-m-d') ?? '-' }}</td>
            <td>{{ $row['outstanding_balance'] !== null ? number_format($row['outstanding_balance'], 2) : '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="10">No data for the selected filters.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
