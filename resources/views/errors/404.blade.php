@extends('layouts.app')

@section('title', 'Page Not Found')

@section('content')
    <div class="min-h-screen flex flex-col justify-center items-center bg-gradient-to-r from-blue-50 to-green-50">
        <div class="text-center px-6">
            <h1 class="text-9xl font-extrabold text-blue-600">404</h1>
            <h2 class="mt-4 text-2xl md:text-3xl font-semibold text-gray-800">Oops! Page not found</h2>
            <p class="mt-4 text-gray-600 max-w-md mx-auto">
                The page you’re looking for doesn’t exist or may have been moved.
            </p>
            <div class="mt-6 flex justify-center gap-4">
                <a href="{{ url('/') }}"
                   class="px-6 py-3 bg-blue-600 text-white rounded-xl shadow hover:bg-blue-700 transition">
                    Back to Home
                </a>
                <a href="/contact"
                   class="px-6 py-3 bg-gray-200 text-gray-800 rounded-xl shadow hover:bg-gray-300 transition">
                    Contact Support
                </a>
            </div>
        </div>
    </div>
@endsection
