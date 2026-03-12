<x-guest-layout>
    <h1 class="text-xl font-semibold text-gray-800 mb-6 text-center">Crear cuenta</h1>

    <form method="POST" action="{{ route('register') }}" id="registerForm">
        @csrf

        <!-- Token reCAPTCHA v3 -->
        <input type="hidden" name="recaptcha_token" id="recaptcha_token_register">

        <!-- Nombre -->
        <div>
            <x-input-label for="name" :value="__('Nombre completo')" />
            <x-text-input
                id="name"
                class="block mt-1 w-full"
                type="text"
                name="name"
                :value="old('name')"
                required
                autofocus
                autocomplete="name"
                maxlength="255"
            />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input
                id="email"
                class="block mt-1 w-full"
                type="email"
                name="email"
                :value="old('email')"
                required
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
                autocomplete="new-password"
                maxlength="255"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
            <p class="mt-1 text-xs text-gray-500">Mínimo 8 caracteres, con mayúsculas, números y símbolos.</p>
        </div>

        <!-- Confirmar Contraseña -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
            <x-text-input
                id="password_confirmation"
                class="block mt-1 w-full"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                maxlength="255"
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        {{-- Aviso 2FA --}}
        <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded text-sm text-blue-800">
            <strong>Seguridad activada:</strong> Tu cuenta tendrá verificación en dos pasos por correo electrónico.
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="text-sm text-gray-600 underline hover:text-gray-900" href="{{ route('login') }}">
                {{ __('¿Ya tienes cuenta?') }}
            </a>

            <x-primary-button id="registerBtn">
                {{ __('Crear cuenta') }}
            </x-primary-button>
        </div>
    </form>

    {{-- reCAPTCHA v3 --}}
    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const btn  = document.getElementById('registerBtn');
            btn.disabled = true;

            grecaptcha.ready(function() {
                grecaptcha.execute('{{ config("recaptcha.site_key") }}', {action: 'register'}).then(function(token) {
                    document.getElementById('recaptcha_token_register').value = token;
                    form.submit();
                }).catch(function() {
                    btn.disabled = false;
                    alert('Error al verificar reCAPTCHA. Recarga la página e inténtalo de nuevo.');
                });
            });
        });
    </script>
</x-guest-layout>
