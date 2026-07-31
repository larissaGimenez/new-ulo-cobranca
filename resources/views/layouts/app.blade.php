<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <x-banner />

        <div class="drawer lg:drawer-open min-h-screen bg-base-100">
            <input id="main-drawer" type="checkbox" class="drawer-toggle" />
            
            <div class="drawer-content flex flex-col h-screen max-h-screen overflow-hidden">
                <!-- Navbar -->
                @include('layouts.navbar.index')

                <!-- Page Content -->
                <main class="flex-1 flex flex-col min-h-0 overflow-hidden p-2 sm:p-3 bg-base-100">
                    {{ $slot }}
                </main>
            </div>
            
            <div class="drawer-side z-40">
                <label for="main-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
                <!-- Sidebar -->
                @include('layouts.sidebar.index')
            </div>
        </div>

        @stack('modals')

        @livewireScripts
    </body>
</html>
