<?php

declare(strict_types=1);

namespace App\Services;

class UserManualCatalog
{
    /**
     * Definición centralizada de capítulos del manual para visor web y exportación PDF.
     *
     * @return list<array{
     *     id: string,
     *     number: string,
     *     title: string,
     *     category: string,
     *     roles: list<string>,
     *     summary: string,
     *     tip: string,
     *     steps: list<string>,
     *     screenshots: list<array{file: string, caption: string}>
     * }>
     */
    public static function chapters(): array
    {
        return [
            [
                'id' => 'acceso-seguro',
                'number' => '01',
                'title' => 'Portada, Estándares e Ingreso Seguro',
                'category' => 'Acceso y Seguridad',
                'roles' => ['Todos los roles'],
                'summary' => 'Acceso exclusivo para el personal autorizado del consultorio mediante correo institucional, contraseña robusta, llave biométrica (Passkey / WebAuthn) o verificación en dos pasos (2FA).',
                'tip' => 'El registro público está cerrado automáticamente en producción. Solo el Administrador del consultorio puede crear nuevas cuentas desde Ajustes.',
                'steps' => [
                    'Ingresa desde tu navegador a la dirección oficial del consultorio (recomendado bajo HTTPS).',
                    'Introduce tu correo electrónico y contraseña, o selecciona ingresar con tu llave biométrica (Passkey) previamente registrada.',
                    'Si cuentas con autenticación en dos pasos (2FA), ingresa el código temporal de tu aplicación autenticadora.',
                    'Al ausentarte del sillón, pulsa el icono de candado en la barra superior o espera el bloqueo automático por inactividad.',
                ],
                'screenshots' => [
                    ['file' => '01-portada.jpg', 'caption' => 'Portada principal de DentaFlow con homologación clínica y acceso directo al consultorio.'],
                    ['file' => '02-login.jpg', 'caption' => 'Pantalla de inicio de sesión segura con soporte para credenciales y Passkeys.'],
                ],
            ],
            [
                'id' => 'panel-clinico',
                'number' => '02',
                'title' => 'Panel Clínico, Alertas y Búsqueda Global (⌘K)',
                'category' => 'Operación Diaria',
                'roles' => ['Todos los roles'],
                'summary' => 'Centro de control diario con métricas en tiempo real, sala de espera, alertas críticas de laboratorio/morosidad/autoclave e indicador de conectividad offline PWA.',
                'tip' => 'Presiona Cmd+K (Mac) o Ctrl+K (Windows) desde cualquier módulo para buscar instantáneamente un paciente por nombre, DNI o número de historia clínica.',
                'steps' => [
                    'Revisa en la fila superior las tarjetas de Citas de hoy, Pacientes en sala de espera, Pacientes activos, Planes aprobados e Ingresos del día.',
                    'Atiende primero las alertas de color en el panel central: trabajos de laboratorio próximos a cita, cuotas vencidas, insumos bajos o ciclos de autoclave en incubación.',
                    'Usa los botones rápidos (+ Nuevo paciente, Abrir agenda, Ir a caja) para iniciar flujos en 1 clic.',
                    'Si trabajas temporalmente sin internet, el indicador superior mostrará modo offline y sincronizará cambios pendientes al recuperar conexión.',
                ],
                'screenshots' => [
                    ['file' => '03-dashboard.jpg', 'caption' => 'Panel principal del consultorio con indicadores, alertas prioritarias, sala de espera y últimas evoluciones.'],
                ],
            ],
            [
                'id' => 'pacientes-reniec',
                'number' => '03',
                'title' => 'Directorio de Pacientes y Consulta RENIEC por DNI',
                'category' => 'Admisión y Filiación',
                'roles' => ['Administrador', 'Odontólogo', 'Asistente'],
                'summary' => 'Registro completo de pacientes sin historias duplicadas, con autocompletado directo de nombres, apellidos y dirección mediante consulta RENIEC (apiperu.dev).',
                'tip' => 'Al ingresar los 8 dígitos del DNI en el modal de registro, el sistema verifica primero si el paciente ya posee historia clínica local antes de consultar RENIEC.',
                'steps' => [
                    'Abre el módulo Pacientes desde el menú lateral y utiliza los filtros por nombre, DNI, historia clínica o etiquetas (ej. VIP, ortodoncia).',
                    'Haz clic en Nuevo paciente, ingresa los 8 dígitos del DNI y pulsa Buscar DNI para autocompletar los datos personales.',
                    'Completa teléfono, correo, aseguradora y persona de contacto antes de guardar la ficha digital (se asigna código HC automático).',
                    'Haz clic sobre cualquier paciente para abrir su ficha clínica integral con alertas médicas destacadas en rojo.',
                ],
                'screenshots' => [
                    ['file' => '04-pacientes.jpg', 'caption' => 'Listado general de pacientes con buscador rápido, alertas médicas visibles y creación por DNI.'],
                    ['file' => '05-ficha-resumen.jpg', 'caption' => 'Resumen de ficha clínica integral del paciente con alertas médicas, UUID HL7 FHIR y accesos directos.'],
                ],
            ],
            [
                'id' => 'historia-clinica-hce',
                'number' => '04',
                'title' => 'Historia Clínica Estomatológica Homologada (HCE)',
                'category' => 'Módulo Clínico',
                'roles' => ['Administrador', 'Odontólogo'],
                'summary' => 'Anamnesis estructurada bajo estándar internacional: clasificación anestésica ASA I–V, revisión por aparatos y sistemas, IMC automático, examen estomatognático, CIE-10 y exportación HL7 FHIR R4.',
                'tip' => 'Registra siempre alergias medicamentosas (como penicilina o AINEs) y riesgo ASA; aparecerán fijadas en rojo en toda la ficha para prevenir incidentes.',
                'steps' => [
                    'En la ficha del paciente, selecciona la pestaña Historia clínica.',
                    'Registra motivo de consulta, enfermedad actual, alergias, medicación habitual y el nivel de riesgo anestésico ASA (I a V).',
                    'Ingresa signos vitales (PA, FC, FR, T°, SatO2) y antropometría (peso/talla para cálculo inmediato de IMC) junto con la revisión por sistemas.',
                    'Documenta examen extraoral, intraoral, ATM, oclusión y diagnóstico CIE-10. Puedes exportar el PDF oficial o descargar el Bundle JSON HL7 FHIR R4.',
                ],
                'screenshots' => [
                    ['file' => '06-historia-clinica.jpg', 'caption' => 'Historia clínica homologada con riesgo ASA, signos vitales, IMC automático y examen estomatognático.'],
                ],
            ],
            [
                'id' => 'odontograma-evoluciones',
                'number' => '05',
                'title' => 'Odontograma Anatómico y Evoluciones SOAP Firmadas',
                'category' => 'Módulo Clínico',
                'roles' => ['Administrador', 'Odontólogo'],
                'summary' => 'Odontograma interactivo FDI / Universal (adulto, niño o mixto) con comparación histórica de fechas, conversión directa a presupuesto y notas SOAP con sellado SHA-256 inmutable.',
                'tip' => 'Una nota SOAP firmada no puede alterarse retroactivamente para garantizar validez médico-legal. Si necesitas precisar un dato posterior, utiliza Agregar nota de corrección (fe de erratas).',
                'steps' => [
                    'Abre la pestaña Odontograma, elige el estado (Patología / Por hacer en rojo o Realizado / Buen estado en azul) y selecciona el hallazgo clínico.',
                    'Haz clic en la cara dental correspondiente (Oclusal, Mesial, Distal, Vestibular o Palatina/Lingual) o pieza completa.',
                    'Utiliza Comparar fechas para contrastar el odontograma inicial frente al evolutivo, o convierte hallazgos pendientes directo a un presupuesto.',
                    'Desde Nueva atención registra evoluciones SOAP y pulsa Firmar y bloquear para sellar la nota con hash criptográfico SHA-256.',
                ],
                'screenshots' => [
                    ['file' => '07-odontograma.jpg', 'caption' => 'Odontograma anatómico FDI/Universal interactivo con registro de patologías, restauraciones y comparador evolutivo.'],
                ],
            ],
            [
                'id' => 'perio-ortodoncia-presupuestos',
                'number' => '06',
                'title' => 'Periodontograma, Ortodoncia y Presupuestos por Fases',
                'category' => 'Especialidades y Planes',
                'roles' => ['Administrador', 'Odontólogo'],
                'summary' => 'Sondaje periodontal de 6 sitios por pieza con cálculo automático del índice de sangrado y placa, control evolutivo de arcos ortodónticos y presupuestos clínicos por fases.',
                'tip' => 'El sistema calcula automáticamente el porcentaje de sangrado al sondaje (BOP %) para evaluar la evolución entre controles periodontales.',
                'steps' => [
                    'En la pestaña Perio y Ortodoncia ingresa las profundidades de sondaje (6 puntos), recesión, movilidad, furcación, sangrado y placa.',
                    'En la sección inferior registra controles ortodónticos indicando etapa, calibre de arco superior/inferior, elásticos e higiene oral.',
                    'En la pestaña Tratamientos crea planes organizados por fase (Preventiva, Rehabilitación, Ortodoncia), aplica descuentos globales y marca ítems realizados al concluir cada sesión.',
                ],
                'screenshots' => [
                    ['file' => '08-periodonto-ortodoncia.jpg', 'caption' => 'Registro de periodontograma de 6 sitios por pieza y seguimiento de controles de ortodoncia.'],
                    ['file' => '09-presupuestos.jpg', 'caption' => 'Planes de tratamiento y presupuestos detallados por fases con estado de avance en tiempo real.'],
                ],
            ],
            [
                'id' => 'visor-rx-documentos',
                'number' => '07',
                'title' => 'Visor Radiográfico Medible (mm) y Documentos Firmados',
                'category' => 'Imagenología y Legal',
                'roles' => ['Administrador', 'Odontólogo'],
                'summary' => 'Galería clínica privada con visor radiográfico calibrable en milímetros (conductometría y altura ósea), filtros diagnósticos y consentimientos informados firmados en pantalla.',
                'tip' => 'Las radiografías y fotografías clínicas se almacenan en disco privado autenticado: nunca son accesibles mediante enlaces públicos sin sesión iniciada.',
                'steps' => [
                    'En la pestaña Imágenes sube radiografías periapicales, panorámicas o fotografías intraorales/antes y después.',
                    'Pulsa Visor Rx / Medir sobre una radiografía, ajusta brillo/contraste/negativo, calibra una referencia conocida en mm y traza mediciones o pines de hallazgo.',
                    'En la pestaña Documentos emite consentimientos informados, recetas médicas o certificados odontológicos desde plantillas clínicas.',
                    'Solicita al paciente su firma táctil directamente en pantalla (tablet o mouse) y descarga el PDF con respaldo legal.',
                ],
                'screenshots' => [
                    ['file' => '10-visor-radiografico.jpg', 'caption' => 'Visor radiográfico diagnóstico con calibración milimétrica en mm, filtros y pines de hallazgo.'],
                    ['file' => '11-documentos.jpg', 'caption' => 'Emisión de recetas médicas y consentimientos informados con firma digital táctil e impresión PDF.'],
                ],
            ],
            [
                'id' => 'agenda-whatsapp',
                'number' => '08',
                'title' => 'Agenda por Sillón y Confirmación WhatsApp 1-Clic',
                'category' => 'Agenda y Recepción',
                'roles' => ['Administrador', 'Odontólogo', 'Asistente'],
                'summary' => 'Calendario clínico sin cruces de horario por sillón dental y odontólogo, gestión de lista de espera y recordatorios por WhatsApp con enlace público de confirmación.',
                'tip' => 'El enlace enviado por WhatsApp permite al paciente confirmar su cita o pedir reprogramación sin necesidad de tener cuenta ni iniciar sesión.',
                'steps' => [
                    'Accede a Agenda, filtra por fecha o sillón odontológico y crea citas nuevas (el sistema bloquea automáticamente horarios superpuestos).',
                    'Actualiza el estado conforme avanza el flujo clínico: Programada -> Confirmada -> En sala -> Atendida.',
                    'Pulsa el botón Recordar WhatsApp en la cita para abrir WhatsApp con el mensaje prearmado y el enlace único de confirmación.',
                    'Cuando el paciente confirma o solicita cambio de fecha desde su celular, la agenda y alertas se actualizan al instante.',
                ],
                'screenshots' => [
                    ['file' => '12-agenda.jpg', 'caption' => 'Agenda clínica por sillón y profesional con control de estados y recordatorio WhatsApp en 1 clic.'],
                ],
            ],
            [
                'id' => 'tratamientos-laboratorio',
                'number' => '09',
                'title' => 'Catálogo de Tratamientos y Laboratorio Dental',
                'category' => 'Tarifario y Prótesis',
                'roles' => ['Administrador', 'Odontólogo', 'Asistente (solo Lab)'],
                'summary' => 'Administración del tarifario maestro del consultorio y seguimiento completo de trabajos protésicos externos (coronas, prótesis, alineadores) vinculados a cita.',
                'tip' => 'Si una orden de laboratorio sigue pendiente cuando faltan menos de 48 horas para la cita del paciente, el sistema muestra una alerta prioritaria en Inicio.',
                'steps' => [
                    'En Tratamientos gestiona el catálogo maestro con código, categoría, precio base en moneda local, duración estimada y condición asociada del odontograma.',
                    'En Laboratorio registra cada orden especificando paciente, pieza dental, tono de color, laboratorio externo, costo y fecha compromiso.',
                    'Actualiza el flujo de etapas (Por enviar -> En laboratorio -> Recibido -> Probado -> Instalado / Rehacer) y valida la recepción antes de citar al paciente.',
                ],
                'screenshots' => [
                    ['file' => '13-tratamientos.jpg', 'caption' => 'Catálogo maestro de tratamientos y tarifario clínico editable con vinculación al odontograma.'],
                    ['file' => '14-laboratorio.jpg', 'caption' => 'Control de órdenes de laboratorio dental, colorimetría, costos de taller y alertas de entrega.'],
                ],
            ],
            [
                'id' => 'esterilizacion-kardex',
                'number' => '10',
                'title' => 'Bioseguridad (Autoclave) y Kardex de Insumos',
                'category' => 'Bioseguridad y Almacén',
                'roles' => ['Administrador', 'Odontólogo', 'Asistente'],
                'summary' => 'Trazabilidad sanitaria de ciclos de esterilización (controles físicos, químicos e indicadores biológicos por esporas) e inventario crítico de insumos con lotes y vencimientos.',
                'tip' => 'Todo ciclo con indicador biológico pendiente se muestra en el panel principal hasta registrar su lectura final aprobada.',
                'steps' => [
                    'En Esterilización / Kardex registra cada ciclo de autoclave con número de lote, temperatura, presión, minutos y resultado de cinta/integrador químico.',
                    'Registra posteriormente la lectura de control biológico por esporas (Pendiente / Negativo aprobado / Positivo alerta).',
                    'En la pestaña de Kardex administra insumos críticos (anestésicos, resinas, suturas, implantes) con lote, fecha de vencimiento y stock mínimo.',
                    'Registra entradas por compra y salidas vinculadas al paciente atendido para auditar consumo real.',
                ],
                'screenshots' => [
                    ['file' => '15-esterilizacion.jpg', 'caption' => 'Registro de ciclos de autoclave con indicadores químicos/biológicos y Kardex de insumos críticos.'],
                ],
            ],
            [
                'id' => 'caja-sunat-reportes',
                'number' => '11',
                'title' => 'Caja Diaria, SUNAT UBL 2.1, Cuotas y Reportes',
                'category' => 'Finanzas y Facturación',
                'roles' => ['Administrador', 'Contador', 'Asistente (Caja)'],
                'summary' => 'Control financiero completo: cobros por medio de pago, tickets térmicos 80/58 mm, emisión XML SUNAT UBL 2.1 con consulta RUC, cuotas de financiamiento y liquidación de comisiones.',
                'tip' => 'En los cierres de caja diarios puedes imprimir directamente el arqueo consolidado por medios de pago (Efectivo, Yape/Plin, Tarjeta y Transferencia) en formato 80 mm o 58 mm.',
                'steps' => [
                    'En Caja y cobros registra pagos asociando comprobante (Recibo, Boleta o Factura con validación automática de RUC vía apiperu.dev) y descarga el PDF, ticket térmico o XML UBL 2.1.',
                    'Configura planes de financiamiento en cuotas desde la ficha del paciente o cobra cuotas pendientes con control automático de morosidad.',
                    'Al final de la jornada ejecuta Cerrar caja del día e imprime el reporte térmico consolidado por medio de pago.',
                    'En Reportes filtra rangos de fechas para analizar ingresos por método, procedimientos frecuentes, saldos por cobrar y comisiones médicas, con exportación a Excel CSV.',
                ],
                'screenshots' => [
                    ['file' => '16-caja.jpg', 'caption' => 'Caja diaria con desglose por medios de pago, tickets térmicos 80/58 mm, morosidad y saldos pendientes.'],
                    ['file' => '17-reportes.jpg', 'caption' => 'Reportes gerenciales de recaudación, asistencia de agenda y liquidación de comisiones por odontólogo.'],
                ],
            ],
            [
                'id' => 'auditoria-ajustes-seguridad',
                'number' => '12',
                'title' => 'Auditoría Normativa, Ajustes del Consultorio y Seguridad',
                'category' => 'Administración y Gobierno',
                'roles' => ['Administrador', 'Contador (solo Auditoría)'],
                'summary' => 'Supervisión total del consultorio: bitácora inalterable de acciones y accesos, identidad/logotipo/impresoras térmicas, token apiperu.dev, protección del rol Administrador y copias de seguridad.',
                'tip' => 'El rol Administrador del consultorio está protegido en servidor e interfaz: no puede degradarse ni eliminarse para evitar dejar al consultorio sin acceso administrativo.',
                'steps' => [
                    'En Auditoría consulta el historial cronológico de creaciones, firmas clínicas, exportaciones, respaldos e intentos de inicio de sesión exitosos/fallidos.',
                    'En Ajustes personaliza datos del consultorio, logotipo, sillones, tiempo de auto-bloqueo, formato de papel térmico (80/58 mm) y token RENIEC apiperu.dev.',
                    'En Equipo y Roles gestiona usuarios, colegiaturas y comisiones; las cuentas Administrador del consultorio muestran la etiqueta Rol protegido.',
                    'En Respaldo y Reinicio descarga copias portables JSON verificadas con firma HMAC-SHA256 o volcados SQL, y administra tu 2FA o Passkeys desde Ajustes de cuenta.',
                ],
                'screenshots' => [
                    ['file' => '18-auditoria.jpg', 'caption' => 'Bitácora de auditoría normativa con usuario, acción, IP y resumen trazable.'],
                    ['file' => '19-ajustes.jpg', 'caption' => 'Centro de ajustes del consultorio: logotipo, sillones, impresiones térmicas e integraciones.'],
                    ['file' => '20-equipo-roles.jpg', 'caption' => 'Gestión del equipo odontológico con protección inmutable del rol Administrador del consultorio.'],
                    ['file' => '21-perfil-seguridad.jpg', 'caption' => 'Ajustes personales de cuenta, contraseña, autenticación 2FA y Passkeys biométricas.'],
                ],
            ],
            [
                'id' => 'inteligencia-artificial-crm',
                'number' => '13',
                'title' => 'Inteligencia Artificial Clínica, Contralor IA, CRM y Reserva 24/7',
                'category' => 'Inteligencia Artificial',
                'roles' => ['Administrador', 'Odontólogo', 'Asistente (CRM)', 'Contador (Contralor/Reportes IA)'],
                'summary' => 'Suite inteligente de nueva generación: Resumen Pre-Consulta IA, Copiloto SOAP por Voz, pre-evaluación de Rayos X IA, Reportes Conversacionales IA, auditoría automática Contralor IA, CRM WhatsApp y Reserva Online 24/7.',
                'tip' => 'El motor IA funciona de forma integrada sin costo externo desde el primer día; adicionalmente puedes conectar tu clave opcional de Google Gemini u OpenAI desde Ajustes > Integraciones.',
                'steps' => [
                    'En la ficha del paciente pulsa Generar resumen IA para revisar riesgos ASA, hallazgos pendientes y saldo en segundos antes de atender.',
                    'En Nueva atención usa Dictar voz o escribe texto libre y pulsa Estructurar SOAP para autocompletar Subjetivo, Objetivo, Evaluación, Plan y código CIE-10.',
                    'En Contralor IA audita todo el consultorio (consentimientos faltantes, notas sin firmar, hallazgos sin presupuestar y laboratorio próximo) con puntuación de cumplimiento.',
                    'En CRM y Retención clasifica pacientes por etapa del embudo, redacta mensajes WhatsApp personalizados con IA y comparte el enlace público /reservar-cita para citas online 24/7.',
                ],
                'screenshots' => [
                    ['file' => '22-contralor-ia.jpg', 'caption' => 'Panel de Contralor IA con porcentaje de cumplimiento normativo, fuga de ingresos y brechas accionables.'],
                    ['file' => '23-crm-retencion.jpg', 'caption' => 'CRM odontológico por etapas del embudo con generador de mensajes personalizados para WhatsApp.'],
                    ['file' => '24-reserva-online.jpg', 'caption' => 'Portal público de Reserva de Citas Online 24/7 conectado en tiempo real con la disponibilidad de cada odontólogo.'],
                ],
            ],
        ];
    }

