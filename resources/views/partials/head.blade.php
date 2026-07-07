<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])

@livewireStyles

{{-- Apply the persisted theme before first paint to avoid a flash of the wrong mode. --}}
<script>
    window.applyTheme = () => {
        const mode = (localStorage.getItem('theme-mode') || 'system').replaceAll('"', '');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const dark = mode === 'dark' || (mode === 'system' && prefersDark);
        document.documentElement.setAttribute('data-theme', dark ? 'irongall' : 'khata');
        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.classList.toggle('light', !dark);
    };

    // First paint, plus a fallback for navigations restored from bf-cache (which
    // skip the in-body re-apply below).
    window.applyTheme();
    document.addEventListener('livewire:navigated', window.applyTheme);
</script>
