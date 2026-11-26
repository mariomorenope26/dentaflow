<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case AdminConsultorio = 'admin_consultorio';
    case Odontologo = 'odontologo';
    case Asistente = 'asistente';
    case Contador = 'contador';

    public function label(): string
    {
        return match ($this) {
            self::AdminConsultorio => 'Administrador del consultorio',
            self::Odontologo => 'Odontólogo',
            self::Asistente => 'Asistente / Recepción',
            self::Contador => 'Contador / Caja',
        };
    }

    public function canEditClinicalRecords(): bool
    {
        return in_array($this, [self::AdminConsultorio, self::Odontologo], true);
    }

    public function canManagePatients(): bool
    {
        return in_array($this, [self::AdminConsultorio, self::Odontologo, self::Asistente], true);
    }

    public function canManageAppointments(): bool
    {
        return in_array($this, [self::AdminConsultorio, self::Odontologo, self::Asistente], true);
    }

    public function canManageCash(): bool
    {
        return in_array($this, [self::AdminConsultorio, self::Asistente, self::Contador], true);
    }

    public function canManageClinic(): bool
    {
        return $this === self::AdminConsultorio;
    }

    public function canViewAuditLogs(): bool
    {
        return in_array($this, [self::AdminConsultorio, self::Contador], true);
    }
}
