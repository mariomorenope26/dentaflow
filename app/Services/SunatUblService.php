<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Payment;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use DOMDocument;

class SunatUblService
{
    /**
     * Compute Peruvian IGV (18%) fiscal columns, series/correlative and UBL hash.
     *
     * @return array{
     *     sunat_series: string,
     *     sunat_correlative: string,
     *     customer_doc_type: string,
     *     customer_doc_number: string,
     *     customer_name: string,
     *     customer_address: string|null,
     *     tax_subtotal: float,
     *     tax_igv: float,
     *     sunat_status: string,
     *     sunat_hash: string
     * }
     */
    public static function buildFiscalColumns(
        ?Clinic $clinic,
        string $documentType,
        string $receiptNumber,
        float $totalAmount,
        Patient $patient,
        ?string $customerDocType,
        ?string $customerDocNumber,
        ?string $customerName,
        ?string $customerAddress,
    ): array {
        $series = match ($documentType) {
            'factura' => 'F001',
            'boleta' => 'B001',
            default => 'R001',
        };

        $correlative = '000001';
        if (preg_match('/(\d+)$/', $receiptNumber, $m) === 1) {
            $correlative = str_pad($m[1], 6, '0', STR_PAD_LEFT);
        }

        $docTypeCode = $customerDocType ?: match (strtoupper($patient->document_type)) {
            'DNI' => '1',
            'CE' => '4',
            'PASAPORTE' => '7',
            default => '0',
        };

        $docNum = trim((string) ($customerDocNumber ?: ($patient->document_number ?? '00000000')));
        $custName = trim((string) ($customerName ?: $patient->full_name));
        $custAddr = trim((string) ($customerAddress ?: ($patient->address ?? '')));

        if ($documentType === 'recibo') {
            $subtotal = round($totalAmount, 2);
            $igv = 0.0;
            $status = 'emitido_interno';
        } else {
            $subtotal = round($totalAmount / 1.18, 2);
            $igv = round($totalAmount - $subtotal, 2);
            $status = 'ubl_generado';
        }

        $hashSeed = implode('|', [
            $clinic !== null ? ($clinic->ruc ?? '20000000001') : '20000000001',
            $documentType,
            $series,
            $correlative,
            number_format($totalAmount, 2, '.', ''),
            $docNum,
            now()->format('Y-m-d'),
        ]);

        return [
            'sunat_series' => $series,
            'sunat_correlative' => $correlative,
            'customer_doc_type' => $docTypeCode,
            'customer_doc_number' => $docNum,
            'customer_name' => $custName,
            'customer_address' => $custAddr !== '' ? $custAddr : null,
            'tax_subtotal' => $subtotal,
            'tax_igv' => $igv,
            'sunat_status' => $status,
            'sunat_hash' => substr(hash('sha256', $hashSeed), 0, 32),
        ];
    }

    public static function buildQrPayload(Payment $payment, ?Clinic $clinic): string
    {
        $ruc = $clinic?->ruc ?: '20601234567';
        $tipoComp = match ($payment->document_type) {
            'factura' => '01',
            'boleta' => '03',
            default => '00',
        };
        $serie = $payment->sunat_series ?: 'B001';
        $correlativo = $payment->sunat_correlative ?: '000001';
        $igv = number_format((float) $payment->tax_igv, 2, '.', '');
        $total = number_format((float) $payment->amount, 2, '.', '');
        $fecha = $payment->paid_at->format('Y-m-d');
        $cliTipo = $payment->customer_doc_type ?: '1';
        $cliNum = $payment->customer_doc_number ?: ($payment->patient->document_number ?? '00000000');
        $hash = $payment->sunat_hash ?: '0000';

        return "{$ruc}|{$tipoComp}|{$serie}|{$correlativo}|{$igv}|{$total}|{$fecha}|{$cliTipo}|{$cliNum}|{$hash}|";
    }

