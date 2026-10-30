<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn bg-gradient-primary mb-0']) }}>
    {{ $slot }}
</button>
