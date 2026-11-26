export type MedicalAlert = {
    type: string;
    label: string;
    severity: 'alta' | 'media' | 'baja';
};

export type MedicalHistory = {
    id: number;
    clinic_id: number;
    patient_id: number;
    chief_complaint: string | null;
    current_illness: string | null;
    medical_history_notes: string | null;
    dental_history_notes: string | null;
    allergies: string | null;
    current_medications: string | null;
    chronic_conditions: string[] | null;
    family_history: string | null;
    review_of_systems: Record<string, boolean> | null;
    habits: string[] | null;
    is_pregnant: boolean;
    asa_classification: string | null;
    medical_alerts: MedicalAlert[] | null;
    vital_signs: {
        pa?: string;
        fc?: string;
        fr?: string;
        temp?: string;
        sato2?: string;
        weight_kg?: string;
        height_cm?: string;
        bmi?: string;
    } | null;
    extraoral_exam: string | null;
    intraoral_exam: string | null;
    tmj_exam: string | null;
    soft_tissues_exam: string | null;
    periodontal_exam: string | null;
    occlusion_exam: string | null;
    stomatognathic_exam: Record<string, string> | null;
    presumptive_diagnosis: string | null;
    definitive_diagnosis: string | null;
    cie10_code: string | null;
    version: number;
    updated_at: string;
    updater?: { id: number; name: string } | null;
};

export type OdontogramCondition = {
    code: string;
    label: string;
    short: string;
    category: string;
    default_status: 'por_realizar' | 'realizado' | 'sano' | 'ausente';
    surface_mode: 'surface' | 'tooth' | 'multi';
    symbol: string;
    color_todo: string;
    color_done: string;
};

export type OdontogramFinding = {
    id?: number;
    tooth_code: string;
    surface: 'O' | 'V' | 'L' | 'M' | 'D' | 'FULL';
    condition_code: string;
    status: 'por_realizar' | 'realizado' | 'sano' | 'ausente';
    notes?: string | null;
    treatment_plan_item_id?: number | null;
};

export type Odontogram = {
    id: number;
    clinic_id: number;
    patient_id: number;
    created_by: number | null;
    type: 'inicial' | 'evolutivo';
    dentition: 'adulto' | 'nino' | 'mixto';
    notation: 'FDI' | 'UNIVERSAL';
    recorded_at: string;
    notes: string | null;
    version: number;
    creator?: { id: number; name: string } | null;
    findings: OdontogramFinding[];
};

export type ClinicalEvolution = {
    id: number;
    clinic_id: number;
    patient_id: number;
    user_id: number;
    appointment_id: number | null;
    attended_at: string;
    procedure_summary: string;
    clinical_notes: string;
    soap_subjective: string | null;
    soap_objective: string | null;
    soap_assessment: string | null;
    soap_plan: string | null;
    cie10_code: string | null;
    prescription_notes: string | null;
    signed_at: string | null;
    signed_by: number | null;
    signature_hash: string | null;
    parent_evolution_id: number | null;
    correction_reason: string | null;
    is_locked: boolean;
    version: number;
    author?: {
        id: number;
        name: string;
        specialty?: string | null;
        license_number?: string | null;
    } | null;
    signer?: { id: number; name: string } | null;
    corrections?: ClinicalEvolution[];
};

export type TreatmentCatalogItem = {
    id: number;
    clinic_id: number;
    code: string;
    name: string;
    category: string;
    default_price: string | number;
    duration_minutes: number;
    odontogram_condition: string | null;
    is_active: boolean;
};

export type TreatmentPlanItem = {
    id: number;
    treatment_plan_id: number;
    treatment_catalog_id: number | null;
    tooth_code: string | null;
    surface: string | null;
    description: string;
    phase: string;
    priority: 'alta' | 'media' | 'baja';
    quantity: number;
    unit_price: string | number;
    discount: string | number;
    status: 'pendiente' | 'en_curso' | 'realizado';
    completed_at: string | null;
};

export type TreatmentPlan = {
    id: number;
    patient_id: number;
    title: string;
    status: 'borrador' | 'aprobado' | 'en_curso' | 'completado' | 'cancelado';
    discount_amount: string | number;
    valid_until: string | null;
    notes: string | null;
    approved_at: string | null;
    created_at: string;
    creator?: { id: number; name: string } | null;
    items: TreatmentPlanItem[];
};

