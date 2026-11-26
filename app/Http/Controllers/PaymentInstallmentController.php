<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use App\Models\TreatmentPlan;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SunatUblService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PaymentInstallmentController extends Controller
{
    public function generate(Request $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('view', $patient);

        /** @var User $user */
        $user = $request->user();
        if (! $user->canManageCash()) {
            abort(403, 'No tienes permisos para programar cuotas de financiamiento.');
        }

        $validated = $request->validate([
            'treatment_plan_id' => ['nullable', 'integer', 'exists:treatment_plans,id'],
            'total_amount' => ['required', 'numeric', 'min:1', 'max:999999'],
            'down_payment' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'installments_count' => ['required', 'integer', 'min:1', 'max:36'],
            'first_due_date' => ['required', 'date'],
            'frequency_days' => ['required', 'integer', 'in:15,30'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        if (! empty($validated['treatment_plan_id'])) {
            $plan = TreatmentPlan::query()->find((int) $validated['treatment_plan_id']);
            if ($plan !== null && $plan->patient_id !== $patient->id) {
                abort(403);
            }
        }

        $total = (float) $validated['total_amount'];
        $down = min($total, (float) ($validated['down_payment'] ?? 0));
        $count = (int) $validated['installments_count'];
        $remaining = round($total - $down, 2);

        if ($remaining <= 0) {
            return back()->with('error', 'La inicial cubre todo el importe; registra un pago único.');
        }

        $startNumber = (int) $patient->paymentInstallments()->max('installment_number') + 1;
        $firstDue = CarbonImmutable::parse((string) $validated['first_due_date']);
        $stepDays = (int) $validated['frequency_days'];

        DB::transaction(function () use ($patient, $validated, $remaining, $count, $startNumber, $firstDue, $stepDays): void {
            $baseQuota = floor(($remaining / $count) * 100) / 100;
            $accumulated = 0.0;

            for ($i = 0; $i < $count; $i++) {
                $amount = ($i === $count - 1)
                    ? round($remaining - $accumulated, 2)
                    : $baseQuota;
                $accumulated += $amount;

                $dueDate = $stepDays === 30
                    ? $firstDue->addMonthsNoOverflow($i)
                    : $firstDue->addDays($i * 15);

                PaymentInstallment::create([
                    'clinic_id' => $patient->clinic_id,
                    'patient_id' => $patient->id,
                    'treatment_plan_id' => $validated['treatment_plan_id'] ?? null,
                    'installment_number' => $startNumber + $i,
                    'due_date' => $dueDate->toDateString(),
                    'amount' => $amount,
                    'paid_amount' => 0,
                    'status' => 'pendiente',
                    'notes' => $validated['notes'] ?? null,
                ]);
            }
        });

        AuditLogger::log('create', $patient, "Generó plan de {$count} cuotas para {$patient->full_name} por S/ {$remaining}", [], $request);

        return back()->with('success', "Cronograma de {$count} cuotas generado correctamente.");
    }

    public function pay(Request $request, Patient $patient, PaymentInstallment $installment): RedirectResponse
    {
        if ($installment->patient_id !== $patient->id) {
            abort(404);
        }

        /** @var User $user */
        $user = $request->user();
        if (! $user->canManageCash()) {
            abort(403, 'No tienes permisos para cobrar cuotas.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.5', 'max:999999'],
            'payment_method' => ['required', 'string', 'in:efectivo,tarjeta,yape_plin,transferencia'],
            'document_type' => ['required', 'string', 'in:recibo,boleta,factura'],
        ]);

        $amountToPay = (float) $validated['amount'];
        $quotaTotal = (float) $installment->amount;
        $alreadyPaid = (float) $installment->paid_amount;

        DB::transaction(function () use ($patient, $installment, $validated, $amountToPay, $quotaTotal, $alreadyPaid, $user, $request): void {
            $docType = (string) $validated['document_type'];
            $receiptNumber = Payment::nextReceiptNumber($patient->clinic_id, $docType);
            $sunatMeta = SunatUblService::buildFiscalColumns(
                $patient->clinic,
                $docType,
                $receiptNumber,
                $amountToPay,
                $patient,
                null,
                null,
                null,
                null,
            );

            $payment = Payment::create([
                'clinic_id' => $patient->clinic_id,
                'patient_id' => $patient->id,
                'treatment_plan_id' => $installment->treatment_plan_id,
                'received_by' => $user->id,
                'receipt_number' => $receiptNumber,
                'document_type' => $docType,
                'payment_method' => $validated['payment_method'],
                'amount' => $amountToPay,
                'paid_at' => now(),
                'concept' => "Pago Cuota N.º {$installment->installment_number} ({$patient->history_number})",
                'status' => 'registrado',
                ...$sunatMeta,
            ]);

            $newPaid = round($alreadyPaid + $amountToPay, 2);
            $installment->update([
                'payment_id' => $payment->id,
                'paid_amount' => $newPaid,
                'paid_at' => now(),
                'status' => $newPaid + 0.01 >= $quotaTotal ? 'pagado' : 'parcial',
            ]);

            AuditLogger::log('create', $payment, "Cobró Cuota N.º {$installment->installment_number} de {$patient->full_name} ({$receiptNumber})", [], $request);
        });

        return back()->with('success', "Cobro de Cuota N.º {$installment->installment_number} registrado en Caja.");
    }

    public function destroy(Request $request, Patient $patient, PaymentInstallment $installment): RedirectResponse
    {
        if ($installment->patient_id !== $patient->id) {
            abort(404);
        }

        /** @var User $user */
        $user = $request->user();
        if (! $user->canManageCash()) {
            abort(403);
        }

        if ($installment->status === 'pagado') {
            return back()->with('error', 'No se puede eliminar una cuota ya pagada.');
        }

        $num = $installment->installment_number;
        $installment->delete();

        AuditLogger::log('delete', PaymentInstallment::class, "Eliminó cuota N.º {$num} de {$patient->full_name}", [], $request, $patient->clinic_id);

        return back()->with('success', "Cuota N.º {$num} eliminada.");
    }
}
