<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn bg-gradient-danger mb-0']) }}>
    {{ $slot }}
</button>
