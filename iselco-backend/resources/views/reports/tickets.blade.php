<!DOCTYPE html>
<html>
<head>
    <title>Tickets Report</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
        .meta { margin-bottom: 15px; font-size: 9pt; color: #555; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Tickets Report</h2>
        <p>Generated on {{ date('Y-m-d H:i:s') }}</p>
    </div>

    <div class="meta">
        <strong>Total Records:</strong> {{ $tickets->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Ticket #</th>
                <th>Title</th>
                <th>Status</th>
                <th>Department</th>
                <th>Requestor</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $ticket)
            <tr>
                <td>{{ $ticket->ticket_number }}</td>
                <td>{{ Str::limit($ticket->title, 40) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</td>
                <td>{{ $ticket->department->name ?? '-' }}</td>
                <td>{{ $ticket->requestor->employee_name ?? $ticket->requestor->username ?? '-' }}</td>
                <td>{{ $ticket->created_at->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
