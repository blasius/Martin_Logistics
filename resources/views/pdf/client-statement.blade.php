<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 3px 0; color: #666; }
        .client-info { margin-bottom: 15px; }
        .client-info h3 { margin: 0 0 5px; }
        .client-info p { margin: 2px 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background: #f5f5f5; border: 1px solid #ddd; padding: 6px; text-align: left; font-weight: bold; font-size: 9px; }
        td { border: 1px solid #ddd; padding: 5px; font-size: 9px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { font-weight: bold; background: #f9f9f9; }
        .footer { text-align: center; color: #999; font-size: 8px; margin-top: 20px; border-top: 1px solid #eee; padding-top: 8px; }
        .status-paid { color: green; }
        .status-overdue { color: red; }
        .status-sent { color: #cc7a00; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Client Statement</h1>
        <p>Martin Logistics</p>
    </div>

    <div class="client-info">
        <h3>{{ $statement['client']?->name ?? 'Client' }}</h3>
        @if($statement['client']?->address)
            <p>{{ $statement['client']->address }}</p>
        @endif
        @if($statement['client']?->phone)
            <p>Phone: {{ $statement['client']->phone }}</p>
        @endif
        @if($statement['client']?->email)
            <p>Email: {{ $statement['client']->email }}</p>
        @endif
        <p>Total Outstanding: <strong>{{ number_format($statement['total_outstanding'], 2) }}</strong></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Type</th>
                <th>Due Date</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Paid</th>
                <th class="text-right">Balance</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($statement['entries'] as $entry)
                <tr>
                    <td>{{ $entry['date'] }}</td>
                    <td>{{ $entry['reference'] }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $entry['type'])) }}</td>
                    <td>{{ $entry['due_date'] }}</td>
                    <td class="text-right">{{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '-' }}</td>
                    <td class="text-right">{{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '-' }}</td>
                    <td class="text-right">{{ $entry['paid'] > 0 ? number_format($entry['paid'], 2) : '-' }}</td>
                    <td class="text-right">{{ number_format($entry['balance'], 2) }}</td>
                    <td class="text-center">
                        <span class="status-{{ $entry['status'] }}">{{ ucfirst($entry['status']) }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No transactions found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('Y-m-d H:i') }} | Martin Logistics
    </div>
</body>
</html>