    /**
     * Matriz oficial de permisos por rol del consultorio.
     *
     * @return list<array{module: string, admin: string, dentist: string, assistant: string, accountant: string}>
     */
    public static function roleMatrix(): array
    {
        return [
            ['module' => 'Inicio, Búsqueda ⌘K y Manual', 'admin' => 'Completo', 'dentist' => 'Completo', 'assistant' => 'Completo', 'accountant' => 'Completo'],
            ['module' => 'Pacientes y Consulta DNI RENIEC', 'admin' => 'Crear/Editar/Ver', 'dentist' => 'Crear/Editar/Eliminar', 'assistant' => 'Crear/Editar/Ver', 'accountant' => 'Solo lectura reportes'],
            ['module' => 'Historia Clínica, Odontograma, Perio, Rx y SOAP', 'admin' => 'Completo + Firma', 'dentist' => 'Completo + Firma', 'assistant' => 'Restringido (solo lectura ficha)', 'accountant' => 'Sin acceso'],
            ['module' => 'Agenda y Recordatorio WhatsApp 1-Clic', 'admin' => 'Completo', 'dentist' => 'Completo', 'assistant' => 'Completo', 'accountant' => 'Sin acceso'],
            ['module' => 'Catálogo de Tratamientos', 'admin' => 'CRUD Completo', 'dentist' => 'CRUD Completo', 'assistant' => 'Consulta', 'accountant' => 'Consulta'],
            ['module' => 'Laboratorio Dental y Autoclave / Kardex', 'admin' => 'Completo', 'dentist' => 'Completo', 'assistant' => 'Completo', 'accountant' => 'Sin acceso'],
            ['module' => 'Caja Diaria, Cierre Térmico, Cuotas y SUNAT UBL', 'admin' => 'Completo', 'dentist' => 'Solo vista ficha', 'assistant' => 'Cobros y Cierre', 'accountant' => 'Completo'],
            ['module' => 'Reportes Financieros, Reportes IA y Comisiones', 'admin' => 'Completo + CSV/IA', 'dentist' => 'Completo + CSV/IA', 'assistant' => 'Completo + CSV/IA', 'accountant' => 'Completo + CSV/IA'],
            ['module' => 'Contralor IA (Auditoría Clínica/Financiera)', 'admin' => 'Completo', 'dentist' => 'Completo', 'assistant' => 'Sin acceso', 'accountant' => 'Lectura'],
            ['module' => 'CRM Retención WhatsApp IA y Reserva Online 24/7', 'admin' => 'Completo', 'dentist' => 'Completo', 'assistant' => 'Completo', 'accountant' => 'Lectura / Campañas'],
            ['module' => 'Bitácora de Auditoría Normativa', 'admin' => 'Completo', 'dentist' => 'Sin acceso', 'assistant' => 'Sin acceso', 'accountant' => 'Lectura'],
            ['module' => 'Ajustes del Consultorio, Equipo y Respaldos', 'admin' => 'Exclusivo (Rol protegido)', 'dentist' => 'Sin acceso', 'assistant' => 'Sin acceso', 'accountant' => 'Sin acceso'],
        ];
    }
}
