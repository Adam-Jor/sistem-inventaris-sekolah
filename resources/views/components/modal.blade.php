@props(['name', 'show' => false, 'maxWidth' => 'md'])

@php
    $sizeClass = ['sm' => 'app-modal-sm', 'md' => 'app-modal-md', 'lg' => 'app-modal-lg'][$maxWidth] ?? 'app-modal-md';
@endphp

{{--
    Modal berbasis Alpine.js.
    Buka : $dispatch('open-modal', 'nama-modal')
    Tutup: $dispatch('close') dari dalam modal, atau $dispatch('close-modal', 'nama-modal')
--}}
<div
    x-data="{ show: @js($show) }"
    x-init="document.body.classList.toggle('overflow-hidden', show); $watch('show', value => document.body.classList.toggle('overflow-hidden', value))"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    class="app-modal"
    style="display: none;"
>
    <div x-show="show" x-on:click="show = false" class="app-modal-backdrop" x-transition.opacity></div>

    <div x-show="show" class="app-modal-dialog {{ $sizeClass }}" x-transition>
        <div class="card">
            <div class="card-body">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
