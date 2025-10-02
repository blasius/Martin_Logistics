@extends('layouts.app')

@section('content')
    {{-- Hero Section with Image --}}
    <section class="relative text-center py-28 text-white">
        <!-- Background image -->
        <div class="absolute inset-0">
            <img src="https://plus.unsplash.com/premium_photo-1661879188208-21ffbe983feb?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D?q=80&w=1906&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D?auto=format&fit=crop&w=1600&q=80"
                 alt="Logistics Fleet" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-[#222222]/80"></div>
        </div>

        <div class="relative z-10 max-w-3xl mx-auto px-6">
            <h1 class="text-4xl md:text-6xl font-extrabold mb-6 leading-tight">
                Powering East Africa’s Supply Chain
            </h1>
            <p class="text-lg md:text-2xl mb-8">
                Road freight, warehousing & cross-border logistics — <br class="hidden md:block">
                with speed, reliability, and transparency.
            </p>
            <a href="/services"
               class="bg-[#e9ec3c] text-[#222222] px-8 py-4 rounded-lg font-semibold shadow-lg hover:bg-white transition">
                Explore Our Services
            </a>
        </div>
    </section>

    {{-- Services Section --}}
    <section class="max-w-6xl mx-auto py-20 px-6">
        <h2 class="text-3xl font-bold text-center text-[#222222] mb-12">Our Core Services</h2>

        <div class="grid md:grid-cols-3 gap-10">
            <div class="p-6 bg-gray-50 rounded-lg shadow hover:shadow-lg transition">
                <div class="text-[#dc3534] text-4xl mb-4">🚛</div>
                <h3 class="text-xl font-bold mb-2">Freight Transport</h3>
                <p class="text-gray-600">
                    Reliable road transport across East Africa with real-time tracking and secure handling.
                </p>
            </div>

            <div class="p-6 bg-gray-50 rounded-lg shadow hover:shadow-lg transition">
                <div class="text-[#dc3534] text-4xl mb-4">📦</div>
                <h3 class="text-xl font-bold mb-2">Warehousing</h3>
                <p class="text-gray-600">
                    Secure, scalable storage solutions with efficient inventory management.
                </p>
            </div>

            <div class="p-6 bg-gray-50 rounded-lg shadow hover:shadow-lg transition">
                <div class="text-[#dc3534] text-4xl mb-4">🌍</div>
                <h3 class="text-xl font-bold mb-2">Cross-Border Logistics</h3>
                <p class="text-gray-600">
                    Seamless customs clearance and border handling for regional and international shipments.
                </p>
            </div>
        </div>
    </section>

    {{-- Why Choose Us --}}
    <section class="bg-gray-100 py-20 px-6">
        <div class="max-w-6xl mx-auto grid md:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-3xl font-bold text-[#222222] mb-6">Why Choose Our Company?</h2>
                <ul class="space-y-4 text-gray-700">
                    <li>✅ 24/7 Customer Support</li>
                    <li>✅ Real-time GPS tracking for all shipments</li>
                    <li>✅ 10+ years experience in logistics & transport</li>
                    <li>✅ Strong cross-border expertise across East Africa</li>
                </ul>
            </div>
            <div class="bg-white rounded-lg shadow p-8">
                <h3 class="text-xl font-bold text-[#dc3534] mb-4">Quick Facts</h3>
                <p class="mb-2">🚚 <span class="font-bold">150+ Vehicles</span> in operation</p>
                <p class="mb-2">📍 <span class="font-bold">7 Countries</span> covered</p>
                <p class="mb-2">👥 <span class="font-bold">300+ Clients</span> served</p>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="text-center py-20 bg-gradient-to-r from-[#dc3534] to-[#222222] text-white">
        <h2 class="text-3xl font-bold mb-6">
            Ready to Move with Us?
        </h2>
        <p class="text-lg mb-6">Get in touch today for a free quote or shipment tracking.</p>
        <a href="/contact"
           class="bg-[#e9ec3c] text-[#222222] px-8 py-4 rounded-lg font-semibold hover:bg-white transition">
            Contact Us
        </a>
    </section>
@endsection
