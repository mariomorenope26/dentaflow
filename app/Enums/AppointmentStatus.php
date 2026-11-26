<?php

declare(strict_types=1);

namespace App\Enums;

enum AppointmentStatus: string
{
    case Reservada = 'reservada';
    case Confirmada = 'confirmada';
    case EnSala = 'en_sala';
    case Atendida = 'atendida';
    case Cancelada = 'cancelada';
    case NoAsistio = 'no_asistio';

    public function label(): string
    {
        return match ($this) {
            self::Reservada => 'Reservada',
            self::Confirmada => 'Confirmada',
            self::EnSala => 'En sala',
            self::Atendida => 'Atendida',
            self::Cancelada => 'Cancelada',
            self::NoAsistio => 'No asistió',
        };
    }
}
