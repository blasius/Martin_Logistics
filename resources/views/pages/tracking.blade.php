@extends('layouts.app')

@section('content')
    <section class="text-center mb-16">
        <h1 class="text-3xl font-extrabold text-[#222222] mb-4">Track Your Shipment</h1>
        <p class="text-lg text-gray-600">
            Enter your tracking number to view the latest shipment details and delivery status.
        </p>
    </section>

    {{-- Tracking Form --}}
    <div class="max-w-lg mx-auto bg-gray-50 p-8 rounded-lg shadow">
        <form method="GET" action="{{ url('/tracking') }}" class="flex gap-3">
            <input
                type="text"
                name="tracking_number"
                placeholder="Enter Tracking Number"
                class="flex-1 p-3 border rounded-lg focus:ring-[#dc3534] focus:border-[#dc3534]"
                required
            >
            <button
                type="submit"
                class="bg-[#dc3534] text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#222222] transition"
            >
                Track
            </button>
        </form>
    </div>

    {{-- Dummy Tracking Results --}}
    @if(request('tracking_number'))
        <div class="mt-16 max-w-2xl mx-auto">
            <div class="p-6 border rounded-lg shadow bg-white">
                <h2 class="text-2xl font-bold text-[#222222] mb-4">
                    Tracking Results for <span class="text-[#dc3534]">{{ request('tracking_number') }}</span>
                </h2>

                {{-- Shipment Overview --}}
                <div class="mb-6">
                    <p><span class="font-bold">Status:</span> <span class="text-green-600">In Transit</span></p>
                    <p><span class="font-bold">Origin:</span> Dar es Salaam, TZ</p>
                    <p><span class="font-bold">Destination:</span> Kigali, RW</p>
                    <p><span class="font-bold">Estimated Delivery:</span> Oct 10, 2025</p>
                </div>

                {{-- Tracking Steps --}}
                <div class="relative border-l-2 border-[#e9ec3c] pl-6 space-y-6">
                    <div>
                        <span class="absolute -left-3 w-6 h-6 bg-[#dc3534] rounded-full border-2 border-white"></span>
                        <p class="font-semibold text-[#222222]">Dar es Salaam Warehouse</p>
                        <p class="text-sm text-gray-600">Picked up on Oct 1, 2025 – 08:30</p>
                    </div>

                    <div>
                        <span class="absolute -left-3 w-6 h-6 bg-[#e9ec3c] rounded-full border-2 border-white"></span>
                        <p class="font-semibold text-[#222222]">On the Road</p>
                        <p class="text-sm text-gray-600">Departed border checkpoint on Oct 2, 2025 – 14:00</p>
                    </div>

                    <div>
                        <span class="absolute -left-3 w-6 h-6 bg-gray-300 rounded-full border-2 border-white"></span>
                        <p class="font-semibold text-[#222222]">Kigali Distribution Center</p>
                        <p class="text-sm text-gray-600">Expected arrival: Oct 9, 2025</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
