@extends('layouts.app')

@section('title', 'Server Error')

@section('content')
    <div class="min-h-screen flex flex-col justify-center items-center bg-gradient-to-r from-red-50 to-orange-50">
        <div class="text-center px-6">
            <h1 class="text-9xl font-extrabold text-red-600">500</h1>
            <h2 class="mt-4 text-2xl md:text-3xl font-semibold text-gray-800">Something went wrong</h2>
            <p class="mt-4 text-gray-600 max-w-md mx-auto">
                Our team has been notified and we’re working to fix it. Please try again later.
            </p>
            <div class="mt-6">
                <a href="{{ url('/') }}"
                   class="px-6 py-3 bg-red-600 text-white rounded-xl shadow hover:bg-red-700 transition">
                    Back to Home
                </a>
            </div>
        </div>
    </div>
@endsection
