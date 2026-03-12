<x-guest-layout>
    <!-- Estado de sesión -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6 text-center">
        <div class="mx-auto w-14 h-14 bg-indigo-100 rounded-full flex items-center justify-center mb-3">
            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <h1 class="text-xl font-semibold text-gray-800">Verificación en dos pasos</h1>
        <p class="text-sm text-gray-600 mt-1">
            Hemos enviado un código de {{ config('app.two_factor_code_length', 6) }} dígitos a tu correo.<br>
            El código expira en <strong>{{ config('app.two_factor_code_expiry', 10) }} minutos</strong>.
        </p>
    </div>

    <form method="POST" action="{{ route('two-factor.challenge') }}" id="twoFactorForm">
        @csrf

        <!-- Token reCAPTCHA v3 -->
        <input type="hidden" name="recaptcha_token" id="recaptcha_token_2fa">

        <!-- Código 2FA -->
        <div>
            <x-input-label for="code" :value="__('Código de verificación')" />
            <x-text-input
                id="code"
                class="block mt-1 w-full text-center text-2xl tracking-widest font-mono"
                type="text"
                inputmode="numeric"
                pattern="[0-9]{6}"
                name="code"
                required
                autofocus
                autocomplete="one-time-code"
                maxlength="6"
                placeholder="000000"
            />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="mt-6">
            <x-primary-button id="twoFactorBtn" class="w-full justify-center">
                {{ __('Verificar') }}
            </x-primary-button>
        </div>
    </form>

    <!-- Reenviar código -->
    <div class="mt-4 text-center">
        <form method="POST" action="{{ route('two-factor.resend') }}" class="inline">
            @csrf
            <button type="submit" class="text-sm text-indigo-600 underline hover:text-indigo-800">
                Reenviar código
            </button>
        </form>
        <span class="text-sm text-gray-400 mx-2">·</span>
        <a href="{{ route('login') }}" class="text-sm text-gray-600 underline hover:text-gray-800">
            Volver al inicio de sesión
        </a>
    </div>

    {{-- reCAPTCHA v3 --}}
    <script>
        document.getElementById('twoFactorForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const btn  = document.getElementById('twoFactorBtn');
            btn.disabled = true;

            grecaptcha.ready(function() {
                grecaptcha.execute('{{ config("recaptcha.site_key") }}', {action: 'two_factor'}).then(function(token) {
                    document.getElementById('recaptcha_token_2fa').value = token;
                    form.submit();
                }).catch(function() {
                    btn.disabled = false;
                    alert('Error al verificar reCAPTCHA. Recarga la página e inténtalo de nuevo.');
                });
            });
        });
    </script>
</x-guest-layout>
