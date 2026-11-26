import { Form, Head, router } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
    canRegister?: boolean;
    showDemoAccounts?: boolean;
};

const DEMO_ACCOUNTS = [
    { label: 'Administrador (Dr. Salazar)', email: 'admin@sonrisa.test' },
    { label: 'Odontóloga (Dra. Vega)', email: 'dra.vega@sonrisa.test' },
    { label: 'Asistente / Recepción', email: 'recepcion@sonrisa.test' },
    { label: 'Contador / Caja', email: 'caja@sonrisa.test' },
];

export default function Login({
    status,
    canResetPassword,
    canRegister = false,
    showDemoAccounts = false,
}: Props) {
    const demoLogin = (email: string) => {
        router.post(store.url(), {
            email,
            password: 'password',
            remember: true,
        });
    };

    return (
        <>
            <Head title="Iniciar sesión" />

            <PasskeyVerify
                label="Ingresar con llave de acceso (Passkey)"
                loadingLabel="Autenticando..."
                separator="O ingresa con tu correo"
            />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-5"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    Correo electrónico
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="doctor@consultorio.pe"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <Label htmlFor="password">Contraseña</Label>
                                    {canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="text-xs sm:text-sm"
                                            tabIndex={5}
                                        >
                                            ¿Olvidaste tu contraseña?
                                        </TextLink>
                                    )}
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Contraseña"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                />
                                <Label htmlFor="remember">
                                    Mantener sesión iniciada
                                </Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 h-11 w-full bg-[#0E8FA3] text-white hover:bg-[#0A6577]"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Iniciar sesión
                            </Button>
                        </div>

                        {canRegister && (
                            <div className="text-center text-sm text-muted-foreground">
                                ¿Nuevo integrante del equipo?{' '}
                                <TextLink href={register()} tabIndex={5}>
                                    Crear cuenta
                                </TextLink>
                            </div>
                        )}
                    </>
                )}
            </Form>

            {showDemoAccounts && import.meta.env.DEV && (
                <div className="mt-5 rounded-xl border border-dashed bg-muted/30 p-3.5">
                    <div className="mb-2 text-xs font-semibold text-muted-foreground">
                        Acceso rápido por rol (clave: password)
                    </div>
                    <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        {DEMO_ACCOUNTS.map((acc) => (
                            <Button
                                key={acc.email}
                                type="button"
                                variant="outline"
                                size="sm"
                                className="h-auto min-h-9 w-full justify-start px-3 py-2 text-left text-xs leading-snug whitespace-normal"
                                onClick={() => demoLogin(acc.email)}
                            >
                                {acc.label}
                            </Button>
                        ))}
                    </div>
                </div>
            )}

            {status && (
                <div className="mt-4 text-center text-sm font-medium text-[#2E9E6B]">
                    {status}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: 'Ingreso al consultorio',
    description:
        'Accede con tus credenciales clínicas o llave biométrica (Passkey)',
};
