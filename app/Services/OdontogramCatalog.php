<?php

declare(strict_types=1);

namespace App\Services;

class OdontogramCatalog
{
    /**
     * @return array<int, array{
     *     code: string,
     *     label: string,
     *     short: string,
     *     category: string,
     *     default_status: string,
     *     surface_mode: string,
     *     symbol: string,
     *     color_todo: string,
     *     color_done: string,
     * }>
     */
    public static function conditions(): array
    {
        return [
            [
                'code' => 'caries',
                'label' => 'Caries dental',
                'short' => 'CAR',
                'category' => 'patologia',
                'default_status' => 'por_realizar',
                'surface_mode' => 'surface',
                'symbol' => 'fill',
                'color_todo' => '#D64545',
                'color_done' => '#2F7FD1',
            ],
            [
                'code' => 'obturacion',
                'label' => 'Obturación / Resina',
                'short' => 'OBT',
                'category' => 'restauracion',
                'default_status' => 'realizado',
                'surface_mode' => 'surface',
                'symbol' => 'fill',
                'color_todo' => '#D64545',
                'color_done' => '#2F7FD1',
            ],
            [
                'code' => 'sellante',
                'label' => 'Sellante de fosas y fisuras',
                'short' => 'SEL',
                'category' => 'preventivo',
                'default_status' => 'realizado',
                'surface_mode' => 'surface',
                'symbol' => 'S',
                'color_todo' => '#D64545',
                'color_done' => '#2F7FD1',
            ],
            [
                'code' => 'corona',
                'label' => 'Corona',
                'short' => 'COR',
                'category' => 'protesis',
                'default_status' => 'realizado',
                'surface_mode' => 'tooth',
                'symbol' => 'ring',
                'color_todo' => '#D64545',
                'color_done' => '#2F7FD1',
            ],
            [
                'code' => 'endodoncia',
                'label' => 'Endodoncia',
                'short' => 'END',
                'category' => 'endodoncia',
                'default_status' => 'realizado',
                'surface_mode' => 'tooth',
                'symbol' => 'canal',
                'color_todo' => '#D64545',
                'color_done' => '#2F7FD1',
            ],
            [
                'code' => 'implante',
                'label' => 'Implante dental',
                'short' => 'IMP',
                'category' => 'implantologia',
                'default_status' => 'realizado',
                'surface_mode' => 'tooth',
                'symbol' => 'implant',
                'color_todo' => '#D64545',
                'color_done' => '#2F7FD1',
            ],
            [
                'code' => 'protesis_fija',
                'label' => 'Prótesis fija / Puente',
                'short' => 'PF',
                'category' => 'protesis',
                'default_status' => 'por_realizar',
                'surface_mode' => 'multi',
                'symbol' => 'bridge',
                'color_todo' => '#D64545',
                'color_done' => '#2F7FD1',
            ],
            [
                'code' => 'protesis_removible',
                'label' => 'Prótesis removible',
                'short' => 'PR',
                'category' => 'protesis',
                'default_status' => 'por_realizar',
                'surface_mode' => 'multi',
                'symbol' => 'double_line',
                'color_todo' => '#D64545',
                'color_done' => '#2F7FD1',
            ],
            [
                'code' => 'ausente',
                'label' => 'Pieza ausente',
                'short' => 'AUS',
                'category' => 'ausente',
                'default_status' => 'ausente',
                'surface_mode' => 'tooth',
                'symbol' => 'cross_muted',
                'color_todo' => '#5F6F7C',
                'color_done' => '#5F6F7C',
            ],
            [
                'code' => 'extraccion_indicada',
                'label' => 'Extracción indicada',
                'short' => 'EXT',
                'category' => 'cirugia',
                'default_status' => 'por_realizar',
                'surface_mode' => 'tooth',
                'symbol' => 'cross_red',
                'color_todo' => '#D64545',
                'color_done' => '#5F6F7C',
            ],
            [
                'code' => 'fractura',
                'label' => 'Fractura coronaria',
                'short' => 'FRA',
                'category' => 'patologia',
                'default_status' => 'por_realizar',
                'surface_mode' => 'surface',
                'symbol' => 'zigzag',
                'color_todo' => '#D64545',
                'color_done' => '#2F7FD1',
            ],
            [
                'code' => 'desgaste',
                'label' => 'Desgaste / Atrición',
                'short' => 'DES',
                'category' => 'patologia',
                'default_status' => 'por_realizar',
                'surface_mode' => 'surface',
                'symbol' => 'W',
                'color_todo' => '#D64545',
                'color_done' => '#2F7FD1',
            ],
            [
                'code' => 'movilidad',
                'label' => 'Movilidad dentaria',
                'short' => 'MOV',
                'category' => 'periodoncia',
                'default_status' => 'por_realizar',
                'surface_mode' => 'tooth',
                'symbol' => 'M',
                'color_todo' => '#D64545',
                'color_done' => '#2F7FD1',
            ],
            [
                'code' => 'retenido',
                'label' => 'Diente retenido',
                'short' => 'RET',
                'category' => 'cirugia',
                'default_status' => 'por_realizar',
                'surface_mode' => 'tooth',
                'symbol' => 'R',
                'color_todo' => '#D64545',
                'color_done' => '#2F7FD1',
            ],
            [
                'code' => 'impactado',
                'label' => 'Diente impactado',
                'short' => 'IMPAC',
                'category' => 'cirugia',
                'default_status' => 'por_realizar',
                'surface_mode' => 'tooth',
                'symbol' => 'I',
                'color_todo' => '#D64545',
                'color_done' => '#2F7FD1',
            ],
            [
                'code' => 'diastema',
                'label' => 'Diastema',
                'short' => 'DIA',
                'category' => 'ortodoncia',
                'default_status' => 'por_realizar',
                'surface_mode' => 'tooth',
                'symbol' => 'brackets_gap',
                'color_todo' => '#D64545',
                'color_done' => '#2F7FD1',
            ],
            [
                'code' => 'giroversion',
                'label' => 'Giroversión',
                'short' => 'GIR',
                'category' => 'ortodoncia',
                'default_status' => 'por_realizar',
                'surface_mode' => 'tooth',
                'symbol' => 'rotate',
                'color_todo' => '#D64545',
                'color_done' => '#2F7FD1',
            ],
            [
                'code' => 'migracion',
                'label' => 'Migración dentaria',
                'short' => 'MIG',
                'category' => 'ortodoncia',
                'default_status' => 'por_realizar',
                'surface_mode' => 'tooth',
                'symbol' => 'arrow',
                'color_todo' => '#D64545',
                'color_done' => '#2F7FD1',
            ],
            [
                'code' => 'ortodoncia',
                'label' => 'Ortodoncia (Brackets)',
                'short' => 'ORT',
                'category' => 'ortodoncia',
                'default_status' => 'realizado',
                'surface_mode' => 'multi',
                'symbol' => 'bracket',
                'color_todo' => '#D64545',
                'color_done' => '#2F7FD1',
            ],
            [
                'code' => 'lesion_periapical',
                'label' => 'Lesión periapical',
                'short' => 'LP',
                'category' => 'endodoncia',
                'default_status' => 'por_realizar',
                'surface_mode' => 'tooth',
                'symbol' => 'apical_circle',
                'color_todo' => '#D64545',
                'color_done' => '#2F7FD1',
            ],
            [
                'code' => 'recesion',
                'label' => 'Recesión gingival',
                'short' => 'REC',
                'category' => 'periodoncia',
                'default_status' => 'por_realizar',
                'surface_mode' => 'tooth',
                'symbol' => 'recession',
                'color_todo' => '#D64545',
                'color_done' => '#2F7FD1',
            ],
            [
                'code' => 'sano',
                'label' => 'Pieza sana / Sin hallazgos',
                'short' => 'OK',
                'category' => 'sano',
                'default_status' => 'sano',
                'surface_mode' => 'tooth',
                'symbol' => 'check',
                'color_todo' => '#2E9E6B',
                'color_done' => '#2E9E6B',
            ],
        ];
    }

