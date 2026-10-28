@props(['value' => null, 'required' => false])

<label {{ $attributes->merge(['class' => 'form-label']) }}>
    {{ $value ?? $slot }}@if ($required)<span class="text-danger ms-1">*</span>@endif
</label>
