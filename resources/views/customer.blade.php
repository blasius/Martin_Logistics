<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Portal — Martin Logistics</title>
    <link rel="icon" type="image/png" href="{{ asset('images/martin_hardware_logo.png') }}">
    @include('partials.ga-analytics')
    @vite(['resources/js/customer/main.js'])
</head>
<body class="antialiased bg-gray-50">
<div id="app"></div>
</body>
</html>
