@props(['class' => 'h-10 w-auto rounded-full object-cover'])

<img src="{{ asset('logo.jpeg') }}" alt="{{ config('app.name', 'immo') }}" {{ $attributes->merge(['class' => $class]) }}>
