@extends('layouts.app')

@section('content')
    <section class="py-16 bg-white">
        <div class="container mx-auto max-w-3xl">
            <h1 class="text-4xl font-bold text-gray-900 mb-6">{{ $service->name }}</h1>
            <p class="text-gray-700 text-lg mb-8">{{ $service->description }}</p>

            <a href="{{ route('services.index') }}"
               class="inline-block px-6 py-3 bg-blue-600 text-white rounded-xl shadow hover:bg-blue-700 transition">
                ← Back to Services
            </a>
        </div>
    </section>
@endsection
