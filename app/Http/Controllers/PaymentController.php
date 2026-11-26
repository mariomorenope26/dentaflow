<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CashClosing;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use App\Models\TreatmentPlan;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SunatUblService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->canManageCash()) {
            abort(403, 'No tienes permisos para acceder a Caja.');
        }

        $dateStr = (string) $request->query('date', CarbonImmutable::today()->toDateString());
        $date = CarbonImmutable::parse($dateStr);

        $payments = Payment::query()
            ->with(['patient:id,first_name,last_name,history_number', 'receiver:id,name'])
            ->whereBetween('paid_at', [$date->startOfDay(), $date->endOfDay()])
            ->orderByDesc('paid_at')
            ->get();

        $activePayments = $payments->where('status', 'registrado');

        $totals = [
            'efectivo' => (float) $activePayments->where('payment_method', 'efectivo')->sum('amount'),
            'tarjeta' => (float) $activePayments->where('payment_method', 'tarjeta')->sum('amount'),
            'yape_plin' => (float) $activePayments->where('payment_method', 'yape_plin')->sum('amount'),
            'transferencia' => (float) $activePayments->where('payment_method', 'transferencia')->sum('amount'),
            'total' => (float) $activePayments->sum('amount'),
        ];

        $closing = CashClosing::query()
            ->with('closer:id,name')
            ->whereDate('closing_date', $date->toDateString())
            ->first();

        $patients = Patient::query()
            ->where('is_active', true)
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'history_number']);

        $recentClosings = CashClosing::query()
            ->with('closer:id,name')
            ->orderByDesc('closing_date')
            ->limit(10)
            ->get();

        $debtors = $this->computeDebtors();

        $overdueInstallments = PaymentInstallment::query()
            ->with(['patient:id,first_name,last_name,history_number,phone', 'treatmentPlan:id,title'])
            ->whereIn('status', ['pendiente', 'parcial'])
            ->whereDate('due_date', '<=', CarbonImmutable::today()->addDays(7)->toDateString())
            ->orderBy('due_date')
            ->limit(25)
            ->get()
            ->map(function (PaymentInstallment $inst): array {
                $balance = round(max(0.0, (float) $inst->amount - (float) $inst->paid_amount), 2);
                $isOverdue = $inst->due_date->lt(CarbonImmutable::today());

                return [
                    'id' => $inst->id,
                    'patient_id' => $inst->patient_id,
                    'patient_name' => $inst->patient->full_name ?? 'Paciente',
                    'patient_history' => $inst->patient->history_number ?? '',
                    'patient_phone' => $inst->patient?->phone,
                    'plan_title' => $inst->treatmentPlan?->title,
                    'installment_number' => $inst->installment_number,
                    'due_date' => $inst->due_date->toDateString(),
                    'balance' => $balance,
                    'is_overdue' => $isOverdue,
                ];
            });

        return Inertia::render('cash/index', [
            'date' => $date->toDateString(),
            'payments' => $payments,
            'totals' => $totals,
            'closing' => $closing,
            'recentClosings' => $recentClosings,
            'patients' => $patients,
            'debtors' => $debtors,
            'overdueInstallments' => $overdueInstallments,
        ]);
    }

    public function store(Request $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('view', $patient);

        /** @var User $user */
        $user = $request->user();
        if (! $user->canManageCash()) {
            abort(403, 'No tienes permisos para registrar cobros.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.5', 'max:999999'],
            'payment_method' => ['required', 'string', 'in:efectivo,tarjeta,yape_plin,transferencia'],
            'document_type' => ['required', 'string', 'in:recibo,boleta,factura'],
            'customer_doc_type' => ['nullable', 'string', 'in:1,4,6,7,0'],
            'customer_doc_number' => ['nullable', 'string', 'max:20'],
            'customer_name' => ['nullable', 'string', 'max:180'],
            'customer_address' => ['nullable', 'string', 'max:255'],
            'concept' => ['required', 'string', 'max:255'],
            'treatment_plan_id' => ['nullable', 'integer', 'exists:treatment_plans,id'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! empty($validated['treatment_plan_id'])) {
            $plan = TreatmentPlan::query()->find((int) $validated['treatment_plan_id']);
            if ($plan !== null && $plan->patient_id !== $patient->id) {
                abort(403);
            }
        }

        if ($validated['document_type'] === 'factura' && empty($validated['customer_doc_number'])) {
            return back()->with('error', 'Para emitir Factura electrónica debes ingresar el RUC (11 dígitos) del cliente.');
        }

        $clinicId = $patient->clinic_id;
        $receiptNumber = Payment::nextReceiptNumber($clinicId, (string) $validated['document_type']);
        $sunatMeta = SunatUblService::buildFiscalColumns(
            $patient->clinic,
            (string) $validated['document_type'],
            $receiptNumber,
            (float) $validated['amount'],
            $patient,
            $validated['customer_doc_type'] ?? null,
            $validated['customer_doc_number'] ?? null,
            $validated['customer_name'] ?? null,
            $validated['customer_address'] ?? null,
        );

        $payment = Payment::create([
            'clinic_id' => $clinicId,
            'patient_id' => $patient->id,
            'treatment_plan_id' => $validated['treatment_plan_id'] ?? null,
            'received_by' => $user->id,
            'receipt_number' => $receiptNumber,
            'document_type' => $validated['document_type'],
            'payment_method' => $validated['payment_method'],
            'amount' => $validated['amount'],
            'paid_at' => $validated['paid_at'] ?? now(),
            'concept' => $validated['concept'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'registrado',
            ...$sunatMeta,
        ]);

        AuditLogger::log(
            'create',
            $payment,
            "Registró pago {$receiptNumber} por {$validated['amount']} para {$patient->full_name}",
            [],
            $request,
        );

        return back()->with('success', "Cobro {$receiptNumber} registrado correctamente.");
    }

    public function void(Request $request, Payment $payment): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->canManageCash() || $payment->clinic_id !== $user->clinic_id) {
            abort(403);
        }

        $payment->update(['status' => 'anulado']);

        AuditLogger::log('update', $payment, "Anuló comprobante {$payment->receipt_number}", [], $request);

        return back()->with('success', "Comprobante {$payment->receipt_number} anulado.");
    }

    public function closeDay(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->canManageCash()) {
            abort(403);
        }

        $validated = $request->validate([
            'closing_date' => ['required', 'date'],
            'opening_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $date = CarbonImmutable::parse((string) $validated['closing_date']);
        $clinicId = $user->clinic_id ?? (int) Clinic::query()->value('id');

        if (CashClosing::query()->whereDate('closing_date', $date->toDateString())->exists()) {
            return back()->with('error', 'Ya existe un cierre de caja registrado para esa fecha.');
        }

        $dayPayments = Payment::query()
            ->where('status', 'registrado')
            ->whereBetween('paid_at', [$date->startOfDay(), $date->endOfDay()])
            ->get();

        $cash = (float) $dayPayments->where('payment_method', 'efectivo')->sum('amount');
        $card = (float) $dayPayments->where('payment_method', 'tarjeta')->sum('amount');
        $digital = (float) $dayPayments->where('payment_method', 'yape_plin')->sum('amount');
        $transfer = (float) $dayPayments->where('payment_method', 'transferencia')->sum('amount');

        $closing = CashClosing::create([
            'clinic_id' => $clinicId,
            'closed_by' => $user->id,
            'closing_date' => $date->toDateString(),
            'opening_amount' => $validated['opening_amount'] ?? 0,
            'cash_total' => $cash,
            'card_total' => $card,
            'digital_total' => $digital,
            'transfer_total' => $transfer,
            'grand_total' => $cash + $card + $digital + $transfer,
            'notes' => $validated['notes'] ?? null,
            'closed_at' => now(),
        ]);

        AuditLogger::log('create', $closing, "Realizó cierre de caja del {$date->format('d/m/Y')} (Total: {$closing->grand_total})", [], $request);

        return back()->with('success', "Cierre de caja del {$date->format('d/m/Y')} registrado.");
    }

    public function receiptPdf(Request $request, Payment $payment): HttpResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ($payment->clinic_id !== $user->clinic_id) {
            abort(403);
        }

        $payment->load(['patient', 'clinic', 'receiver:id,name']);
        $clinic = $payment->clinic;
        $printSettings = $clinic ? $clinic->resolvedPrintSettings() : $this->defaultPrintSettings();
        $logoDataUri = $printSettings['show_logo'] ? $clinic?->logoDataUri() : null;
        $sunatQrDataUri = $payment->document_type !== 'recibo'
            ? SunatUblService::qrDataUri($payment, $clinic)
            : null;

        AuditLogger::log('export_pdf', $payment, "Generó comprobante PDF {$payment->receipt_number}", [], $request);

        $pdf = Pdf::loadView('pdf.receipt', [
            'payment' => $payment,
            'patient' => $payment->patient,
            'clinic' => $clinic,
            'printSettings' => $printSettings,
            'logoDataUri' => $logoDataUri,
            'sunatQrDataUri' => $sunatQrDataUri,
        ])->setPaper('a5', 'landscape');

        return $pdf->stream("Comprobante-{$payment->receipt_number}.pdf");
    }

    public function ublXml(Request $request, Payment $payment): HttpResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ($payment->clinic_id !== $user->clinic_id) {
            abort(403);
        }

        if ($payment->document_type === 'recibo') {
            abort(404, 'Solo las Boletas y Facturas generan XML UBL 2.1.');
        }

        $payment->load(['patient', 'clinic']);
        $xml = SunatUblService::generateUblXml($payment, $payment->clinic);
        $ruc = $payment->clinic?->ruc ?: '20601234567';
        $type = $payment->document_type === 'factura' ? '01' : '03';
        $serie = $payment->sunat_series ?: ($type === '01' ? 'F001' : 'B001');
        $correlativo = $payment->sunat_correlative ?: str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
        $filename = "{$ruc}-{$type}-{$serie}-{$correlativo}.xml";

        AuditLogger::log('export_pdf', $payment, "Descargó XML UBL 2.1 SUNAT {$filename}", [], $request);

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function thermalReceipt(Request $request, Payment $payment): HttpResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ($payment->clinic_id !== $user->clinic_id) {
            abort(403);
        }

        $payment->load(['patient', 'clinic', 'receiver:id,name']);
        $clinic = $payment->clinic;
        $printSettings = $clinic ? $clinic->resolvedPrintSettings() : $this->defaultPrintSettings();
        $logoDataUri = $printSettings['show_logo'] ? $clinic?->logoDataUri() : null;
        $sunatQrDataUri = $payment->document_type !== 'recibo'
            ? SunatUblService::qrDataUri($payment, $clinic)
            : null;

        $widthMm = $this->resolveThermalWidth($request, $printSettings);
        $widthPt = $widthMm === '58mm' ? 164.41 : 226.77;
        $heightPt = 420.0
            + (mb_strlen($payment->concept) > 40 ? 28.0 : 0.0)
            + ($sunatQrDataUri !== null ? 105.0 : 0.0)
            + ($logoDataUri !== null ? 45.0 : 0.0);

        AuditLogger::log('export_pdf', $payment, "Imprimió ticket térmico ({$widthMm}) {$payment->receipt_number}", [], $request);

        $pdf = Pdf::loadView('pdf.thermal-receipt', [
            'payment' => $payment,
            'patient' => $payment->patient,
            'clinic' => $clinic,
            'printSettings' => $printSettings,
            'logoDataUri' => $logoDataUri,
            'sunatQrDataUri' => $sunatQrDataUri,
            'widthMm' => $widthMm,
            'printedAt' => now(),
        ])->setPaper([0, 0, $widthPt, $heightPt], 'portrait');

        return $pdf->stream("Ticket-{$widthMm}-{$payment->receipt_number}.pdf");
    }

    public function thermalClosing(Request $request, CashClosing $closing): HttpResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->canManageCash() || $closing->clinic_id !== $user->clinic_id) {
            abort(403);
        }

        $closing->load(['closer:id,name', 'clinic']);
        $clinic = $closing->clinic;
        $date = CarbonImmutable::parse($closing->closing_date->toDateString());

        return $this->streamThermalClosingPdf($request, $clinic, $date, $closing);
    }

    public function thermalDailySummary(Request $request): HttpResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->canManageCash()) {
            abort(403, 'No tienes permisos para imprimir reportes de Caja.');
        }

        $dateStr = (string) $request->query('date', CarbonImmutable::today()->toDateString());
        $date = CarbonImmutable::parse($dateStr);
        $clinic = Clinic::current();

        $closing = CashClosing::query()
            ->with('closer:id,name')
            ->whereDate('closing_date', $date->toDateString())
            ->first();

        return $this->streamThermalClosingPdf($request, $clinic, $date, $closing);
    }

    private function streamThermalClosingPdf(
        Request $request,
        ?Clinic $clinic,
        CarbonImmutable $date,
        ?CashClosing $closing,
    ): HttpResponse {
        $printSettings = $clinic ? $clinic->resolvedPrintSettings() : $this->defaultPrintSettings();
        $logoDataUri = $printSettings['show_logo'] ? $clinic?->logoDataUri() : null;
        $widthMm = $this->resolveThermalWidth($request, $printSettings);
        $widthPt = $widthMm === '58mm' ? 164.41 : 226.77;

        $payments = Payment::query()
            ->with(['patient:id,first_name,last_name,history_number', 'receiver:id,name'])
            ->whereBetween('paid_at', [$date->startOfDay(), $date->endOfDay()])
            ->orderBy('paid_at')
            ->get();

        $active = $payments->where('status', 'registrado');
        $voided = $payments->where('status', 'anulado');
        $grandTotal = (float) $active->sum('amount');

        $methodDefs = [
            'efectivo' => 'Efectivo',
            'yape_plin' => 'Yape / Plin',
            'tarjeta' => 'Tarjeta POS',
            'transferencia' => 'Transferencia',
        ];

        $methodBreakdown = [];
        foreach ($methodDefs as $key => $label) {
            $subset = $active->where('payment_method', $key);
            $amount = (float) $subset->sum('amount');
            $methodBreakdown[] = [
                'key' => $key,
                'label' => $label,
                'count' => $subset->count(),
                'amount' => $amount,
                'percentage' => $grandTotal > 0 ? round(($amount / $grandTotal) * 100, 1) : 0.0,
            ];
        }

        $docDefs = [
            'boleta' => 'Boletas de venta',
            'factura' => 'Facturas',
            'recibo' => 'Recibos internos',
        ];

        $docBreakdown = [];
        foreach ($docDefs as $key => $label) {
            $subset = $active->where('document_type', $key);
            $docBreakdown[] = [
                'key' => $key,
                'label' => $label,
                'count' => $subset->count(),
                'amount' => (float) $subset->sum('amount'),
            ];
        }

        $openingAmount = $closing !== null
            ? (float) $closing->opening_amount
            : max(0.0, (float) $request->query('opening_amount', 0));
        $cashTotal = (float) $active->where('payment_method', 'efectivo')->sum('amount');

        $heightPt = 530.0
            + ($payments->count() * 24.0)
            + ($logoDataUri !== null ? 45.0 : 0.0);

        AuditLogger::log(
            'export_pdf',
            $closing ?? Payment::class,
            "Imprimió reporte térmico de caja ({$widthMm}) del {$date->format('d/m/Y')}",
            [],
            $request,
            $clinic?->id,
        );

        /** @var User|null $printedUser */
        $printedUser = $request->user();

        $pdf = Pdf::loadView('pdf.thermal-cash-closing', [
            'clinic' => $clinic,
            'date' => $date,
            'closing' => $closing,
            'payments' => $payments,
            'activeCount' => $active->count(),
            'voidedCount' => $voided->count(),
            'voidedTotal' => (float) $voided->sum('amount'),
            'openingAmount' => $openingAmount,
            'cashInDrawer' => $openingAmount + $cashTotal,
            'grandTotal' => $grandTotal,
            'expectedTotal' => $openingAmount + $grandTotal,
            'methodBreakdown' => $methodBreakdown,
            'docBreakdown' => $docBreakdown,
            'printSettings' => $printSettings,
            'logoDataUri' => $logoDataUri,
            'widthMm' => $widthMm,
            'printedAt' => now(),
            'printedBy' => $printedUser !== null ? $printedUser->name : 'Sistema',
        ])->setPaper([0, 0, $widthPt, $heightPt], 'portrait');

        return $pdf->stream("CierreCaja-{$widthMm}-{$date->format('Ymd')}.pdf");
    }

    /**
     * @param  array{paper_size: string, thermal_width: string, show_logo: bool, show_cop: bool, header_text: string, footer_text: string}  $printSettings
     */
    private function resolveThermalWidth(Request $request, array $printSettings): string
    {
        $sizeQuery = strtolower((string) $request->query('size', ''));
        if (in_array($sizeQuery, ['58', '58mm'], true)) {
            return '58mm';
        }
        if (in_array($sizeQuery, ['80', '80mm'], true)) {
            return '80mm';
        }

        return $printSettings['thermal_width'];
    }

    /**
     * @return array{paper_size: string, thermal_width: string, show_logo: bool, show_cop: bool, header_text: string, footer_text: string}
     */
    private function defaultPrintSettings(): array
    {
        return [
            'paper_size' => 'a4',
            'thermal_width' => '80mm',
            'show_logo' => true,
            'show_cop' => true,
            'header_text' => '',
            'footer_text' => 'Gracias por confiar su salud bucal en nosotros.',
        ];
    }

    /**
     * @return array<int, array{patient_id: int, history_number: string, full_name: string, budgeted: float, paid: float, balance: float}>
     */
    private function computeDebtors(): array
    {
        $patients = Patient::query()
            ->with(['treatmentPlans.items', 'payments'])
            ->where('is_active', true)
            ->get();

        $debtors = [];
        foreach ($patients as $patient) {
            $budgeted = $patient->treatmentPlans
                ->where('status', '!=', 'cancelado')
                ->sum(function (TreatmentPlan $plan): float {
                    $sub = $plan->items->sum(
                        fn ($item): float => ((float) $item->unit_price * $item->quantity) - (float) $item->discount,
                    );

                    return max(0.0, $sub - (float) $plan->discount_amount);
                });

            $paid = (float) $patient->payments->where('status', 'registrado')->sum('amount');
            $balance = round($budgeted - $paid, 2);

            if ($balance > 0.01) {
                $debtors[] = [
                    'patient_id' => $patient->id,
                    'history_number' => $patient->history_number,
                    'full_name' => $patient->full_name,
                    'budgeted' => round($budgeted, 2),
                    'paid' => round($paid, 2),
                    'balance' => $balance,
                ];
            }
        }

        usort($debtors, fn (array $a, array $b): int => $b['balance'] <=> $a['balance']);

        return array_slice($debtors, 0, 15);
    }
}
