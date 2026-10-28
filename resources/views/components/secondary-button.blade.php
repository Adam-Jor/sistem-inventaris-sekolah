<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-outline-secondary mb-0']) }}>
    {{ $slot }}
</button>
