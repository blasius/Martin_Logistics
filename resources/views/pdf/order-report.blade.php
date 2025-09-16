<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Orders Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
<h2>Orders Report</h2>
<table>
    <thead>
    <tr>
        <th>Reference</th>
        <th>Client</th>
        <th>Origin</th>
        <th>Destination</th>
        <th>Status</th>
        <th>Price</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($orders as $order)
        <tr>
            <td>{{ $order->reference }}</td>
            <td>{{ $order->client->name ?? '-' }}</td>
            <td>{{ $order->origin }}</td>
            <td>{{ $order->destination }}</td>
            <td>{{ ucfirst($order->status) }}</td>
            <td>{{ number_format($order->price, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
