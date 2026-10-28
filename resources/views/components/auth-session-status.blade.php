@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'alert alert-success text-white text-sm']) }} role="alert">
        {{ $status }}
    </div>
@endif
