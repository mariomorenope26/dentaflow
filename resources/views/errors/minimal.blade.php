<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · @yield('title') — DentaFlow</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #F6FAFB;
            color: #1F2D3A;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 24px;
        }
        main {
            max-width: 520px;
            width: 100%;
            margin: auto;
            background: #ffffff;
            border: 1px solid #DCE6EA;
            border-radius: 20px;
            padding: 36px 28px;
            text-align: center;
            box-shadow: 0 12px 30px rgba(6, 46, 57, 0.06);
        }
        .badge {
            display: inline-block;
            background: #E6F5F7;
            color: #0A6577;
            font-weight: 700;
            font-size: 13px;
            padding: 6px 14px;
            border-radius: 999px;
            margin-bottom: 14px;
        }
        h1 { font-size: 24px; font-weight: 700; margin-bottom: 10px; color: #062E39; }
        p { font-size: 14px; line-height: 1.6; color: #5F6F7C; margin-bottom: 24px; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        a.btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 42px;
            padding: 0 18px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }
        a.btn-primary { background: #0E8FA3; color: #ffffff; }
        a.btn-secondary { background: #EEF6F8; color: #0A6577; border: 1px solid #DCE6EA; }
        footer {
            text-align: center;
            font-size: 12px;
            color: #5F6F7C;
            padding-top: 20px;
        }
        footer a { color: #0E8FA3; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
    <main>
        <div class="badge">Error @yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            <a href="{{ url('/dashboard') }}" class="btn btn-primary">Ir al panel clínico</a>
            <a href="{{ url('/') }}" class="btn btn-secondary">Volver al inicio</a>
        </div>
    </main>
    <footer>
        Desarrollado por <strong>Grupo JMSPERU 2026</strong> ::
        <a href="https://wa.me/51959199368?text=Hola%20Grupo%20JMSPERU%2C%20necesito%20soporte%20con%20DentaFlow." target="_blank" rel="noopener noreferrer">Contacto 959199368</a>
    </footer>
</body>
</html>
