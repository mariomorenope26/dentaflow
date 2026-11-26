<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\UserManualCatalog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserManualController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $clinic = $user->clinic ?? Clinic::current();

        $chapters = array_map(function (array $chapter): array {
            $chapter['screenshots'] = array_map(function (array $shot): array {
                return [
                    'file' => $shot['file'],
                    'caption' => $shot['caption'],
                    'url' => '/images/manual/'.$shot['file'],
                ];
            }, $chapter['screenshots']);

            return $chapter;
        }, UserManualCatalog::chapters());

        return Inertia::render('manual/index', [
            'chapters' => $chapters,
            'roleMatrix' => UserManualCatalog::roleMatrix(),
            'clinicName' => $clinic !== null ? $clinic->name : 'Consultorio Odontológico',
            'manualVersion' => 'v1.0 · Edición Homologada HCE',
            'pdfUrl' => route('manual.pdf'),
        ]);
    }

    public function pdf(Request $request): HttpResponse
    {
        /** @var User $user */
        $user = $request->user();
        $clinic = $user->clinic ?? Clinic::current();
        $printSettings = $clinic ? $clinic->resolvedPrintSettings() : [
            'paper_size' => 'a4',
            'show_logo' => true,
        ];
        $logoDataUri = ! empty($printSettings['show_logo']) ? $clinic?->logoDataUri() : null;

        $chapters = array_map(function (array $chapter): array {
            $chapter['screenshots'] = array_map(function (array $shot): array {
                $fullPath = public_path('images/manual/'.$shot['file']);
                $dataUri = null;
                if (is_file($fullPath)) {
                    $raw = file_get_contents($fullPath);
                    if (is_string($raw) && $raw !== '') {
                        $dataUri = 'data:image/jpeg;base64,'.base64_encode($raw);
                    }
                }

                return [
                    'file' => $shot['file'],
                    'caption' => $shot['caption'],
                    'data_uri' => $dataUri,
                ];
            }, $chapter['screenshots']);

            return $chapter;
        }, UserManualCatalog::chapters());

        AuditLogger::log('export_pdf', Clinic::class, "Descargó Manual Oficial del Sistema en PDF ({$user->name})", [], $request, $clinic?->id);

        $pdf = Pdf::loadView('pdf.user-manual', [
            'clinic' => $clinic,
            'logoDataUri' => $logoDataUri,
            'chapters' => $chapters,
            'roleMatrix' => UserManualCatalog::roleMatrix(),
            'generatedBy' => $user->name,
            'generatedAt' => now(),
            'manualVersion' => 'v1.0 · Homologación HCE Internacional',
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('Manual-Usuario-DentaFlow.pdf');
    }
}