export type Payment = {
    id: number;
    patient_id: number;
    treatment_plan_id: number | null;
    receipt_number: string;
    sunat_series: string | null;
    sunat_correlative: string | null;
    document_type: 'recibo' | 'boleta' | 'factura';
    customer_doc_type: string | null;
    customer_doc_number: string | null;
    customer_name: string | null;
    customer_address: string | null;
    payment_method: 'efectivo' | 'tarjeta' | 'yape_plin' | 'transferencia';
    amount: string | number;
    tax_subtotal: string | number;
    tax_igv: string | number;
    paid_at: string;
    concept: string;
    notes: string | null;
    status: 'registrado' | 'anulado';
    sunat_status: string;
    sunat_hash: string | null;
    receiver?: { id: number; name: string } | null;
    treatment_plan?: { id: number; title: string } | null;
    patient?: {
        id: number;
        first_name: string;
        last_name: string;
        full_name: string;
        history_number: string;
    } | null;
};

export type CashClosing = {
    id: number;
    closing_date: string;
    opening_amount: string | number;
    cash_total: string | number;
    card_total: string | number;
    digital_total: string | number;
    transfer_total: string | number;
    grand_total: string | number;
    notes: string | null;
    closed_at: string;
    closer?: { id: number; name: string } | null;
};

export type ClinicalDocument = {
    id: number;
    patient_id: number;
    type: 'consentimiento' | 'receta' | 'certificado';
    title: string;
    content: string;
    signature_data: string | null;
    signed_by_name: string | null;
    signed_at: string | null;
    issued_at: string;
    creator?: {
        id: number;
        name: string;
        license_number?: string | null;
    } | null;
};

export type RadiographMeasurement = {
    id: string;
    x1: number;
    y1: number;
    x2: number;
    y2: number;
    mm: number | null;
    label: string;
};

export type RadiographPin = {
    id: string;
    x: number;
    y: number;
    label: string;
    color: string;
};

export type RadiographAnnotations = {
    calibration_mm_per_px?: number | null;
    measurements?: RadiographMeasurement[];
    pins?: RadiographPin[];
    filters?: {
        brightness?: number;
        contrast?: number;
        invert?: boolean;
    };
};

export type PatientImage = {
    id: number;
    patient_id: number;
    category:
        | 'radiografia'
        | 'intraoral'
        | 'extraoral'
        | 'antes_despues'
        | 'documento';
    title: string;
    tooth_code: string | null;
    file_path: string;
    url: string;
    taken_at: string;
    notes: string | null;
    annotations?: RadiographAnnotations | null;
    uploader?: { id: number; name: string } | null;
};

export type PeriodontalToothEntry = {
    pd: [number, number, number, number, number, number];
    bop: boolean;
    plaque: boolean;
    recession: number;
    mobility: number;
    furcation: number;
};

export type Periodontogram = {
    id: number;
    patient_id: number;
    recorded_at: string;
    entries: Record<string, PeriodontalToothEntry>;
    bleeding_index: string | number;
    plaque_index: string | number;
    notes: string | null;
    creator?: { id: number; name: string } | null;
};

export type OrthodonticControl = {
    id: number;
    patient_id: number;
    control_date: string;
    stage: string;
    upper_archwire: string | null;
    lower_archwire: string | null;
    elastics: string | null;
    hygiene_status: 'buena' | 'regular' | 'deficiente';
    clinical_notes: string;
    next_control_date: string | null;
    dentist?: { id: number; name: string } | null;
};

export type Appointment = {
    id: number;
    clinic_id: number;
    patient_id: number;
    dentist_id: number;
    chair: string;
    title: string;
    starts_at: string;
    ends_at: string;
    status:
        | 'reservada'
        | 'confirmada'
        | 'en_sala'
        | 'atendida'
        | 'cancelada'
        | 'no_asistio';
    is_waitlist: boolean;
    reason: string | null;
    notes: string | null;
    version: number;
    confirmation_token?: string | null;
    reminder_sent_at?: string | null;
    patient_confirmed_at?: string | null;
    reschedule_requested_at?: string | null;
    reschedule_note?: string | null;
    lab_orders?: {
        id: number;
        order_code: string;
        work_type: string;
        status: string;
        due_date: string | null;
    }[];
    patient?: {
        id: number;
        first_name: string;
        last_name: string;
        full_name: string;
        history_number: string;
        phone: string | null;
        medical_history?: Pick<
            MedicalHistory,
            'id' | 'patient_id' | 'medical_alerts' | 'allergies'
        > | null;
    } | null;
    dentist?: {
        id: number;
        name: string;
        specialty?: string | null;
    } | null;
};

