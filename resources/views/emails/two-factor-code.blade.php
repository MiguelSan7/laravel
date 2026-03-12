<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de verificación - {{ config('app.name') }}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 480px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.1); overflow: hidden; }
        .header { background: #1a56db; padding: 32px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; }
        .body { padding: 32px; }
        .code-box { background: #f0f4ff; border: 2px dashed #1a56db; border-radius: 8px; text-align: center; padding: 24px; margin: 24px 0; }
        .code { font-size: 42px; font-weight: bold; letter-spacing: 12px; color: #1a56db; font-family: monospace; }
        .warning { background: #fff8e1; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 4px; font-size: 14px; color: #92400e; }
        .footer { background: #f9fafb; padding: 20px 32px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
    </div>
    <div class="body">
        <p>Has iniciado sesión desde un nuevo dispositivo o ubicación. Para continuar, ingresa el siguiente código de verificación:</p>

        <div class="code-box">
            <div class="code">{{ $code }}</div>
        </div>

        <div class="warning">
            <strong>Importante:</strong> Este código expira en <strong>{{ config('app.two_factor_code_expiry', 10) }} minutos</strong>.
            Si no solicitaste este código, ignora este correo y tu cuenta seguirá segura.
        </div>

        <p style="margin-top: 24px; font-size: 14px; color: #6b7280;">
            Por seguridad, nunca compartiremos este código contigo por teléfono o chat. Solo es válido para este inicio de sesión.
        </p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
    </div>
</div>
</body>
</html>
