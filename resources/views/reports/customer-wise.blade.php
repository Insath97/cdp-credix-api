@extends('reports.layout')

@section('content')
<table>
    <thead>
        <tr>
            <th>Customer Code</th>
            <th>Customer Name</th>
            <th>Total Loans</th>
            <th>Total Disbursed</th>
            <th>Outstanding Balance</th>
            <th>Total Paid</th>
            <th>Overdue Amount</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
        <tr>
            <td>{{ $row['customer_code'] }}</td>
            <td>{{ $row['full_name'] }}</td>
            <td>{{ $row['total_loans'] }}</td>
            <td>{{ number_format($row['total_disbursed'], 2) }}</td>
            <td>{{ number_format($row['outstanding_balance'], 2) }}</td>
            <td>{{ number_format($row['total_paid'], 2) }}</td>
            <td>{{ number_format($row['overdue_amount'], 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="7">No data for the selected filters.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
