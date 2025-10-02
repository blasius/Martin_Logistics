@extends('layouts.app')

@section('content')
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto text-center">
            <h2 class="text-4xl font-bold text-gray-900 mb-6">Our Services</h2>
            <p class="text-lg text-gray-600 mb-12">We provide everything you need to get your website online and secure.</p>

            <div class="grid md:grid-cols-3 gap-8">
                @foreach($services as $service)
                    <div class="bg-white rounded-2xl shadow-md p-8 hover:shadow-lg transition">
                        <div class="flex justify-center mb-4">
                            <i class="lucide lucide-{{ $service->icon }} text-blue-600 text-4xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ $service->name }}</h3>
                        <p class="text-gray-600 mb-4">{{ $service->description }}</p>
                        <a href="{{ route('services.show', $service->slug) }}"
                           class="text-green-600 font-medium hover:underline">
                            Learn More →
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