    /**
     * @return array<int, array{code: string, label: string}>
     */
    public static function cie10Codes(): array
    {
        return [
            ['code' => 'K02.0', 'label' => 'K02.0 · Caries limitada al esmalte'],
            ['code' => 'K02.1', 'label' => 'K02.1 · Caries de la dentina'],
            ['code' => 'K02.2', 'label' => 'K02.2 · Caries del cemento'],
            ['code' => 'K04.0', 'label' => 'K04.0 · Pulpitis'],
            ['code' => 'K04.1', 'label' => 'K04.1 · Necrosis de la pulpa'],
            ['code' => 'K04.5', 'label' => 'K04.5 · Periodontitis apical crónica'],
            ['code' => 'K04.7', 'label' => 'K04.7 · Absceso periapical sin fístula'],
            ['code' => 'K05.0', 'label' => 'K05.0 · Gingivitis aguda'],
            ['code' => 'K05.1', 'label' => 'K05.1 · Gingivitis crónica'],
            ['code' => 'K05.3', 'label' => 'K05.3 · Periodontitis crónica'],
            ['code' => 'K01.1', 'label' => 'K01.1 · Dientes impactados'],
            ['code' => 'K07.4', 'label' => 'K07.4 · Maloclusión de tipo no especificado'],
            ['code' => 'K08.1', 'label' => 'K08.1 · Pérdida de dientes por accidente, extracción o enfermedad periodontal'],
            ['code' => 'K03.2', 'label' => 'K03.2 · Erosión dental / desgaste'],
        ];
    }