    public static function qrDataUri(Payment $payment, ?Clinic $clinic): string
    {
        $payload = self::buildQrPayload($payment, $clinic);
        $renderer = new ImageRenderer(
            new RendererStyle(120, 1),
            new SvgImageBackEnd,
        );
        $writer = new Writer($renderer);
        $svg = $writer->writeString($payload);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    public static function generateUblXml(Payment $payment, ?Clinic $clinic): string
    {
        $ruc = htmlspecialchars($clinic?->ruc ?: '20601234567', ENT_XML1, 'UTF-8');
        $clinicName = htmlspecialchars($clinic?->name ?: 'Consultorio Odontológico', ENT_XML1, 'UTF-8');
        $clinicAddr = htmlspecialchars($clinic?->address ?: 'Lima, Perú', ENT_XML1, 'UTF-8');

        $invoiceType = $payment->document_type === 'factura' ? '01' : '03';
        $serie = $payment->sunat_series ?: ($invoiceType === '01' ? 'F001' : 'B001');
        $correlativo = $payment->sunat_correlative ?: str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
        $docId = "{$serie}-{$correlativo}";
        $issueDate = $payment->paid_at->format('Y-m-d');
        $issueTime = $payment->paid_at->format('H:i:s');

        $custDocType = htmlspecialchars($payment->customer_doc_type ?: ($invoiceType === '01' ? '6' : '1'), ENT_XML1, 'UTF-8');
        $custDocNum = htmlspecialchars($payment->customer_doc_number ?: ($payment->patient->document_number ?? '00000000'), ENT_XML1, 'UTF-8');
        $custName = htmlspecialchars($payment->customer_name ?: ($payment->patient->full_name ?? 'Cliente'), ENT_XML1, 'UTF-8');

        $total = (float) $payment->amount;
        $subtotal = (float) $payment->tax_subtotal > 0 ? (float) $payment->tax_subtotal : round($total / 1.18, 2);
        $igv = (float) $payment->tax_igv > 0 ? (float) $payment->tax_igv : round($total - $subtotal, 2);
        $concept = htmlspecialchars($payment->concept, ENT_XML1, 'UTF-8');

        $subtotalStr = number_format($subtotal, 2, '.', '');
        $igvStr = number_format($igv, 2, '.', '');
        $totalStr = number_format($total, 2, '.', '');

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
         xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
         xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
    <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
    <cbc:CustomizationID>2.0</cbc:CustomizationID>
    <cbc:ID>{$docId}</cbc:ID>
    <cbc:IssueDate>{$issueDate}</cbc:IssueDate>
    <cbc:IssueTime>{$issueTime}</cbc:IssueTime>
    <cbc:InvoiceTypeCode listID="0101">{$invoiceType}</cbc:InvoiceTypeCode>
    <cbc:DocumentCurrencyCode>PEN</cbc:DocumentCurrencyCode>
    <cac:AccountingSupplierParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="6">{$ruc}</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName>{$clinicName}</cbc:RegistrationName>
                <cac:RegistrationAddress>
                    <cac:AddressLine>
                        <cbc:Line>{$clinicAddr}</cbc:Line>
                    </cac:AddressLine>
                </cac:RegistrationAddress>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingSupplierParty>
    <cac:AccountingCustomerParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="{$custDocType}">{$custDocNum}</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName>{$custName}</cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingCustomerParty>
    <cac:TaxTotal>
        <cbc:TaxAmount currencyID="PEN">{$igvStr}</cbc:TaxAmount>
        <cac:TaxSubtotal>
            <cbc:TaxableAmount currencyID="PEN">{$subtotalStr}</cbc:TaxableAmount>
            <cbc:TaxAmount currencyID="PEN">{$igvStr}</cbc:TaxAmount>
            <cac:TaxCategory>
                <cac:TaxScheme>
                    <cbc:ID>1000</cbc:ID>
                    <cbc:Name>IGV</cbc:Name>
                    <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                </cac:TaxScheme>
            </cac:TaxCategory>
        </cac:TaxSubtotal>
    </cac:TaxTotal>
    <cac:LegalMonetaryTotal>
        <cbc:LineExtensionAmount currencyID="PEN">{$subtotalStr}</cbc:LineExtensionAmount>
        <cbc:TaxInclusiveAmount currencyID="PEN">{$totalStr}</cbc:TaxInclusiveAmount>
        <cbc:PayableAmount currencyID="PEN">{$totalStr}</cbc:PayableAmount>
    </cac:LegalMonetaryTotal>
    <cac:InvoiceLine>
        <cbc:ID>1</cbc:ID>
        <cbc:InvoicedQuantity unitCode="ZZ">1</cbc:InvoicedQuantity>
        <cbc:LineExtensionAmount currencyID="PEN">{$subtotalStr}</cbc:LineExtensionAmount>
        <cac:PricingReference>
            <cac:AlternativeConditionPrice>
                <cbc:PriceAmount currencyID="PEN">{$totalStr}</cbc:PriceAmount>
                <cbc:PriceTypeCode>01</cbc:PriceTypeCode>
            </cac:AlternativeConditionPrice>
        </cac:PricingReference>
        <cac:TaxTotal>
            <cbc:TaxAmount currencyID="PEN">{$igvStr}</cbc:TaxAmount>
            <cac:TaxSubtotal>
                <cbc:TaxableAmount currencyID="PEN">{$subtotalStr}</cbc:TaxableAmount>
                <cbc:TaxAmount currencyID="PEN">{$igvStr}</cbc:TaxAmount>
                <cac:TaxCategory>
                    <cbc:Percent>18</cbc:Percent>
                    <cbc:TaxExemptionReasonCode>10</cbc:TaxExemptionReasonCode>
                    <cac:TaxScheme>
                        <cbc:ID>1000</cbc:ID>
                        <cbc:Name>IGV</cbc:Name>
                        <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                </cac:TaxCategory>
            </cac:TaxSubtotal>
        </cac:TaxTotal>
        <cac:Item>
            <cbc:Description>{$concept}</cbc:Description>
        </cac:Item>
        <cac:Price>
            <cbc:PriceAmount currencyID="PEN">{$subtotalStr}</cbc:PriceAmount>
        </cac:Price>
    </cac:InvoiceLine>
</Invoice>
XML;

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);

        return (string) $dom->saveXML();
    }
}
