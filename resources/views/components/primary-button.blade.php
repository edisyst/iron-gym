<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-xl px-6 py-2.5 text-sm font-bold bg-red-600 hover:bg-red-500 active:bg-red-700 text-white transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-[#0d0d0d]']) }}>
    {{ $slot }}
</button>
