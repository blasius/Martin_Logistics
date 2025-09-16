<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
    </style>
</head>
<body>
<h1>Order Report</h1>
<p><strong>Reference:</strong> {{ $order->reference }}</p>
<p><strong>Client:</strong> {{ $order->client->name }}</p>
<p><strong>Origin:</strong> {{ $order->origin }}</p>
<p><strong>Destination:</strong> {{ $order->destination }}</p>
<p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
<p><strong>Pickup Date:</strong> {{ $order->pickup_date }}</p>
<p><strong>Price:</strong> ${{ number_format($order->price, 2) }}</p>
<p><strong>Notes:</strong> {{ $order->notes }}</p>
</body>
</html>
