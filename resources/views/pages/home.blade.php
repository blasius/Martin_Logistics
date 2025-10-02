@extends('layouts.app')

@section('content')
    {{-- Hero Section --}}
    <section class="text-center py-20 bg-gradient-to-r from-[#222222] to-[#dc3534] text-white">
        <h1 class="text-4xl md:text-5xl font-extrabold mb-4">
            Reliable Logistics for East Africa & Beyond
        </h1>
        <p class="text-lg md:text-xl mb-6">
            Fast, secure, and transparent transportation for businesses that move the region forward.
        </p>
        <a href="/services"
           class="bg-[#e9ec3c] text-[#222222] px-6 py-3 rounded-lg font-semibold hover:bg-white transition">
            Explore Our Services
        </a>
    </section>

    {{-- Services Section --}}
    <section class="max-w-6xl mx-auto py-16 px-6">
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
    <section class="bg-gray-100 py-16 px-6">
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
    <section class="text-center py-20">
        <h2 class="text-3xl font-bold text-[#222222] mb-6">
            Ready to Move with Us?
        </h2>
        <p class="text-gray-600 mb-6">Get in touch today for a free quote or shipment tracking.</p>
        <a href="/contact"
           class="bg-[#dc3534] text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#222222] transition">
            Contact Us
        </a>
    </section>
@endsection
