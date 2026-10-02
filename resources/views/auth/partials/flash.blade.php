@foreach ([
    'info' => 'bg-blue-500/10 border-blue-500/30 text-blue-600 dark:text-blue-400',
    'warning' => 'bg-amber-500/10 border-amber-500/30 text-amber-600 dark:text-amber-400',
    'error' => 'bg-rose-500/10 border-rose-500/30 text-rose-600 dark:text-rose-400',
    'success' => 'bg-emerald-500/10 border-emerald-500/30 text-emerald-600 dark:text-emerald-400',
] as $key => $classes)
    @if(session($key))
        <div class="mb-4 p-3 border text-xs rounded-xl {{ $classes }}">
            {{ session($key) }}
        </div>
    @endif
@endforeach
