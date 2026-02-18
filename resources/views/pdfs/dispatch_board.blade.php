<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #1e293b; margin: 0; padding: 20px; font-size: 11px; }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 18px; font-weight: bold; text-transform: uppercase; color: #1e293b; }
        .meta { color: #64748b; font-size: 10px; margin-top: 5px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f8fafc; color: #475569; text-transform: uppercase; font-size: 9px; padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }

        .plate { font-weight: bold; color: #0f172a; font-size: 12px; }
        .specs { color: #64748b; font-style: italic; }

        .badge { padding: 3px 6px; border-radius: 4px; font-size: 8px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .status-active { background-color: #dcfce7; color: #15803d; }
        .status-maintenance { background-color: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>
<div class="header">
    <div class="title">Fleet Dispatch Board</div>
    <div class="meta">Status Report | Generated: {{ now()->format('F j, Y, g:i a') }}</div>
</div>

<table>
    <thead>
    <tr>
        <th>Unit ID</th>
        <th>Specifications</th>
        <th>Current Driver</th>
        <th>Attached Trailer</th>
        <th>Status</th>
    </tr>
    </thead>
    <tbody>
    @foreach($vehicles as $v)
        <tr>
            <td class="plate">{{ $v->plate_number }}</td>
            <td class="specs">{{ $v->make }} {{ $v->model }}</td>
            <td>{{ $v->current_driver->name ?? 'STANDBY' }}</td>
            <td>{{ $v->currentAssignment->trailer->plate_number ?? 'BOBTAIL' }}</td>
            <td>
                <span class="badge status-{{ $v->status }}">{{ $v->status }}</span>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
