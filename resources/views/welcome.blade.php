<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#0B1A4A] via-[#152C70] to-[#1E3A8A] px-4 relative overflow-hidden">

        <!-- Fondo rejilla -->
        <div class="absolute inset-0 opacity-20">
            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)" />
            </svg>
        </div>

        <!-- Contenedor principal -->
        <div class="relative flex flex-col md:flex-row w-full max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden z-10">

            <!-- Lado izquierdo con el logo -->
            <div class="md:w-1/2 w-full flex items-center justify-center bg-[#0B1A4A] p-10">
                <img src="{{ asset('images/cote_logo_white.png') }}" alt="Logo Côte Français" class="w-56 md:w-64">
            </div>

            <!-- Lado derecho con el formulario -->
            <div class="md:w-1/2 w-full p-10 flex flex-col justify-center">
                <h2 class="text-3xl font-extrabold text-gray-800 mb-6 text-center">Iniciar sesión</h2>

                <x-validation-errors class="mb-4" />

                @if (session('status'))
                    <div class="mb-4 font-medium text-sm text-green-600">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Email -->
                    <div class="mb-4">
                        <input id="email" type="email" name="email"
                               class="w-full px-4 py-3 border rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                               placeholder="Correo electrónico" :value="old('email')" required autofocus autocomplete="username" />
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <input id="password" type="password" name="password"
                               class="w-full px-4 py-3 border rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                               placeholder="Contraseña" required autocomplete="current-password" />
                    </div>

                    <!-- Remember -->
                    <div class="flex items-center mb-4">
                        <x-checkbox id="remember_me" name="remember" />
                        <label for="remember_me" class="ml-2 text-sm text-gray-600">Recordarme</label>
                    </div>

                    <!-- Botón login -->
                    <div class="flex items-center justify-between">
                        <x-button class="w-full justify-center bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl py-3 text-lg shadow-lg">
                            Iniciar sesión
                        </x-button>
                    </div>

                    <!-- Forgot password -->
                    <div class="mt-6 text-center">
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm text-indigo-500 hover:underline">
                                ¿Olvidaste tu contraseña?
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
