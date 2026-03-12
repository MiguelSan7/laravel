<x-guest-layout>
    <!-- Estado de sesión (ej: correo de verificación enviado) -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <h1 class="text-xl font-semibold text-gray-800 mb-6 text-center">Iniciar sesión</h1>

    <form method="POST" action="{{ route('login') }}" id="loginForm">
        @csrf

        <!-- Token reCAPTCHA v3 (oculto, se rellena vía JS antes del submit) -->
        <input type="hidden" name="recaptcha_token" id="recaptcha_token_login">

        <!-- Email -->
        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input
                id="email"
                class="block mt-1 w-full"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
                maxlength="255"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Contraseña -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Contraseña')" />
            <x-text-input
                id="password"
                class="block mt-1 w-full"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                maxlength="255"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Recordarme -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input
                    id="remember_me"
                    type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    name="remember"
                >
                <span class="ms-2 text-sm text-gray-600">{{ __('Recordarme') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('register') }}" class="text-sm text-gray-600 underline hover:text-gray-900">
                ¿No tienes cuenta?
            </a>

            <div class="flex items-center gap-3">
                @if (Route::has('password.request'))
                    <a class="text-sm text-gray-600 underline hover:text-gray-900" href="{{ route('password.request') }}">
                        {{ __('¿Olvidaste tu contraseña?') }}
                    </a>
                @endif

                <x-primary-button id="loginBtn">
                    {{ __('Entrar') }}
                </x-primary-button>
            </div>
        </div>
    </form>

    {{-- reCAPTCHA v3: obtiene token antes del envío del formulario --}}
    <script nonce="{{ $cspNonce }}">
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const btn  = document.getElementById('loginBtn');
            btn.disabled = true;

            grecaptcha.ready(function() {
                grecaptcha.execute('{{ config("recaptcha.site_key") }}', {action: 'login'}).then(function(token) {
                    document.getElementById('recaptcha_token_login').value = token;
                    form.submit();
                }).catch(function() {
                    btn.disabled = false;
                    alert('Error al verificar reCAPTCHA. Recarga la página e inténtalo de nuevo.');
                });
            });
        });
    </script>
</x-guest-layout>