    /**
     * @return array<int, array{title: string, procedure: string, notes: string, prescription: string}>
     */
    public static function evolutionTemplates(): array
    {
        return [
            [
                'title' => 'Evaluación inicial y diagnóstico',
                'procedure' => 'Examen estomatológico integral',
                'notes' => 'Paciente acude a evaluación clínica. Se realiza anamnesis, examen extraoral e intraoral, registro de odontograma inicial y explicación de plan de tratamiento.',
                'prescription' => 'Higiene oral con cepillo de cerdas suaves e hilo dental después de cada comida.',
            ],
            [
                'title' => 'Restauración con resina compuesta',
                'procedure' => 'Restauración estética con resina fotocurada',
                'notes' => 'Aislamiento del campo operatorio, remoción de tejido cariado, grabado ácido selectivo, aplicación de adhesivo y restauración incremental con resina compuesta. Control oclusal y pulido.',
                'prescription' => 'Evitar alimentos muy pigmentados durante las próximas 24 horas. Acudir a control si percibe molestia al morder.',
            ],
            [
                'title' => 'Profilaxis y destartraje',
                'procedure' => 'Destartraje supragingival y profilaxis dental',
                'notes' => 'Remoción de cálculo dental con ultrasonido, pulido coronario con pasta profiláctica, aplicación tópica de flúor barniz y refuerzo de técnica de cepillado.',
                'prescription' => 'No ingerir alimentos ni enjuagarse durante 30 minutos posteriores a la aplicación de flúor.',
            ],
            [
                'title' => 'Endodoncia — Apertura e instrumentación',
                'procedure' => 'Tratamiento de conducto (sesión de instrumentación)',
                'notes' => 'Anestesia infiltrativa/troncular, aislamiento absoluto con dique de hule, apertura cameral, conductometría, preparación biomecánica con irrigación de hipoclorito de sodio al 2.5% y medicación intraconducto.',
                'prescription' => 'Ibuprofeno 400 mg cada 8 horas por 3 días condicional a dolor. No masticar alimentos duros por el lado tratado.',
            ],
            [
                'title' => 'Exodoncia simple',
                'procedure' => 'Exodoncia simple atraumática',
                'notes' => 'Asepsia, anestesia local, sindesmotomía, luxación y avulsión de pieza dentaria sin complicaciones. Curetaje alveolar, irrigación y hemostasia compresiva.',
                'prescription' => 'Morder gasa estéril por 30 minutos. Dieta blanda y fría las primeras 24 horas. No escupir, no usar sorbete ni fumar por 48 horas.',
            ],
        ];
    }

