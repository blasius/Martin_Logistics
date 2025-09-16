<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order #{{ $order->reference }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .section { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px; text-align: left; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
<div class="header">
    <h2>Delivery Note</h2>
    <p>Order Reference: <strong>{{ $order->reference }}</strong></p>
</div>

<div class="section">
    <h4>Client Information</h4>
    <p><strong>Name:</strong> {{ $order->client->name ?? '-' }}</p>
    <p><strong>Phone:</strong> {{ $order->client->phone ?? '-' }}</p>
</div>

<div class="section">
    <h4>Order Details</h4>
    <table>
        <tr>
            <th>Origin</th>
            <td>{{ $order->origin }}</td>
        </tr>
        <tr>
            <th>Destination</th>
            <td>{{ $order->destination }}</td>
        </tr>
        <tr>
            <th>Status</th>
            <td>{{ ucfirst($order->status) }}</td>
        </tr>
        <tr>
            <th>Price</th>
            <td>{{ number_format($order->price, 2) }}</td>
        </tr>
    </table>
</div>
</body>
</html>
