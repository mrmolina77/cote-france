<x-guest-layout>
    <div class="min-h-screen bg-slate-100 flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-6xl overflow-hidden rounded-[32px] bg-white shadow-2xl">
            <div class="grid grid-cols-1 lg:grid-cols-2">
                <section class="relative overflow-hidden bg-gradient-to-br from-blue-950 via-blue-900 to-blue-700 text-white">
                    <div class="absolute inset-0 opacity-30 bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.35),_transparent_55%)]"></div>
                    <div class="relative flex h-full flex-col gap-8 px-8 py-10 sm:px-12 sm:py-12">
                        <div class="flex items-center gap-3">
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10">
                                <img src="{{ asset('images/cote_logo_white.png') }}" alt="RankPilot" class="h-7 w-auto" />
                            </span>
                            <div>
                                <p class="text-lg font-semibold">RankPilot</p>
                                <p class="text-xs text-blue-100">Legal Rankings. Simplified</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h1 class="text-3xl font-semibold leading-tight sm:text-4xl">
                                Automatiza tus submissions.<br />
                                Acelera tu posicionamiento
                            </h1>
                            <p class="text-sm text-blue-100 sm:text-base">
                                La primera herramienta con inteligencia artificial especializada en rankings legales para América Latina y el mundo.
                            </p>
                            <div class="flex flex-wrap gap-2 pt-2">
                                <span class="rounded-full border border-white/20 bg-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-wide">Multiusuario</span>
                                <span class="rounded-full border border-white/20 bg-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-wide">Multilingüe</span>
                                <span class="rounded-full border border-white/20 bg-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-wide">100% Legaltech</span>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/10 p-6 text-sm">
                            <p class="text-base font-semibold">✨ Optimiza cada submission con IA jurídica y procesos inteligentes.</p>
                            <p class="mt-2 text-blue-100">Ahorra tiempo, mejora tus drafts y aumenta tus oportunidades de ranking.</p>
                            <ul class="mt-4 space-y-3 text-blue-100">
                                <li class="flex items-start gap-3">
                                    <span class="mt-1 flex h-5 w-5 items-center justify-center rounded-full bg-white/20">✓</span>
                                    Reduce hasta <span class="font-semibold text-white">70%</span> del tiempo de trabajo en cada submission.
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="mt-1 flex h-5 w-5 items-center justify-center rounded-full bg-white/20">✓</span>
                                    Redacción asistida con IA entrenada en lenguaje jurídico.
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="mt-1 flex h-5 w-5 items-center justify-center rounded-full bg-white/20">✓</span>
                                    Carga guiada + export a Word (Chambers, Legal 500, IFLR, Leaders League).
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>

                <section class="flex items-center justify-center bg-slate-50 px-6 py-10 sm:px-12">
                    <div class="w-full max-w-md rounded-3xl border border-slate-100 bg-white p-8 shadow-xl">
                        <div class="mb-6 space-y-2">
                            <h2 class="text-2xl font-semibold text-slate-900">Iniciar sesión</h2>
                            <p class="text-sm text-slate-500">Introduce tus accesos para entrar a RankPilot.</p>
                        </div>

                        <x-validation-errors class="mb-4" />

                        @if (session('status'))
                            <div class="mb-4 font-medium text-sm text-green-600">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}" class="space-y-5">
                            @csrf

                            <div>
                                <label for="email" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Correo electrónico</label>
                                <input
                                    id="email"
                                    class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    placeholder="admin@rankpilot.com"
                                    required
                                    autofocus
                                    autocomplete="username"
                                />
                            </div>

                            <div>
                                <label for="password" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Contraseña</label>
                                <div class="relative mt-2">
                                    <input
                                        id="password"
                                        class="w-full rounded-xl border border-slate-200 px-4 py-3 pr-16 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                        type="password"
                                        name="password"
                                        required
                                        autocomplete="current-password"
                                    />
                                    <button
                                        type="button"
                                        id="toggle-password"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-500 shadow-sm hover:text-slate-700"
                                    >
                                        Ver
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                                <label for="remember_me" class="flex items-center gap-2 text-slate-600">
                                    <input id="remember_me" name="remember" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-200" />
                                    Recordarme
                                </label>
                                @if (Route::has('password.request'))
                                    <a class="font-semibold text-blue-700 hover:text-blue-900" href="{{ route('password.request') }}">
                                        ¿Olvidaste tu clave?
                                    </a>
                                @endif
                            </div>

                            <button
                                type="submit"
                                class="w-full rounded-full bg-gradient-to-r from-blue-900 to-blue-600 py-3 text-sm font-semibold uppercase tracking-wide text-white shadow-lg shadow-blue-900/30 transition hover:from-blue-800 hover:to-blue-500"
                            >
                                Entrar al sistema
                            </button>
                        </form>

                        <p class="mt-6 text-center text-xs text-slate-400">© RankPilot</p>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        const toggle = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');

        toggle?.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            toggle.textContent = isPassword ? 'Ocultar' : 'Ver';
        });
    </script>
</x-guest-layout>