    /**
     * @return array<int, array{code: string, name: string, category: string, default_price: float, duration_minutes: int, odontogram_condition: ?string}>
     */
    public static function defaultTreatmentCatalog(): array
    {
        return [
            ['code' => 'PREV-01', 'name' => 'Consulta y diagnóstico integral', 'category' => 'Preventivo', 'default_price' => 60.00, 'duration_minutes' => 30, 'odontogram_condition' => null],
            ['code' => 'PREV-02', 'name' => 'Profilaxis y destartraje completo', 'category' => 'Preventivo', 'default_price' => 140.00, 'duration_minutes' => 45, 'odontogram_condition' => null],
            ['code' => 'PREV-03', 'name' => 'Sellante de fosas y fisuras (por pieza)', 'category' => 'Preventivo', 'default_price' => 70.00, 'duration_minutes' => 20, 'odontogram_condition' => 'sellante'],
            ['code' => 'OPER-01', 'name' => 'Restauración con resina compuesta simple', 'category' => 'Operatoria', 'default_price' => 130.00, 'duration_minutes' => 40, 'odontogram_condition' => 'caries'],
            ['code' => 'OPER-02', 'name' => 'Restauración con resina compuesta compleja', 'category' => 'Operatoria', 'default_price' => 180.00, 'duration_minutes' => 50, 'odontogram_condition' => 'fractura'],
            ['code' => 'ENDO-01', 'name' => 'Endodoncia unirradicular', 'category' => 'Endodoncia', 'default_price' => 380.00, 'duration_minutes' => 60, 'odontogram_condition' => 'endodoncia'],
            ['code' => 'ENDO-02', 'name' => 'Endodoncia multirradicular', 'category' => 'Endodoncia', 'default_price' => 550.00, 'duration_minutes' => 75, 'odontogram_condition' => 'lesion_periapical'],
            ['code' => 'CIRU-01', 'name' => 'Exodoncia simple', 'category' => 'Cirugía', 'default_price' => 150.00, 'duration_minutes' => 30, 'odontogram_condition' => 'extraccion_indicada'],
            ['code' => 'CIRU-02', 'name' => 'Cirugía de tercera molar retenida', 'category' => 'Cirugía', 'default_price' => 450.00, 'duration_minutes' => 60, 'odontogram_condition' => 'impactado'],
            ['code' => 'PROT-01', 'name' => 'Corona metal-porcelana / zirconia', 'category' => 'Prótesis', 'default_price' => 950.00, 'duration_minutes' => 60, 'odontogram_condition' => 'corona'],
            ['code' => 'PROT-02', 'name' => 'Puente fijo (por unidad)', 'category' => 'Prótesis', 'default_price' => 900.00, 'duration_minutes' => 60, 'odontogram_condition' => 'protesis_fija'],
            ['code' => 'IMPL-01', 'name' => 'Implante dental oseointegrado', 'category' => 'Implantología', 'default_price' => 2600.00, 'duration_minutes' => 90, 'odontogram_condition' => 'implante'],
            ['code' => 'ORTO-01', 'name' => 'Instalación de ortodoncia metálica', 'category' => 'Ortodoncia', 'default_price' => 1800.00, 'duration_minutes' => 60, 'odontogram_condition' => 'ortodoncia'],
            ['code' => 'PERI-01', 'name' => 'Raspado y alisado radicular por cuadrante', 'category' => 'Periodoncia', 'default_price' => 220.00, 'duration_minutes' => 45, 'odontogram_condition' => 'movilidad'],
        ];
    }

    public static function labelFor(string $conditionCode): string
    {
        foreach (self::conditions() as $condition) {
            if ($condition['code'] === $conditionCode) {
                return $condition['label'];
            }
        }

        return ucfirst(str_replace('_', ' ', $conditionCode));
    }

