@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-white/25 focus:border-red-600 focus:ring-1 focus:ring-red-600 focus:outline-none transition-colors disabled:opacity-50']) }}>
