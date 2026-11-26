<?php

declare(strict_types=1);
use App\Enums\UserRole;
use App\Models\Clinic;
use App\Models\User;
use App\Services\ClinicProvisioner;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Instalador Web Oficial de DentaFlow (Edición Consultorio Único)
 * Desarrollado por Grupo JMSPERU 2026 :: Contacto +51 959199368
 */
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

$basePath = is_dir(__DIR__.'/app') && is_dir(__DIR__.'/vendor')
    ? __DIR__
    : dirname(__DIR__);

if (! is_dir($basePath.'/app') || ! is_file($basePath.'/vendor/autoload.php')) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="es"><meta charset="utf-8"><body style="font-family:sans-serif;padding:40px;background:#F6FAFB;color:#1F2D3A"><h2>Estructura incompleta</h2><p>No se encontró la carpeta <code>app/</code> ni <code>vendor/autoload.php</code>. Asegúrate de descomprimir todo el paquete ZIP completo.</p></body></html>';
    exit;
}

$lockFile = $basePath.'/storage/installed.lock';
$scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    ? 'https'
    : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$detectedUrl = $scheme.'://'.$host;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['installer_csrf']) || ! is_string($_SESSION['installer_csrf'])) {
    $_SESSION['installer_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['installer_csrf'];

// Acción final segura: eliminar instalador(es) tras completar la instalación
if (
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
    && ($_POST['action'] ?? '') === 'cleanup_installer'
) {
    $postedToken = (string) ($_POST['_token'] ?? '');
    if (! hash_equals($csrfToken, $postedToken)) {
        http_response_code(419);
        exit('Token de seguridad inválido.');
    }

    if (file_exists($lockFile)) {
        @unlink($basePath.'/install.php');
        @unlink($basePath.'/public/install.php');
        $loginTarget = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/').'/login';
        if (str_ends_with($loginTarget, '/public/login') && is_dir($basePath.'/public')) {
            $loginTarget = '/login';
        }
        header('Location: '.$loginTarget);
        exit;
    }
}

if (file_exists($lockFile)) {
    http_response_code(403);
    echo <<<'HTML'
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instalador bloqueado · DentaFlow</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #F6FAFB; color: #1F2D3A; display: flex; min-height: 100vh; align-items: center; justify-content: center; margin: 0; padding: 20px; }
        .card { max-width: 520px; background: #fff; border: 1px solid #DCE6EA; border-radius: 20px; padding: 32px; text-align: center; box-shadow: 0 12px 30px rgba(6,46,57,.07); }
        h1 { font-size: 22px; color: #062E39; margin: 0 0 10px; }
        p { font-size: 14px; line-height: 1.6; color: #5F6F7C; margin-bottom: 20px; }
        code { background: #EEF6F8; padding: 2px 6px; border-radius: 6px; color: #0A6577; }
        a.btn { display: inline-block; background: #0E8FA3; color: #fff; text-decoration: none; font-weight: 600; padding: 11px 20px; border-radius: 12px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>DentaFlow ya se encuentra instalado</h1>
        <p>El asistente de instalación está bloqueado por seguridad (<code>storage/installed.lock</code> existe). Elimina <code>install.php</code> de tu servidor antes de operar en producción.</p>
        <a href="/login" class="btn">Ir al inicio de sesión</a>
    </div>
</body>
</html>
HTML;
    exit;
}

/**
 * Verificar requisitos mínimos de PHP, extensiones y permisos.
 *
 * @return array{ok: bool, php_ok: bool, extensions: array<string, bool>, paths: array<string, bool>}
 */
function checkRequirements(string $basePath): array
{
    $phpOk = PHP_VERSION_ID >= 80300;
    $requiredExts = ['pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo', 'gd', 'curl', 'zip'];
    $extensions = [];
    foreach ($requiredExts as $ext) {
        $extensions[$ext] = extension_loaded($ext);
    }

    $hasDbDriver = extension_loaded('pdo_mysql') || extension_loaded('pdo_sqlite') || extension_loaded('pdo_pgsql');
    $extensions['pdo_mysql / pdo_sqlite'] = $hasDbDriver;

    $paths = [
        'Directorio raíz (.env)' => is_writable($basePath),
        'storage/' => is_writable($basePath.'/storage'),
        'storage/app/' => is_dir($basePath.'/storage/app') && is_writable($basePath.'/storage/app'),
        'storage/framework/' => is_dir($basePath.'/storage/framework') && is_writable($basePath.'/storage/framework'),
        'storage/logs/' => is_dir($basePath.'/storage/logs') && is_writable($basePath.'/storage/logs'),
        'bootstrap/cache/' => is_writable($basePath.'/bootstrap/cache'),
        'database/' => is_writable($basePath.'/database'),
        'Assets compilados (public/build/manifest.json)' => is_file($basePath.'/public/build/manifest.json'),
    ];

    $allExt = ! in_array(false, $extensions, true);
    $allPaths = ! in_array(false, $paths, true);

    return [
        'ok' => $phpOk && $allExt && $allPaths,
        'php_ok' => $phpOk,
        'extensions' => $extensions,
        'paths' => $paths,
    ];
}

function escapeEnvValue(string $value): string
{
    if ($value === '') {
        return '';
    }
    if (preg_match('/^[A-Za-z0-9._\/:-]+$/', $value) === 1) {
        return $value;
    }

    return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
}

function buildDatabaseTestError(array $input, string $basePath): ?string
{
    $driver = $input['db_connection'] ?? 'mysql';

    try {
        if ($driver === 'sqlite') {
            if (! extension_loaded('pdo_sqlite')) {
                return 'La extensión pdo_sqlite no está habilitada en PHP.';
            }
            $dbFile = $basePath.'/database/database.sqlite';
            if (! is_file($dbFile)) {
                if (@touch($dbFile) === false) {
                    return 'No se pudo crear database/database.sqlite. Verifica permisos de escritura en database/.';
                }
            }
            new PDO('sqlite:'.$dbFile);

            return null;
        }

        if (! extension_loaded('pdo_mysql')) {
            return 'La extensión pdo_mysql no está habilitada en PHP.';
        }

        $host = trim((string) ($input['db_host'] ?? '127.0.0.1'));
        $port = trim((string) ($input['db_port'] ?? '3306'));
        $dbName = trim((string) ($input['db_database'] ?? ''));
        $dbUser = trim((string) ($input['db_username'] ?? ''));
        $dbPass = (string) ($input['db_password'] ?? '');

        if ($dbName === '' || $dbUser === '') {
            return 'Ingresa el nombre de la base de datos y el usuario de MySQL/MariaDB.';
        }

        $dsnNoDb = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port);
        $pdo = new PDO($dsnNoDb, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);

        if (! empty($input['create_database'])) {
            $safeDb = str_replace('`', '', $dbName);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbName);
        new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);

        return null;
    } catch (Throwable $e) {
        return 'Error de conexión: '.$e->getMessage();
    }
}

$reqs = checkRequirements($basePath);
$step = (int) ($_GET['step'] ?? ($_POST['step'] ?? 1));
$errors = [];
$dbSuccessMessage = null;
$installedSummary = null;

$form = [
    'app_url' => trim((string) ($_POST['app_url'] ?? $detectedUrl)),
    'app_timezone' => trim((string) ($_POST['app_timezone'] ?? 'America/Lima')),
    'db_connection' => (string) ($_POST['db_connection'] ?? (extension_loaded('pdo_mysql') ? 'mysql' : 'sqlite')),
    'db_host' => trim((string) ($_POST['db_host'] ?? '127.0.0.1')),
    'db_port' => trim((string) ($_POST['db_port'] ?? '3306')),
    'db_database' => trim((string) ($_POST['db_database'] ?? 'dentaflow')),
    'db_username' => trim((string) ($_POST['db_username'] ?? 'root')),
    'db_password' => (string) ($_POST['db_password'] ?? ''),
    'create_database' => isset($_POST['create_database']),
    'clinic_name' => trim((string) ($_POST['clinic_name'] ?? 'Consultorio Odontológico')),
    'clinic_ruc' => trim((string) ($_POST['clinic_ruc'] ?? '')),
    'clinic_phone' => trim((string) ($_POST['clinic_phone'] ?? '')),
    'clinic_email' => trim((string) ($_POST['clinic_email'] ?? '')),
    'clinic_address' => trim((string) ($_POST['clinic_address'] ?? '')),
    'currency_symbol' => trim((string) ($_POST['currency_symbol'] ?? 'S/')),
    'odontogram_notation' => (string) ($_POST['odontogram_notation'] ?? 'FDI'),
    'admin_name' => trim((string) ($_POST['admin_name'] ?? '')),
    'admin_license' => trim((string) ($_POST['admin_license'] ?? '')),
    'admin_email' => mb_strtolower(trim((string) ($_POST['admin_email'] ?? ''))),
    'admin_password' => (string) ($_POST['admin_password'] ?? ''),
    'admin_password_confirmation' => (string) ($_POST['admin_password_confirmation'] ?? ''),
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $postedToken = (string) ($_POST['_token'] ?? '');
    if (! hash_equals($csrfToken, $postedToken)) {
        $errors[] = 'La sesión del instalador expiró. Recarga la página e intenta nuevamente.';
    } else {
        $action = (string) ($_POST['action'] ?? 'install');

        if ($action === 'test_db') {
            $step = 2;
            $dbError = buildDatabaseTestError($form, $basePath);
            if ($dbError !== null) {
                $errors[] = $dbError;
            } else {
                $dbSuccessMessage = 'Conexión a la base de datos verificada correctamente.';
            }
        } elseif ($action === 'install') {
            $step = 2;
            if (! $reqs['ok']) {
                $errors[] = 'El servidor aún no cumple todos los requisitos mínimos.';
            }

            if (filter_var($form['app_url'], FILTER_VALIDATE_URL) === false) {
                $errors[] = 'Ingresa una URL válida del sistema (por ejemplo: https://clinica.tudominio.com).';
            }

            $dbError = buildDatabaseTestError($form, $basePath);
            if ($dbError !== null) {
                $errors[] = $dbError;
            }

            if ($form['clinic_name'] === '') {
                $errors[] = 'El nombre del consultorio es obligatorio.';
            }
            if ($form['admin_name'] === '') {
                $errors[] = 'El nombre completo del administrador es obligatorio.';
            }
            if (filter_var($form['admin_email'], FILTER_VALIDATE_EMAIL) === false) {
                $errors[] = 'El correo del administrador no es válido.';
            }

            $pwd = $form['admin_password'];
            if (
                strlen($pwd) < 12
                || preg_match('/[A-Z]/', $pwd) !== 1
                || preg_match('/[a-z]/', $pwd) !== 1
                || preg_match('/[0-9]/', $pwd) !== 1
                || preg_match('/[^A-Za-z0-9]/', $pwd) !== 1
            ) {
                $errors[] = 'La contraseña del administrador debe tener mínimo 12 caracteres con mayúscula, minúscula, número y símbolo.';
            }
            if ($pwd !== $form['admin_password_confirmation']) {
                $errors[] = 'Las contraseñas del administrador no coinciden.';
            }

            if ($errors === []) {
                try {
                    // Asegurar permisos de carpetas runtime
                    foreach ([
                        $basePath.'/storage/app/public',
                        $basePath.'/storage/app/private',
                        $basePath.'/storage/framework/cache/data',
                        $basePath.'/storage/framework/sessions',
                        $basePath.'/storage/framework/views',
                        $basePath.'/storage/logs',
                    ] as $dir) {
                        if (! is_dir($dir)) {
                            @mkdir($dir, 0775, true);
                        }
                    }

                    // Limpiar cachés previas de bootstrap para que lea el nuevo .env
                    foreach (glob($basePath.'/bootstrap/cache/*.php') ?: [] as $cacheFile) {
                        @unlink($cacheFile);
                    }

                    $appKey = 'base64:'.base64_encode(random_bytes(32));
                    $appUrl = rtrim($form['app_url'], '/');
                    $isHttps = str_starts_with($appUrl, 'https://');

                    $envLines = [
                        'APP_NAME=DentaFlow',
                        'APP_ENV=production',
                        'APP_KEY='.escapeEnvValue($appKey),
                        'APP_DEBUG=false',
                        'APP_URL='.escapeEnvValue($appUrl),
                        'APP_TIMEZONE='.escapeEnvValue($form['app_timezone']),
                        '',
                        'APP_LOCALE=es',
                        'APP_FALLBACK_LOCALE=es',
                        'APP_FAKER_LOCALE=es_PE',
                        '',
                        'ALLOW_PUBLIC_REGISTRATION=false',
                        'DENTAFLOW_DEMO_LOGIN=false',
                        '',
                        'APP_MAINTENANCE_DRIVER=file',
                        'BCRYPT_ROUNDS=12',
                        '',
                        'LOG_CHANNEL=stack',
                        'LOG_STACK=single',
                        'LOG_LEVEL=warning',
                        '',
                        'DB_CONNECTION='.escapeEnvValue($form['db_connection']),
                    ];

                    if ($form['db_connection'] === 'sqlite') {
                        $envLines[] = 'DB_DATABASE='.escapeEnvValue($basePath.'/database/database.sqlite');
                    } else {
                        $envLines[] = 'DB_HOST='.escapeEnvValue($form['db_host']);
                        $envLines[] = 'DB_PORT='.escapeEnvValue($form['db_port']);
                        $envLines[] = 'DB_DATABASE='.escapeEnvValue($form['db_database']);
                        $envLines[] = 'DB_USERNAME='.escapeEnvValue($form['db_username']);
                        $envLines[] = 'DB_PASSWORD='.escapeEnvValue($form['db_password']);
                    }

                    $envLines = array_merge($envLines, [
                        '',
                        'SESSION_DRIVER=database',
                        'SESSION_LIFETIME=120',
                        'SESSION_ENCRYPT=true',
                        'SESSION_PATH=/',
                        'SESSION_SECURE_COOKIE='.($isHttps ? 'true' : 'false'),
                        '',
                        'BROADCAST_CONNECTION=log',
                        'FILESYSTEM_DISK=local',
                        'QUEUE_CONNECTION=database',
                        'CACHE_STORE=database',
                        '',
                        'MAIL_MAILER=log',
                        'MAIL_FROM_ADDRESS='.escapeEnvValue($form['clinic_email'] !== '' ? $form['clinic_email'] : 'no-reply@consultorio.local'),
                        'MAIL_FROM_NAME="${APP_NAME}"',
                        'VITE_APP_NAME="${APP_NAME}"',
                    ]);

                    if (@file_put_contents($basePath.'/.env', implode(PHP_EOL, $envLines).PHP_EOL) === false) {
                        throw new RuntimeException('No se pudo escribir el archivo .env en la raíz del proyecto.');
                    }

                    require_once $basePath.'/vendor/autoload.php';
                    /** @var Application $app */
                    $app = require $basePath.'/bootstrap/app.php';
                    /** @var Kernel $kernel */
                    $kernel = $app->make(Kernel::class);
                    $kernel->bootstrap();

                    // Garantizar esquema completo
                    $exitMigrate = $kernel->call('migrate', ['--force' => true]);
                    if ($exitMigrate !== 0) {
                        throw new RuntimeException('Error ejecutando migraciones: '.$kernel->output());
                    }

                    DB::transaction(function () use ($form): void {
                        $clinic = Clinic::query()->first();
                        if ($clinic === null) {
                            $clinic = ClinicProvisioner::createClinicWithDefaults([
                                'name' => $form['clinic_name'],
                                'ruc' => $form['clinic_ruc'] !== '' ? $form['clinic_ruc'] : null,
                                'phone' => $form['clinic_phone'] !== '' ? $form['clinic_phone'] : null,
                                'email' => $form['clinic_email'] !== '' ? $form['clinic_email'] : $form['admin_email'],
                                'address' => $form['clinic_address'] !== '' ? $form['clinic_address'] : null,
                                'currency_symbol' => $form['currency_symbol'] !== '' ? $form['currency_symbol'] : 'S/',
                                'settings' => [
                                    'odontogram_notation' => $form['odontogram_notation'] === 'UNIVERSAL' ? 'UNIVERSAL' : 'FDI',
                                    'auto_lock_minutes' => 15,
                                ],
                            ]);
                        }

                        $existingAdmin = User::query()->where('email', $form['admin_email'])->first();
                        if ($existingAdmin === null) {
                            $admin = User::query()->create([
                                'clinic_id' => $clinic->id,
                                'name' => $form['admin_name'],
                                'email' => $form['admin_email'],
                                'role' => UserRole::AdminConsultorio,
                                'license_number' => $form['admin_license'] !== '' ? $form['admin_license'] : null,
                                'commission_rate' => 40.00,
                                'password' => Hash::make($form['admin_password']),
                                'is_active' => true,
                            ]);
                            $admin->forceFill(['email_verified_at' => now()])->save();
                        }
                    });

                    // Crear enlace simbólico public/storage si no existe
                    $publicStorage = $basePath.'/public/storage';
                    $targetStorage = $basePath.'/storage/app/public';
                    if (! file_exists($publicStorage)) {
                        try {
                            $kernel->call('storage:link', ['--force' => true]);
                        } catch (Throwable) {
                            @symlink($targetStorage, $publicStorage);
                        }
                    }

                    // Optimizar configuración y rutas en producción
                    try {
                        $kernel->call('config:cache');
                        $kernel->call('route:cache');
                        $kernel->call('view:cache');
                    } catch (Throwable) {
                        // Algunos hostings restringidos pueden diferir caché; no bloquea el funcionamiento
                    }

                    @file_put_contents(
                        $lockFile,
                        json_encode([
                            'installed_at' => date('c'),
                            'app_url' => $appUrl,
                            'admin_email' => $form['admin_email'],
                            'clinic_name' => $form['clinic_name'],
                        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    );

                    $step = 3;
                    $installedSummary = [
                        'app_url' => $appUrl,
                        'clinic_name' => $form['clinic_name'],
                        'admin_email' => $form['admin_email'],
                        'db_connection' => $form['db_connection'],
                    ];
                } catch (Throwable $e) {
                    $errors[] = 'No se pudo completar la instalación: '.$e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instalador DentaFlow · Consultorio Odontológico</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #F6FAFB;
            color: #1F2D3A;
            line-height: 1.5;
        }
        .header {
            background: #062E39;
            color: #fff;
            padding: 22px 24px;
            border-bottom: 3px solid #0E8FA3;
        }
        .container { max-width: 920px; margin: 0 auto; }
        .brand { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .brand h1 { margin: 0; font-size: 20px; }
        .brand span { font-size: 12px; color: #99E9DF; background: rgba(255,255,255,.1); padding: 4px 12px; border-radius: 999px; border: 1px solid rgba(255,255,255,.18); }
        .main { max-width: 920px; margin: 28px auto; padding: 0 20px 40px; }
        .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 22px; }
        .step-pill { background: #fff; border: 1px solid #DCE6EA; padding: 12px 14px; border-radius: 12px; font-size: 13px; font-weight: 600; color: #5F6F7C; }
        .step-pill.active { background: #0E8FA3; border-color: #0E8FA3; color: #fff; }
        .step-pill.done { background: #E3F6F1; border-color: #2E9E6B; color: #166534; }
        .card { background: #fff; border: 1px solid #DCE6EA; border-radius: 18px; padding: 26px; box-shadow: 0 10px 28px rgba(6,46,57,.05); margin-bottom: 20px; }
        h2 { margin: 0 0 8px; font-size: 18px; color: #062E39; }
        p.sub { margin: 0 0 18px; font-size: 13px; color: #5F6F7C; }
        .grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        @media (max-width: 720px) { .grid-2, .steps { grid-template-columns: 1fr; } }
        label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 5px; color: #1F2D3A; }
        input, select {
            width: 100%; height: 40px; padding: 8px 12px; border: 1px solid #CBD8DF; border-radius: 10px;
            font-size: 14px; color: #1F2D3A; background: #fff;
        }
        input:focus, select:focus { outline: 2px solid #0E8FA3; border-color: #0E8FA3; }
        .check-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        @media (max-width: 680px) { .check-grid { grid-template-columns: 1fr; } }
        .check-item { display: flex; align-items: center; justify-content: space-between; padding: 9px 12px; border-radius: 10px; border: 1px solid #E5EEF2; font-size: 13px; background: #FAFDFE; }
        .ok { color: #166534; font-weight: 700; }
        .fail { color: #B91C1C; font-weight: 700; }
        .alert-error { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 14px 16px; border-radius: 12px; margin-bottom: 18px; font-size: 13px; }
        .alert-ok { background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; padding: 14px 16px; border-radius: 12px; margin-bottom: 18px; font-size: 13px; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; margin-top: 22px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            height: 42px; padding: 0 20px; border-radius: 11px; border: 0; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none;
        }
        .btn-primary { background: #0E8FA3; color: #fff; }
        .btn-primary:hover { background: #0A6577; }
        .btn-outline { background: #fff; border: 1px solid #CBD8DF; color: #1F2D3A; }
        .btn-danger { background: #DC2626; color: #fff; }
        .section-title { font-size: 14px; font-weight: 700; color: #0A6577; margin: 22px 0 12px; border-bottom: 1px solid #E5EEF2; padding-bottom: 6px; }
        .checkbox-inline { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; margin-top: 8px; }
        .checkbox-inline input { width: 16px; height: 16px; }
        .footer { text-align: center; font-size: 12px; color: #5F6F7C; padding: 20px; border-top: 1px solid #DCE6EA; background: #fff; }
        .footer a { color: #0E8FA3; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
    <header class="header">
        <div class="container brand">
            <div>
                <h1>DentaFlow · Asistente de Instalación</h1>
                <small style="color:#CFECEE">Historia Clínica Electrónica Odontológica (HL7 FHIR R4 · ISO 13606)</small>
            </div>
            <span>PHP <?php echo htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    </header>

    <main class="main">
        <div class="steps">
            <div class="step-pill <?php echo $step === 1 ? 'active' : 'done'; ?>">1. Requisitos del servidor</div>
            <div class="step-pill <?php echo $step === 2 ? 'active' : ($step > 2 ? 'done' : ''); ?>">2. Base de datos y Administrador</div>
            <div class="step-pill <?php echo $step === 3 ? 'active' : ''; ?>">3. Sistema listo</div>
        </div>

        <?php if ($errors !== []) { ?>
            <div class="alert-error">
                <strong>Revisa lo siguiente antes de continuar:</strong>
                <ul style="margin:6px 0 0 18px;">
                    <?php foreach ($errors as $err) { ?>
                        <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>

        <?php if ($dbSuccessMessage !== null) { ?>
            <div class="alert-ok"><?php echo htmlspecialchars($dbSuccessMessage, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>

        <?php if ($step === 1) { ?>
            <div class="card">
                <h2>Verificación del entorno del servidor</h2>
                <p class="sub">Comprobamos que tu hosting o VPS cumpla las extensiones de PHP y permisos requeridos.</p>

                <div class="section-title">Versión de PHP y extensiones</div>
                <div class="check-grid">
                    <div class="check-item">
                        <span>PHP &gt;= 8.3 (Actual: <?php echo htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8'); ?>)</span>
                        <span class="<?php echo $reqs['php_ok'] ? 'ok' : 'fail'; ?>"><?php echo $reqs['php_ok'] ? 'OK' : 'Requerido'; ?></span>
                    </div>
                    <?php foreach ($reqs['extensions'] as $name => $passed) { ?>
                        <div class="check-item">
                            <span>Extensión <code><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></code></span>
                            <span class="<?php echo $passed ? 'ok' : 'fail'; ?>"><?php echo $passed ? 'Habilitada' : 'Faltante'; ?></span>
                        </div>
                    <?php } ?>
                </div>

                <div class="section-title">Permisos de escritura y archivos compilados</div>
                <div class="check-grid">
                    <?php foreach ($reqs['paths'] as $label => $passed) { ?>
                        <div class="check-item">
                            <span><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="<?php echo $passed ? 'ok' : 'fail'; ?>"><?php echo $passed ? 'Correcto' : 'Sin permisos'; ?></span>
                        </div>
                    <?php } ?>
                </div>

                <div class="actions">
                    <?php if ($reqs['ok']) { ?>
                        <a href="?step=2" class="btn btn-primary">Continuar con la configuración &rarr;</a>
                    <?php } else { ?>
                        <a href="?step=1" class="btn btn-outline">Volver a comprobar</a>
                    <?php } ?>
                </div>
            </div>
        <?php } elseif ($step === 2) { ?>
            <form method="post" action="?step=2" class="card">
                <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <h2>Configuración de producción, base de datos y administrador</h2>
                <p class="sub">Configura la conexión y crea el consultorio único con tu cuenta Administrador protegida.</p>

                <div class="section-title">1. Entorno Web</div>
                <div class="grid-2">
                    <div>
                        <label>URL pública del consultorio *</label>
                        <input type="url" name="app_url" required value="<?php echo htmlspecialchars($form['app_url'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div>
                        <label>Zona horaria *</label>
                        <select name="app_timezone">
                            <?php foreach (['America/Lima', 'America/Bogota', 'America/Santiago', 'America/Mexico_City', 'America/Argentina/Buenos_Aires', 'America/La_Paz', 'America/Guayaquil', 'Europe/Madrid'] as $tz) { ?>
                                <option value="<?php echo $tz; ?>" <?php echo $form['app_timezone'] === $tz ? 'selected' : ''; ?>><?php echo $tz; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="section-title">2. Base de datos</div>
                <div class="grid-2">
                    <div>
                        <label>Motor de base de datos *</label>
                        <select name="db_connection" id="db_connection" onchange="toggleDbFields()">
                            <option value="mysql" <?php echo $form['db_connection'] === 'mysql' ? 'selected' : ''; ?>>MySQL / MariaDB (Recomendado en Hosting/VPS)</option>
                            <option value="sqlite" <?php echo $form['db_connection'] === 'sqlite' ? 'selected' : ''; ?>>SQLite (Archivo local en database/database.sqlite)</option>
                        </select>
                    </div>
                    <div id="mysql-create-wrap">
                        <label>Opciones</label>
                        <label class="checkbox-inline">
                            <input type="checkbox" name="create_database" value="1" <?php echo $form['create_database'] ? 'checked' : ''; ?>>
                            Crear base de datos automáticamente si no existe
                        </label>
                    </div>
                </div>

                <div id="mysql-fields" class="grid-2" style="margin-top:12px;">
                    <div>
                        <label>Servidor (Host)</label>
                        <input type="text" name="db_host" value="<?php echo htmlspecialchars($form['db_host'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div>
                        <label>Puerto</label>
                        <input type="text" name="db_port" value="<?php echo htmlspecialchars($form['db_port'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div>
                        <label>Nombre de la base de datos</label>
                        <input type="text" name="db_database" value="<?php echo htmlspecialchars($form['db_database'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div>
                        <label>Usuario de base de datos</label>
                        <input type="text" name="db_username" value="<?php echo htmlspecialchars($form['db_username'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label>Contraseña de base de datos</label>
                        <input type="password" name="db_password" value="<?php echo htmlspecialchars($form['db_password'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>

                <div class="section-title">3. Datos del Consultorio Único</div>
                <div class="grid-2">
                    <div>
                        <label>Nombre del consultorio o clínica *</label>
                        <input type="text" name="clinic_name" required value="<?php echo htmlspecialchars($form['clinic_name'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div>
                        <label>RUC / Identificación tributaria</label>
                        <input type="text" name="clinic_ruc" maxlength="20" value="<?php echo htmlspecialchars($form['clinic_ruc'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div>
                        <label>Teléfono / WhatsApp</label>
                        <input type="text" name="clinic_phone" value="<?php echo htmlspecialchars($form['clinic_phone'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div>
                        <label>Correo del consultorio</label>
                        <input type="email" name="clinic_email" value="<?php echo htmlspecialchars($form['clinic_email'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div>
                        <label>Dirección</label>
                        <input type="text" name="clinic_address" value="<?php echo htmlspecialchars($form['clinic_address'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="grid-2">
                        <div>
                            <label>Símbolo de moneda</label>
                            <input type="text" name="currency_symbol" maxlength="10" value="<?php echo htmlspecialchars($form['currency_symbol'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div>
                            <label>Odontograma</label>
                            <select name="odontogram_notation">
                                <option value="FDI" <?php echo $form['odontogram_notation'] === 'FDI' ? 'selected' : ''; ?>>FDI (ISO 3950)</option>
                                <option value="UNIVERSAL" <?php echo $form['odontogram_notation'] === 'UNIVERSAL' ? 'selected' : ''; ?>>Universal</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="section-title">4. Cuenta Administrador del Consultorio (Rol Protegido)</div>
                <div class="grid-2">
                    <div>
                        <label>Nombre completo del administrador *</label>
                        <input type="text" name="admin_name" required placeholder="Dr. Mateo Salazar" value="<?php echo htmlspecialchars($form['admin_name'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div>
                        <label>N.º Colegiatura COP (opcional)</label>
                        <input type="text" name="admin_license" placeholder="COP-12345" value="<?php echo htmlspecialchars($form['admin_license'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label>Correo electrónico de acceso *</label>
                        <input type="email" name="admin_email" required placeholder="admin@tuconsultorio.com" value="<?php echo htmlspecialchars($form['admin_email'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div>
                        <label>Contraseña segura (mín. 12 caracteres: mayús, minús, número, símbolo) *</label>
                        <input type="password" name="admin_password" required minlength="12">
                    </div>
                    <div>
                        <label>Confirmar contraseña *</label>
                        <input type="password" name="admin_password_confirmation" required minlength="12">
                    </div>
                </div>

                <div class="actions">
                    <a href="?step=1" class="btn btn-outline">&larr; Volver</a>
                    <button type="submit" name="action" value="test_db" class="btn btn-outline" formnovalidate>Probar conexión BD</button>
                    <button type="submit" name="action" value="install" class="btn btn-primary">Instalar DentaFlow ahora</button>
                </div>
            </form>
            <script>
                function toggleDbFields() {
                    const isSqlite = document.getElementById('db_connection').value === 'sqlite';
                    document.getElementById('mysql-fields').style.display = isSqlite ? 'none' : 'grid';
                    document.getElementById('mysql-create-wrap').style.display = isSqlite ? 'none' : 'block';
                }
                toggleDbFields();
            </script>
        <?php } elseif ($step === 3 && $installedSummary !== null) { ?>
            <div class="card">
                <div class="alert-ok">
                    <strong>¡Instalación completada correctamente!</strong> El archivo <code>.env</code>, las tablas de la base de datos, el consultorio y tu usuario Administrador protegido ya están listos.
                </div>

                <div class="check-grid">
                    <div class="check-item"><span>Consultorio</span><strong><?php echo htmlspecialchars($installedSummary['clinic_name'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                    <div class="check-item"><span>Administrador</span><strong><?php echo htmlspecialchars($installedSummary['admin_email'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                    <div class="check-item"><span>Base de datos</span><strong><?php echo htmlspecialchars(strtoupper($installedSummary['db_connection']), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                    <div class="check-item"><span>Bloqueo instalador</span><strong>storage/installed.lock</strong></div>
                </div>

                <div class="alert-error" style="margin-top:18px;">
                    <strong>Acción obligatoria de seguridad:</strong> Elimina el archivo <code>install.php</code> ahora mismo antes de compartir la URL con tu equipo.
                </div>

                <form method="post" class="actions">
                    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" name="action" value="cleanup_installer" class="btn btn-danger">
                        Eliminar install.php automáticamente e iniciar sesión &rarr;
                    </button>
                </form>
            </div>
        <?php } ?>
    </main>

    <footer class="footer">
        Desarrollado por <strong>Grupo JMSPERU 2026</strong> ::
        <a href="https://wa.me/51959199368?text=Hola%20Grupo%20JMSPERU%2C%20necesito%20ayuda%20con%20la%20instalaci%C3%B3n%20de%20DentaFlow." target="_blank" rel="noopener noreferrer">Contacto 959199368</a>
    </footer>
</body>
</html>
