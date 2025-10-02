@props(['title' => ''])

    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?: 'Logistics Company' }}</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-white text-gray-900 font-sans">

<!-- Header -->
<header class="bg-white shadow p-4 flex justify-between items-center">
    <div class="text-2xl font-bold text-gray-900">Logistics Co.</div>
    <nav class="space-x-4">
        <a href="/" class="text-gray-900 hover:text-yellow-400">Home</a>
        <a href="/tracking" class="text-gray-900 hover:text-yellow-400">Track Order</a>
        <a href="/contact" class="text-gray-900 hover:text-yellow-400">Contact</a>
    </nav>
</header>

<!-- Main Content -->
<main class="max-w-7xl mx-auto px-6 py-8">
    {{ $slot }}
</main>

<!-- Footer -->
<footer class="bg-gray-100 text-gray-900 p-6 text-center">
    &copy; {{ date('Y') }} Logistics Co. All rights reserved.
</footer>

@vite('resources/js/app.js')
</body>
</html>
