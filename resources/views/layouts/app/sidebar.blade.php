<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
        <div class="flex min-h-dvh w-full">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 shrink-0">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="megaphone" :href="route('campaigns')" :current="request()->routeIs('campaigns')" wire:navigate>
                        {{ __('Campaigns') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="academic-cap" :href="route('schools')" :current="request()->routeIs('schools')" wire:navigate>
                        {{ __('Schools') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Settings')" class="grid">
                    <flux:sidebar.item icon="building-library" :href="route('settings.foundation')" :current="request()->routeIs('settings.foundation')" wire:navigate>
                        {{ __('Foundation') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>



            @auth
            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name ?? ''" />
            @endauth
        </flux:sidebar>

        <div class="flex min-w-0 flex-1 flex-col">
        <!-- Mobile User Menu -->
        <flux:header class="shrink-0 lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            @auth
            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
            @endauth
        </flux:header>

        <div class="min-h-0 flex-1 overflow-auto">
            {{ $slot }}
        </div>
        </div>
        </div>

        @fluxScripts
    </body>
</html>
