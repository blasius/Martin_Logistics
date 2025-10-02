@extends('layouts.app')

@section('content')
    <section class="text-center mb-16">
        <h1 class="text-3xl font-extrabold text-[#222222] mb-4">About Martin Logistics</h1>
        <p class="text-lg text-gray-600">
            We are committed to providing reliable logistics services that connect businesses and people across borders.
        </p>
    </section>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
        <div>
            <img src="/about-truck.jpg" alt="Logistics Truck" class="rounded-lg shadow-lg">
        </div>
        <div>
            <h2 class="text-2xl font-bold text-[#222222] mb-4">Our Mission</h2>
            <p class="text-gray-600 mb-6">
                To empower businesses with efficient, safe, and innovative logistics solutions that deliver growth and connectivity.
            </p>

            <h2 class="text-2xl font-bold text-[#222222] mb-4">Our Vision</h2>
            <p class="text-gray-600">
                To be the most trusted logistics partner in the region, driving excellence in cross-border transportation.
            </p>
        </div>
    </div>

    <section class="mt-20 text-center">
        <h2 class="text-2xl font-bold text-[#222222] mb-6">Our Values</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="p-6 border rounded-lg shadow hover:shadow-lg transition">
                <h3 class="font-bold mb-2">Reliability</h3>
                <p class="text-sm text-gray-600">We deliver on our promises with consistency and trust.</p>
            </div>
            <div class="p-6 border rounded-lg shadow hover:shadow-lg transition">
                <h3 class="font-bold mb-2">Innovation</h3>
                <p class="text-sm text-gray-600">We leverage technology to make logistics smarter and faster.</p>
            </div>
            <div class="p-6 border rounded-lg shadow hover:shadow-lg transition">
                <h3 class="font-bold mb-2">Customer Focus</h3>
                <p class="text-sm text-gray-600">Our clients are at the heart of everything we do.</p>
            </div>
        </div>
    </section>
@endsection
