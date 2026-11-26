<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Clinic;
use App\Models\PatientImage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SystemBackupService
{
    /**
     * Ordered tables for safe export/restore.
     *
     * @var list<string>
     */
    private const TABLES = [
        'clinics',
        'users',
        'treatment_catalogs',
        'patients',
        'medical_histories',
        'odontograms',
        'treatment_plans',
        'treatment_plan_items',
        'odontogram_findings',
        'appointments',
        'clinical_evolutions',
        'payments',
        'payment_installments',
        'cash_closings',
        'clinical_documents',
        'patient_images',
        'periodontograms',
        'orthodontic_controls',
        'lab_orders',
        'sterilization_cycles',
        'inventory_items',
        'inventory_movements',
        'audit_logs',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function buildSnapshot(): array
    {
        $tables = [];

        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $tables[$table] = DB::table($table)
                ->get()
                ->map(fn (object $row): array => (array) $row)
                ->all();
        }

        $encodedTables = (string) json_encode($tables, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $checksum = hash_hmac('sha256', $encodedTables, (string) config('app.key'));

        return [
            'app' => 'DentaFlow',
            'format_version' => '2.0',
            'exported_at' => now()->toIso8601String(),
            'checksum' => $checksum,
            'tables' => $tables,
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public static function restoreSnapshot(array $snapshot): void
    {
        if (($snapshot['app'] ?? null) !== 'DentaFlow' || ! is_array($snapshot['tables'] ?? null)) {
            throw new RuntimeException('El archivo no corresponde a un respaldo válido de DentaFlow.');
        }

        $checksum = (string) ($snapshot['checksum'] ?? '');
        /** @var array<string, array<int, array<string, mixed>>> $tables */
        $tables = $snapshot['tables'];
        $encodedTables = (string) json_encode($tables, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $expectedChecksum = hash_hmac('sha256', $encodedTables, (string) config('app.key'));

        if (! hash_equals($expectedChecksum, $checksum)) {
            throw new RuntimeException('La firma de integridad del respaldo no coincide o el archivo fue alterado.');
        }

        Schema::disableForeignKeyConstraints();

        try {
            foreach (array_reverse(self::TABLES) as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            foreach (self::TABLES as $table) {
                if (! Schema::hasTable($table) || empty($tables[$table])) {
                    continue;
                }

                foreach (array_chunk($tables[$table], 200) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public static function resetOperationalData(User $actor, bool $keepTreatmentCatalog = true): void
    {
        $imagePaths = PatientImage::withoutGlobalScopes()->pluck('file_path')->all();

        $tablesToWipe = [
            'audit_logs',
            'inventory_movements',
            'inventory_items',
            'sterilization_cycles',
            'lab_orders',
            'orthodontic_controls',
            'periodontograms',
            'patient_images',
            'clinical_documents',
            'cash_closings',
            'payment_installments',
            'payments',
            'odontogram_findings',
            'treatment_plan_items',
            'treatment_plans',
            'clinical_evolutions',
            'appointments',
            'odontograms',
            'medical_histories',
            'patients',
        ];

        if (! $keepTreatmentCatalog) {
            $tablesToWipe[] = 'treatment_catalogs';
        }

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($tablesToWipe as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        foreach ($imagePaths as $path) {
            if (is_string($path) && $path !== '') {
                Storage::disk('public')->delete($path);
            }
        }

        $clinic = $actor->clinic ?? Clinic::current();
        if (! $keepTreatmentCatalog && $clinic instanceof Clinic) {
            ClinicProvisioner::seedDefaultCatalog($clinic);
        }

        if ($clinic instanceof Clinic) {
            AuditLogger::log('delete', $clinic, 'Reinicio completo de datos clínicos y financieros del sistema', [
                'keep_catalog' => $keepTreatmentCatalog,
            ], null, $clinic->id);
        }
    }
}
