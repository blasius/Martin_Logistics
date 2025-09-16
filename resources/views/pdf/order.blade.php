<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order #{{ $order->reference }}</title>
    <style>
        @page {
            margin: 120px 40px 100px 40px; /* top, right, bottom, left */
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }
        header {
            position: fixed;
            top: -90px;
            left: 0;
            right: 0;
            height: 50px;
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 30px;
        }
        footer {
            position: fixed;
            bottom: -15px;
            left: 0;
            right: 0;
            height: 80px;
            border-top: 2px solid #333;
            text-align: center;
            font-size: 11px;
            padding-top: 5px;
        }
        .logo {
            float: left;
            width: 120px;
        }
        .company-info {
            text-align: right;
            font-size: 12px;
        }
        .content {
            margin-top: 10px;
        }
        .signature-block {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }
        .signature {
            width: 45%;
            text-align: center;
        }
        body { font-family: DejaVu Sans, sans-serif; }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
    </style>
</head>
<body>
<header>

    <div class="logo" style="width: 95%;">
        <img src="{{ public_path('images/header_martin.png') }}" alt="Logo" style="max-width: 100%; height: 60px;">
    </div>
</header>

<footer>
    <img src="{{ public_path('images/footer_banner.png') }}"
         style="width: 100%; height: auto;" alt="Footer Banner">
    <div style="margin-top: 5px; font-size: 11px;">
        This document was generated on {{ now()->format('d M Y H:i') }}.<br>
        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
    </div>
</footer>

<main class="content">
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
</main>
</body>
</html>
