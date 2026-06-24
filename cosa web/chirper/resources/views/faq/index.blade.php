@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-10 text-center">
        <h1 class="text-3xl font-bold text-primary-900 font-heading">Preguntas Frecuentes (FAQ)</h1>
        <p class="mt-2 text-gray-600">Resolviendo tus dudas sobre el Sistema Integrado de Gestión y Transparencia de Desastres.</p>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="divide-y divide-gray-200">
            @foreach(config('faq.questions') as $faq)
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 font-heading mb-2">{{ $faq['question'] }}</h3>
                <p class="text-gray-600 leading-relaxed">{{ $faq['answer'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
