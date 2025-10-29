<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
        <link rel="icon" href="{{ asset('favicon.ico') }}">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        <link rel="stylesheet" href="https://demos.creative-tim.com/notus-js/assets/styles/tailwind.css">
        <link rel="stylesheet" href="https://demos.creative-tim.com/notus-js/assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
        @stack('css')
        @livewireStyles
    </head>
    <body class="bg-gray-100 font-sans flex">
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
            Livewire.on('alert',function(message,titulo = "Exito!",icono = "success"){
                Swal.fire({
                    title: titulo,
                    text: message,
                    icon: icono
                });
            })
        </script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    </body>
</html>
