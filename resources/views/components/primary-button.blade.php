<button {{ $attributes->merge(['type' => 'submit', 'class' => 'boton-primario']) }}>
    {{ $slot }}
</button>
