<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Clinic;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Date::use(CarbonImmutable::class);
        Carbon::setLocale('es');
        CarbonImmutable::setLocale('es');

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        if ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );

        $this->registerAuthAuditListeners();
    }

    private function registerAuthAuditListeners(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            if (! $event->user instanceof User || $event->user->clinic_id === null) {
                return;
            }

            try {
                AuditLogger::log(
                    'login',
                    $event->user,
                    "Inicio de sesión exitoso de {$event->user->name}",
                    ['guard' => $event->guard, 'remember' => $event->remember],
                    request(),
                    $event->user->clinic_id,
                );
            } catch (Throwable) {
                // Evita interrumpir autenticación si aún no existen tablas de auditoría
            }
        });

        Event::listen(Failed::class, function (Failed $event): void {
            try {
                $clinicId = $event->user instanceof User
                    ? $event->user->clinic_id
                    : Clinic::query()->value('id');

                if ($clinicId === null) {
                    return;
                }

                $email = is_string($event->credentials['email'] ?? null)
                    ? mb_strtolower(trim($event->credentials['email']))
                    : 'desconocido';

                AuditLogger::log(
                    'login_failed',
                    User::class,
                    "Intento fallido de inicio de sesión ({$email})",
                    ['email' => $email, 'guard' => $event->guard],
                    request(),
                    (int) $clinicId,
                );
            } catch (Throwable) {
                // Ignorar en entornos de prueba parciales
            }
        });

        Event::listen(Lockout::class, function (Lockout $event): void {
            try {
                $clinicId = Clinic::query()->value('id');
                if ($clinicId === null) {
                    return;
                }

                $email = is_string($event->request->input('email'))
                    ? mb_strtolower(trim((string) $event->request->input('email')))
                    : 'desconocido';

                AuditLogger::log(
                    'login_lockout',
                    User::class,
                    "Bloqueo temporal por múltiples intentos fallidos ({$email})",
                    ['email' => $email],
                    $event->request,
                    (int) $clinicId,
                );
            } catch (Throwable) {
                // Ignorar en entornos de prueba parciales
            }
        });
    }
}
