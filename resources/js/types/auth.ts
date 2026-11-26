export type UserCapabilities = {
    edit_clinical: boolean;
    manage_patients: boolean;
    manage_appointments: boolean;
    manage_cash: boolean;
    manage_clinic: boolean;
    view_audit: boolean;
};

export type User = {
    id: number;
    clinic_id: number | null;
    name: string;
    email: string;
    role: 'admin_consultorio' | 'odontologo' | 'asistente' | 'contador';
    role_label: string;
    specialty?: string | null;
    license_number?: string | null;
    phone?: string | null;
    is_active: boolean;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    can: UserCapabilities;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type PrintSettings = {
    paper_size: 'a4' | 'letter';
    thermal_width: '80mm' | '58mm';
    show_logo: boolean;
    show_cop: boolean;
    header_text: string;
    footer_text: string;
};

export type ClinicInfo = {
    id: number;
    name: string;
    slug: string;
    ruc: string | null;
    slogan: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    logo_url: string | null;
    primary_color: string;
    currency_symbol: string;
    chairs: string[];
    settings: Record<string, unknown>;
    print_settings: PrintSettings;
    has_apiperu_token: boolean;
    auto_lock_minutes: number;
};

export type Auth = {
    user: User;
    clinic: ClinicInfo | null;
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
