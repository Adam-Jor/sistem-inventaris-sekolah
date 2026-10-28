<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn bg-gradient-info mb-0']) }}>
    {{ $slot }}
</button>
