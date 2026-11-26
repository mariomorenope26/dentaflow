<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SystemBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Process;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SystemBackupController extends Controller
{
    public function exportJson(Request $request): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        $clinic = $user->clinic ?? Clinic::query()->firstOrFail();

        Gate::authorize('manage', $clinic);

        $snapshot = SystemBackupService::buildSnapshot();
        $filename = 'respaldo-dentaflow-'.now()->format('Ymd-His').'.json';

        AuditLogger::log('export_pdf', $clinic, "Descargó copia de seguridad JSON ({$filename})", [], $request);

        return response()->streamDownload(function () use ($snapshot): void {
            echo (string) json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    public function exportSql(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $clinic = $user->clinic ?? Clinic::query()->firstOrFail();

        Gate::authorize('manage', $clinic);

        if (config('database.default') !== 'mysql') {
            return back()->with('error', 'El volcado SQL directo solo está disponible en MySQL. Usa el respaldo JSON portable.');
        }

        /** @var array<string, mixed> $conn */
        $conn = config('database.connections.mysql');
        $host = (string) ($conn['host'] ?? '127.0.0.1');
        $port = (string) ($conn['port'] ?? '3306');
        $database = (string) ($conn['database'] ?? 'dentaflow');
        $username = (string) ($conn['username'] ?? 'root');
        $password = (string) ($conn['password'] ?? '');

        $result = Process::env(['MYSQL_PWD' => $password])
            ->timeout(30)
            ->run([
                'mysqldump',
                "--host={$host}",
                "--port={$port}",
                "--user={$username}",
                '--single-transaction',
                '--routines',
                '--triggers',
                $database,
            ]);

        if (! $result->successful()) {
            return back()->with('error', 'No se pudo ejecutar mysqldump: '.$result->errorOutput());
        }

        $filename = 'dentaflow-mysql-'.now()->format('Ymd-His').'.sql';
        AuditLogger::log('export_pdf', $clinic, "Exportó volcado SQL MySQL ({$filename})", [], $request);

        return response($result->output(), 200, [
            'Content-Type' => 'application/sql; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function restore(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $clinic = $user->clinic ?? Clinic::query()->firstOrFail();

        Gate::authorize('manage', $clinic);

        $validated = $request->validate([
            'backup_file' => ['required', 'file', 'max:20480'],
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check((string) $validated['password'], $user->password)) {
            return back()->withErrors(['password' => 'La contraseña de administrador no es correcta.']);
        }

        /** @var UploadedFile $file */
        $file = $request->file('backup_file');
        $raw = (string) file_get_contents($file->getRealPath());

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($decoded)) {
                return back()->with('error', 'El archivo de respaldo está dañado o vacío.');
            }
            SystemBackupService::restoreSnapshot($decoded);
        } catch (Throwable $e) {
            return back()->with('error', 'No se pudo restaurar el respaldo: '.$e->getMessage());
        }

        AuditLogger::log('update', $clinic, 'Restauró copia de seguridad completa del sistema', [], $request, $clinic->id);

        return back()->with('success', 'Copia de seguridad restaurada correctamente.');
    }

    public function reset(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $clinic = $user->clinic ?? Clinic::query()->firstOrFail();

        Gate::authorize('manage', $clinic);

        $validated = $request->validate([
            'confirmation' => ['required', 'string'],
            'password' => ['required', 'string'],
            'keep_catalog' => ['boolean'],
        ]);

        if (mb_strtoupper(trim((string) $validated['confirmation'])) !== 'REINICIAR') {
            return back()->withErrors(['confirmation' => 'Debes escribir exactamente REINICIAR para confirmar.']);
        }

        if (! Hash::check((string) $validated['password'], $user->password)) {
            return back()->withErrors(['password' => 'La contraseña de administrador no es correcta.']);
        }

        SystemBackupService::resetOperationalData($user, (bool) ($validated['keep_catalog'] ?? true));

        return redirect()->route('dashboard')->with('success', 'Todos los datos operativos fueron eliminados. El sistema está listo para iniciar desde cero.');
    }
}
