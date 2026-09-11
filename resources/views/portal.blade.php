<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Portal</title>
    <link rel="icon" type="image/png" href="{{ asset('images/martin_hardware_logo.png') }}">
    @include('partials.ga-analytics')
    @vite(['resources/js/portal/main.js'])
</head>
<body class="antialiased">
<div id="app"></div>
</body>
</html>
