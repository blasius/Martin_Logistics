<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Receipt — {{ $pod->order?->reference }}</title>
    <style>
        @page { margin: 120px 40px 100px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        header {
            position: fixed; top: -90px; left: 0; right: 0; height: 50px;
            text-align: center; border-bottom: 2px solid #333; padding-bottom: 30px;
        }
        footer {
            position: fixed; bottom: -15px; left: 0; right: 0; height: 80px;
            border-top: 2px solid #333; text-align: center; font-size: 11px; padding-top: 5px;
        }
        h1 { text-align: center; font-size: 18px; margin-bottom: 20px; }
        h2 { font-size: 14px; margin-top: 20px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; }
        th { background: #f0f0f0; width: 35%; }
        .signature-img { max-width: 300px; max-height: 80px; margin-top: 10px; }
        .photo-img { max-width: 100%; max-height: 200px; margin-top: 10px; }
        .status-badge {
            display: inline-block; padding: 2px 10px; border-radius: 10px;
            font-size: 11px; font-weight: bold;
        }
        .status-submitted { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>
<header>
    <div style="width: 95%;">
        <img src="{{ public_path('images/header_martin.png') }}" alt="Logo" style="max-width: 100%; height: 60px;">
    </div>
</header>
<footer>
    <img src="{{ public_path('images/footer_banner.png') }}" style="width: 100%; height: auto;" alt="Footer">
    <div style="margin-top: 5px; font-size: 11px;">
        This delivery receipt was generated on {{ now()->format('d M Y H:i') }}.<br>
        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
    </div>
</footer>

<main>
    <h1>Delivery Receipt</h1>

    <table>
        <tr><th>Order Reference</th><td>{{ $pod->order?->reference }}</td></tr>
        <tr><th>Origin</th><td>{{ $pod->order?->origin }}</td></tr>
        <tr><th>Destination</th><td>{{ $pod->order?->destination }}</td></tr>
        <tr><th>Delivered At</th><td>{{ $pod->delivered_at?->format('d M Y H:i') }}</td></tr>
        <tr><th>Status</th>
            <td>
                <span class="status-badge status-{{ $pod->status }}">
                    {{ ucfirst($pod->status) }}
                </span>
            </td>
        </tr>
    </table>

    <h2>Receipt Information</h2>
    <table>
        <tr><th>Received By</th><td>{{ $pod->received_by_name }}</td></tr>
        <tr><th>Relation</th><td>{{ $pod->received_by_relation ?? '-' }}</td></tr>
        <tr><th>Notes</th><td>{{ $pod->notes ?? '-' }}</td></tr>
        @if($pod->gps_lat && $pod->gps_lng)
        <tr><th>GPS Coordinates</th><td>{{ $pod->gps_lat }}, {{ $pod->gps_lng }}</td></tr>
        @endif
        <tr><th>Submitted By</th><td>{{ $pod->submitter?->name ?? '-' }}</td></tr>
    </table>

    @if($pod->signature_data)
    <h2>Signature</h2>
    <div>
        @if(str_starts_with($pod->signature_data, '<svg'))
            {!! $pod->signature_data !!}
        @elseif(str_starts_with($pod->signature_data, 'data:image'))
            <img class="signature-img" src="{{ $pod->signature_data }}" alt="Signature">
        @else
            <img class="signature-img" src="data:image/png;base64,{{ $pod->signature_data }}" alt="Signature">
        @endif
    </div>
    @endif

    @if($pod->photo_path && Storage::disk('public')->exists($pod->photo_path))
    <h2>Delivery Photo</h2>
    <div>
        <img class="photo-img" src="{{ public_path('storage/' . $pod->photo_path) }}" alt="Delivery Photo">
    </div>
    @endif

    @if($pod->trip)
    <h2>Trip Information</h2>
    <table>
        <tr><th>Vehicle</th><td>{{ $pod->trip->vehicle?->plate ?? $pod->trip->vehicle_plate_snapshot ?? '-' }}</td></tr>
        <tr><th>Driver</th><td>{{ $pod->trip->driver?->user?->name ?? $pod->trip->driver_name_snapshot ?? '-' }}</td></tr>
        <tr><th>Distance (km)</th><td>{{ $pod->trip->actual_distance_km ?? $pod->trip->planned_distance_km ?? '-' }}</td></tr>
    </table>
    @endif
</main>
</body>
</html>
