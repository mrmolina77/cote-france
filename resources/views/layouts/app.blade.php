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
        <link rel="stylesheet" href="https://demos.creative-tim.com/notus-js/assets/styles/tailwind.css">
        <link rel="stylesheet" href="https://demos.creative-tim.com/notus-js/assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">

        @livewireStyles
    </head>
    <body class="bg-gray-100 font-family-karla flex">
        <x-layout.aside />
        <div class="relative w-full flex flex-col h-screen">
            <!-- Desktop Header -->
            <x-layout.desktop-header />
            <!-- Mobile Header & Nav -->
            <x-layout.mobile-header />
            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>

        </div>
        @livewireScripts

        @stack('js')

        <script>
            Livewire.on('alert',function(message){
                Swal.fire({
                    title: "Exito!",
                    text: message,
                    icon: "success"
                });
            })
        </script>
    </body>
</html>
