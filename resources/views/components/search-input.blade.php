@props([
    'name' => 'search',
    'value' => '',
    'placeholder' => 'Cari data...',
])

<div class="modern-search-wrap" x-data="{ q: '' }" x-init="q = $refs.input.value">
    <span class="modern-search-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
    </span>

    <input
        x-ref="input"
        type="text"
        name="{{ $name }}"
        value="{{ $value }}"
        x-model="q"
        x-on:input.debounce.350ms="$el.form.requestSubmit ? $el.form.requestSubmit() : $el.form.submit()"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        {{ $attributes->merge(['class' => 'form-control form-control-sm bg-white modern-search-input']) }}
    >

    <button
        type="button"
        x-show="q.length > 0"
        x-cloak
        x-on:click="q = ''; $refs.input.value = ''; $nextTick(() => { const f = $el.closest('form'); f.requestSubmit ? f.requestSubmit() : f.submit(); $refs.input.focus(); })"
        class="modern-search-clear"
        title="Hapus kata kunci pencarian"
        aria-label="Hapus kata kunci pencarian"
    >&times;</button>
</div>

