<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        .code { font-size: 32px; font-weight: bold; text-align: center; letter-spacing: 8px; color: #4a90d9; background: #f0f7ff; padding: 15px; border-radius: 6px; margin: 20px 0; }
        .footer { font-size: 12px; color: #999; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Hola {{ $name }},</h2>
        <p>Recibimos una solicitud para restablecer tu contraseña. Usa el siguiente código:</p>
        
        <div class="code">{{ $code }}</div>
        
        <p>Este código expira en <strong>10 minutos</strong>.</p>
        <p>Si no solicitaste este cambio, ignora este correo.</p>
        
        <div class="footer">
            <p>— ASPT App</p>
        </div>
    </div>
</body>
</html>
