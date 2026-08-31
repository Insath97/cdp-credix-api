@extends('reports.layout')

@section('content')
<table>
    <thead>
        <tr>
            <th>Branch Code</th>
            <th>Branch Name</th>
            <th>Applications</th>
            <th>Total Disbursed</th>
            <th>Outstanding Portfolio</th>
            <th>Total Collected</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
        <tr>
            <td>{{ $row['branch_code'] }}</td>
            <td>{{ $row['branch_name'] }}</td>
            <td>{{ $row['applications_count'] }}</td>
            <td>{{ number_format($row['total_disbursed'], 2) }}</td>
            <td>{{ number_format($row['outstanding_portfolio'], 2) }}</td>
            <td>{{ number_format($row['total_collected'], 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="6">No data for the selected filters.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
