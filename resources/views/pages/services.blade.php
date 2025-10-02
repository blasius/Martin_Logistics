@extends('layouts.app')

@section('content')
    <section class="text-center mb-16">
        <h1 class="text-3xl font-extrabold text-[#222222] mb-4">Our Services</h1>
        <p class="text-lg text-gray-600">
            Martin Logistics offers reliable and efficient logistics solutions to help your business grow.
        </p>
    </section>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
        <div class="p-6 border rounded-lg shadow hover:shadow-lg transition">
            <h3 class="text-xl font-bold text-[#222222] mb-2">Freight Shipping</h3>
            <p class="text-sm text-gray-600">
                Fast and secure freight services across borders by road, air, and sea.
            </p>
        </div>
        <div class="p-6 border rounded-lg shadow hover:shadow-lg transition">
            <h3 class="text-xl font-bold text-[#222222] mb-2">Warehousing</h3>
            <p class="text-sm text-gray-600">
                Modern storage facilities with advanced security for all goods.
            </p>
        </div>
        <div class="p-6 border rounded-lg shadow hover:shadow-lg transition">
            <h3 class="text-xl font-bold text-[#222222] mb-2">Last-Mile Delivery</h3>
            <p class="text-sm text-gray-600">
                Door-to-door delivery services that ensure your products reach your customers quickly.
            </p>
        </div>
    </div>

    <div class="mt-16 bg-[#e9ec3c] p-10 rounded-lg text-center">
        <h2 class="text-2xl font-bold text-[#222222] mb-4">Need a Custom Solution?</h2>
        <p class="mb-6 text-gray-800">Our team is ready to tailor logistics services for your specific needs.</p>
        <a href="/contact" class="bg-[#dc3534] text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#222222] transition">
            Contact Us
        </a>
    </div>
@endsection
