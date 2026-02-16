@php
    app()->setLocale('ro');
@endphp
<!DOCTYPE html>
<html lang="ro" class="h-full bg-zinc-50 dark:bg-zinc-900">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'School Portal' }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="h-full antialiased">
        <div class="min-h-full flex flex-col">
            <header class="bg-white dark:bg-zinc-800 shadow-sm border-b border-zinc-200 dark:border-zinc-700">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    @auth
                        @php $portalSchool = request()->route('school'); @endphp
                        @if(auth()->user()->hasRole('admin') && session('impersonate_school_id') && $portalSchool)
                            <div class="bg-amber-100 dark:bg-amber-900/30 border-b border-amber-200 dark:border-amber-800 px-4 py-2 flex items-center justify-between gap-4">
                                <span class="text-sm text-amber-800 dark:text-amber-200">
                                    {{ __('Impersonating') }}: <strong>{{ $portalSchool->official_name }}</strong>
                                </span>
                                <a href="{{ route('exit-impersonation') }}" class="text-sm font-medium text-amber-800 dark:text-amber-200 hover:underline shrink-0">
                                    {{ __('Exit to admin dashboard') }} →
                                </a>
                            </div>
                        @endif
                    @endauth
                    <div class="flex h-16 justify-between items-center">
                        <div class="flex items-center gap-4">
                            @if($logo = \App\Models\Foundation::first()?->logo_path)
                                <img src="{{ $logo }}" class="h-8 w-auto" alt="Logo">
                            @endif
                        </div>
                        <div class="flex items-center gap-4">
                            @auth
                                <span class="text-sm text-zinc-500">{{ auth()->user()->name }}</span>
                            @endauth
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 py-10">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </main>
        </div>

        <flux:toast />
        @fluxScripts
    </body>
</html>