export type LabOrder = {
    id: number;
    patient_id: number;
    dentist_id: number | null;
    appointment_id: number | null;
    order_code: string;
    lab_name: string;
    work_type: string;
    tooth_code: string | null;
    shade: string | null;
    material: string | null;
    sent_at: string | null;
    due_date: string | null;
    received_at: string | null;
    delivered_at: string | null;
    cost: string | number;
    status: string;
    notes: string | null;
    patient?: {
        id: number;
        full_name: string;
        history_number: string;
        phone?: string | null;
    } | null;
    dentist?: { id: number; name: string } | null;
    appointment?: {
        id: number;
        title: string;
        starts_at: string;
        chair: string;
    } | null;
};

export type SterilizationCycle = {
    id: number;
    autoclave_name: string;
    cycle_number: string;
    sterilized_at: string;
    temperature_c: string | number;
    pressure_bar: string | number;
    duration_minutes: number;
    load_summary: string;
    chemical_indicator: 'conforme' | 'fallido';
    biological_indicator: 'pendiente' | 'negativo_conforme' | 'positivo_alerta';
    notes: string | null;
    operator?: { id: number; name: string } | null;
};

export type InventoryItem = {
    id: number;
    code: string;
    name: string;
    category: string;
    unit: string;
    lot_number: string | null;
    expires_at: string | null;
    current_stock: string | number;
    min_stock: string | number;
    unit_cost: string | number;
    supplier: string | null;
    is_active: boolean;
};

export type InventoryMovement = {
    id: number;
    inventory_item_id: number;
    type: 'entrada' | 'salida' | 'ajuste';
    quantity: string | number;
    reason: string;
    moved_at: string;
    item?: { id: number; code: string; name: string; unit: string } | null;
    user?: { id: number; name: string } | null;
    patient?: {
        id: number;
        full_name: string;
        history_number: string;
    } | null;
};

export type PaymentInstallment = {
    id: number;
    patient_id: number;
    treatment_plan_id: number | null;
    payment_id: number | null;
    installment_number: number;
    due_date: string;
    amount: string | number;
    paid_amount: string | number;
    paid_at: string | null;
    status: 'pendiente' | 'parcial' | 'pagado';
    notes: string | null;
    payment?: { id: number; receipt_number: string } | null;
};

export type Patient = {
    id: number;
    clinic_id: number;
    history_number: string;
    fhir_uuid?: string | null;
    first_name: string;
    last_name: string;
    full_name: string;
    age: number | null;
    document_type: string;
    document_number: string | null;
    birth_date: string | null;
    gender: string | null;
    blood_type?: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    occupation: string | null;
    insurance_provider: string | null;
    insurance_number: string | null;
    guardian_name: string | null;
    guardian_phone: string | null;
    guardian_relationship: string | null;
    emergency_contact_name?: string | null;
    emergency_contact_phone?: string | null;
    tags: string[] | null;
    notes: string | null;
    is_active: boolean;
    updated_at: string;
    appointments_count?: number;
    clinical_evolutions_count?: number;
    medical_history?: MedicalHistory | null;
    clinical_evolutions?: ClinicalEvolution[];
    odontograms?: Odontogram[];
    treatment_plans?: TreatmentPlan[];
    appointments?: Appointment[];
    payments?: Payment[];
    clinical_documents?: ClinicalDocument[];
    images?: PatientImage[];
    periodontograms?: Periodontogram[];
    orthodontic_controls?: OrthodonticControl[];
    lab_orders?: LabOrder[];
    payment_installments?: PaymentInstallment[];
};

export type TimelineEvent = {
    id: string;
    kind: 'evolucion' | 'odontograma' | 'cita' | 'plan' | 'pago' | 'documento';
    date: string;
    title: string;
    detail: string;
    actor: string | null;
};

export type FinancialSummary = {
    total_budgeted: number;
    total_paid: number;
    balance_due: number;
};