    public static function toUniversal(string $fdiCode): string
    {
        $map = [
            '18' => '1', '17' => '2', '16' => '3', '15' => '4', '14' => '5', '13' => '6', '12' => '7', '11' => '8',
            '21' => '9', '22' => '10', '23' => '11', '24' => '12', '25' => '13', '26' => '14', '27' => '15', '28' => '16',
            '38' => '17', '37' => '18', '36' => '19', '35' => '20', '34' => '21', '33' => '22', '32' => '23', '31' => '24',
            '41' => '25', '42' => '26', '43' => '27', '44' => '28', '45' => '29', '46' => '30', '47' => '31', '48' => '32',
            '55' => 'A', '54' => 'B', '53' => 'C', '52' => 'D', '51' => 'E',
            '61' => 'F', '62' => 'G', '63' => 'H', '64' => 'I', '65' => 'J',
            '75' => 'K', '74' => 'L', '73' => 'M', '72' => 'N', '71' => 'O',
            '81' => 'P', '82' => 'Q', '83' => 'R', '84' => 'S', '85' => 'T',
        ];

        return $map[$fdiCode] ?? $fdiCode;
    }

    /**
     * @return array<int, array{type: string, title: string, content: string}>
     */
    public static function documentTemplates(): array
    {
        return [
            [
                'type' => 'consentimiento',
                'title' => 'Consentimiento Informado · Exodoncia / Cirugía Oral',
                'content' => "Yo, en pleno uso de mis facultades, autorizo al cirujano dentista tratante a realizar la extracción dental (exodoncia simple o quirúrgica) indicada en mi historia clínica.\n\nDeclaro haber sido informado sobre el diagnóstico, alternativas de tratamiento, uso de anestesia local, posibles riesgos postoperatorios (inflamación, sangrado leve, hematoma, alveolitis, sensibilidad temporal) y me comprometo a seguir las indicaciones y medicación prescrita.",
            ],
            [
                'type' => 'consentimiento',
                'title' => 'Consentimiento Informado · Tratamiento de Endodoncia',
                'content' => 'Autorizo la realización del tratamiento de conductos (endodoncia) en la pieza indicada, habiendo sido informado sobre los pasos clínicos, necesidad de radiografías de control, pronóstico reservado en casos con infección periapical previa y la necesidad posterior de una rehabilitación coronaria definitiva para evitar fracturas.',
            ],
            [
                'type' => 'consentimiento',
                'title' => 'Consentimiento Informado · Ortodoncia / Rehabilitación',
                'content' => 'Autorizo el inicio del tratamiento odontológico integral / ortodóntico según el plan y presupuesto aprobado. Comprendo la importancia de asistir puntualmente a los controles mensuales, mantener una higiene oral estricta y respetar las indicaciones sobre alimentación y aparatología.',
            ],
            [
                'type' => 'receta',
                'title' => 'Receta · Analgésico y Antiinflamatorio Post-operatorio',
                'content' => "1. Ketorolaco 10 mg (o Ibuprofeno 400 mg): Tomar 1 tableta cada 8 horas por 3 días después de los alimentos.\n2. Amoxicilina 500 mg (solo si no existe alergia a penicilina; de ser alérgico usar Azitromicina 500 mg c/24h por 3 días): 1 cápsula cada 8 horas por 5 días.\n3. Enjuague con Clorhexidina al 0.12%: Realizar buches suaves sin hacer gárgaras cada 12 horas por 7 días, iniciando 24 horas después de la intervención.",
            ],
            [
                'type' => 'receta',
                'title' => 'Receta · Profilaxis e Higiene Periodontal',
                'content' => "1. Pasta dental desensibilizante con nitrato de potasio y flúor: Cepillado suave 3 veces al día.\n2. Colutorio de Clorhexidina 0.12% sin alcohol: 15 ml durante 30 segundos después del cepillado nocturno por 10 días.\n3. Uso diario de hilo dental y cepillos interproximales.",
            ],
            [
                'type' => 'certificado',
                'title' => 'Certificado Odontológico de Atención / Descanso',
                'content' => 'El cirujano dentista que suscribe certifica que el/la paciente ha acudido a consulta odontológica en nuestra clínica y se le ha realizado el procedimiento correspondiente, requiriendo reposo relativo y cuidados postoperatorios según indicación clínica.',
            ],
        ];
    }
}
