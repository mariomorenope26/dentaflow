<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Registro público
    |--------------------------------------------------------------------------
    | Por seguridad clínica, el auto-registro público queda cerrado en cuanto
    | existe un consultorio configurado. Solo permanece abierto durante la
    | configuración inicial (0 consultorios) o si ALLOW_PUBLIC_REGISTRATION=true.
    */
    'allow_public_registration' => (bool) env('ALLOW_PUBLIC_REGISTRATION', false),

    /*
    |--------------------------------------------------------------------------
    | Cuentas rápidas de demostración en Login
    |--------------------------------------------------------------------------
    | Nunca debe habilitarse en producción.
    */
    'show_demo_accounts' => (bool) env('DENTAFLOW_DEMO_LOGIN', false),
];
