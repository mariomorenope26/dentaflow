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
                'summary' => 'Portada institucional del consultorio (con acceso opcional al portal de Reserva Online 24/7) e ingreso exclusivo para personal autorizado mediante correo, contraseña robusta, llave biométrica (Passkey / WebAuthn) o verificación en dos pasos (2FA).',
                'tip' => 'El registro público de usuarios está desactivado en producción. Solo el Administrador del consultorio (cuyo rol está protegido) puede crear o editar cuentas desde Ajustes > Equipo y Roles.',
                'steps' => [
                    'Ingresa desde tu navegador a la dirección oficial del consultorio bajo HTTPS. Los pacientes también pueden acceder desde la portada al portal público Reservar cita online 24/7 si está habilitado.',
                    'Pulsa Ingresar al consultorio e introduce tu correo y contraseña, o autentícate con tu llave biométrica (Passkey) previamente registrada.',
                    'Si tu cuenta tiene verificación en dos pasos (2FA), ingresa el código temporal de tu aplicación autenticadora.',
                    'Al ausentarte del sillón, pulsa el icono de candado en la barra superior o espera el bloqueo automático por inactividad.',
                ],
                'screenshots' => [
                    ['file' => '01-portada.jpg', 'caption' => 'Portada principal de DentaFlow con homologación clínica internacional y acceso directo al consultorio.'],
                    ['file' => '02-login.jpg', 'caption' => 'Pantalla de inicio de sesión segura con soporte para credenciales, Passkeys biométricas y protección por rol.'],
                ],
            ],
            [
                'id' => 'panel-clinico',
                'number' => '02',
                'title' => 'Panel Clínico, Alertas y Búsqueda Global (⌘K)',
                'category' => 'Operación Diaria',
                'roles' => ['Todos los roles'],
                'summary' => 'Centro de control diario con métricas en tiempo real, sala de espera, alertas prioritarias (laboratorio, morosidad, stock/vencimientos, autoclave) e indicador de sincronización offline PWA.',
                'tip' => 'Presiona Cmd+K (Mac) o Ctrl+K (Windows) desde cualquier módulo para buscar instantáneamente un paciente por nombre, DNI o número de historia clínica.',
                'steps' => [
                    'Revisa en la fila superior las tarjetas de Citas de hoy, Pacientes en sala de espera, Pacientes activos, Planes aprobados e Ingresos del día.',
                    'Atiende primero las alertas de color: trabajos de laboratorio próximos a cita, cuotas vencidas, insumos bajos o por vencer y ciclos de autoclave en incubación.',
                    'Usa los accesos rápidos (+ Nuevo paciente, Abrir agenda, Ir a caja) y la barra lateral actualizada con CRM y Retención, Contralor IA y Manual de uso.',
                    'Si pierdes conexión momentáneamente, el indicador superior mostrará modo offline y sincronizará las evoluciones pendientes al reconectar.',
                ],
                'screenshots' => [
                    ['file' => '03-dashboard.jpg', 'caption' => 'Panel principal del consultorio con indicadores, alertas prioritarias, sala de espera y menú lateral completo.'],
                ],
            ],
            [
                'id' => 'pacientes-reniec',
                'number' => '03',
                'title' => 'Pacientes, Consulta RENIEC y Resumen Clínico IA',
                'category' => 'Admisión y Filiación',
                'roles' => ['Administrador', 'Odontólogo', 'Asistente'],
                'summary' => 'Registro de pacientes sin duplicados con autocompletado RENIEC (apiperu.dev) por DNI y tarjeta de Resumen Clínico IA Pre-Consulta en la ficha integral del paciente.',
                'tip' => 'Antes de iniciar la atención en sillón, pulsa Generar resumen IA en la pestaña Resumen del paciente para ver riesgo ASA, alergias, hallazgos sin presupuestar y saldo en segundos.',
                'steps' => [
                    'En Pacientes utiliza los filtros por nombre, DNI, historia clínica o etiquetas clínicas (VIP, ortodoncia, etc.).',
                    'Haz clic en Nuevo paciente, ingresa los 8 dígitos del DNI y pulsa Buscar DNI para autocompletar nombres, apellidos y dirección desde RENIEC (verificando antes duplicados locales).',
                    'Al abrir la ficha del paciente, revisa en rojo las Alertas Médicas y usa la tarjeta Resumen Clínico IA Pre-Consulta (botón Generar resumen IA) para obtener un briefing clínico con el Siguiente paso sugerido.',
                    'Descarga en 1 clic el PDF oficial de Historia Clínica o el Bundle interoperable HL7 FHIR R4 desde la cabecera de la ficha.',
                ],
                'screenshots' => [
                    ['file' => '04-pacientes.jpg', 'caption' => 'Listado general de pacientes con buscador rápido, alertas médicas visibles y creación por DNI.'],
                    ['file' => '05-ficha-resumen.jpg', 'caption' => 'Ficha clínica integral con alertas médicas, línea de tiempo, acceso HL7 FHIR y Resumen Clínico IA Pre-Consulta.'],
                ],
            ],
            [
                'id' => 'historia-clinica-hce',
                'number' => '04',
                'title' => 'Historia Clínica Estomatológica Homologada (HCE)',
                'category' => 'Módulo Clínico',
                'roles' => ['Administrador', 'Odontólogo'],
                'summary' => 'Anamnesis estructurada bajo estándar internacional HL7 FHIR R4 e ISO 13606: riesgo anestésico ASA I–V, revisión por sistemas, signos vitales, IMC automático, examen estomatognático y CIE-10.',
                'tip' => 'El cálculo de IMC se actualiza automáticamente al ingresar peso y talla, y las alergias o condiciones ASA se destacan en toda la ficha para prevenir incidentes.',
                'steps' => [
                    'En la ficha del paciente, abre la pestaña Historia clínica y documenta motivo de consulta, enfermedad actual, alergias y medicación habitual.',
                    'Selecciona la clasificación de riesgo anestésico ASA (I a V), marca antecedentes sistémicos y registra alertas clínicas de severidad alta o media.',
                    'Completa signos vitales (PA, FC, FR, T°, SatO2) y antropometría junto con la revisión por aparatos y sistemas.',
                    'Registra examen extraoral, intraoral, ATM, oclusión (Angle), diagnóstico presuntivo/definitivo con código CIE-10 y guarda con control optimista de versión.',
                ],
                'screenshots' => [
                    ['file' => '06-historia-clinica.jpg', 'caption' => 'Historia clínica homologada con riesgo ASA, signos vitales, IMC automático y examen estomatognático.'],
                ],
            ],
            [
                'id' => 'odontograma-evoluciones',
                'number' => '05',
                'title' => 'Odontograma Anatómico y Copiloto SOAP por Voz + IA',
                'category' => 'Módulo Clínico',
                'roles' => ['Administrador', 'Odontólogo'],
                'summary' => 'Odontograma anatómico FDI / Universal (adulto, niño o mixto) con comparador histórico y conversión directa a presupuesto, integrado con evoluciones SOAP mediante dictado por voz e IA + firma SHA-256.',
                'tip' => 'En Nueva atención, usa Dictar voz (Chrome/Edge) o escribe texto libre y pulsa Estructurar SOAP: la IA separa Subjetivo, Objetivo, Evaluación, Plan y sugiere el código CIE-10 antes de Firmar y bloquear.',
                'steps' => [
                    'En Odontograma selecciona Patología / Por hacer (rojo) o Realizado / Buen estado (azul), marca la condición en la cara o pieza completa y guarda o compara fechas históricas.',
                    'Convierte hallazgos pendientes del odontograma directamente a ítems de un presupuesto desde el panel de hallazgos.',
                    'Pulsa Nueva atención para abrir el registro evolutivo: dicta o redacta la nota clínica en Copiloto Clínico IA (Dictado por Voz -> SOAP + CIE-10) y haz clic en Estructurar SOAP.',
                    'Revisa los campos generados o aplica una plantilla clínica y pulsa Firmar y bloquear para sellar la evolución con hash SHA-256 inmutable (modificable solo vía fe de erratas).',
                ],
                'screenshots' => [
                    ['file' => '07-odontograma.jpg', 'caption' => 'Odontograma anatómico FDI/Universal interactivo con registro por caras dentales, comparación histórica y notas SOAP.'],
                ],
            ],
            [
                'id' => 'perio-ortodoncia-presupuestos',
                'number' => '06',
                'title' => 'Periodontograma, Ortodoncia y Presupuestos por Fases',
                'category' => 'Especialidades y Planes',
                'roles' => ['Administrador', 'Odontólogo'],
                'summary' => 'Sondaje periodontal de 6 sitios por pieza con índice automático de sangrado y placa, control evolutivo de ortodoncia y presupuestos clínicos organizados por fases con descuento global.',
                'tip' => 'Al marcar cada procedimiento del presupuesto como realizado en la ficha del paciente, la información alimenta automáticamente los reportes de producción y comisiones del odontólogo.',
                'steps' => [
                    'En la pestaña Perio y Ortodoncia registra profundidades de sondaje (6 puntos), recesión, movilidad, furcación, sangrado (BOP %) y placa.',
                    'En la sección inferior documenta cada control ortodóntico (etapa, arcos superior e inferior, elásticos, higiene e indicaciones).',
                    'En la pestaña Tratamientos crea planes de tratamiento por fases (Preventiva, Rehabilitación, Ortodoncia, Cirugía), añade ítems del tarifario y actualiza su ejecución clínica.',
                ],
                'screenshots' => [
                    ['file' => '08-periodonto-ortodoncia.jpg', 'caption' => 'Registro de periodontograma de 6 sitios por pieza y seguimiento de controles de ortodoncia.'],
                    ['file' => '09-presupuestos.jpg', 'caption' => 'Planes de tratamiento y presupuestos detallados por fases con estado de avance en tiempo real.'],
                ],
            ],
            [
                'id' => 'visor-rx-documentos',
                'number' => '07',
                'title' => 'Visor Radiográfico IA (mm) y Documentos Firmados',
                'category' => 'Imagenología y Legal',
                'roles' => ['Administrador', 'Odontólogo'],
                'summary' => 'Almacenamiento privado autenticado de imágenes clínicas, visor radiográfico calibrable en milímetros con asistencia Rayos X IA (+ Pin) y consentimientos o recetas firmadas en pantalla.',
                'tip' => 'Dentro del Visor Radiográfico, pulsa Analizar IA en la sección Rayos X IA (Asistencia) para obtener hallazgos preliminares y fijarlos con + Pin sobre la placa como apoyo diagnóstico.',
                'steps' => [
                    'En la pestaña Imágenes sube radiografías periapicales, panorámicas o fotografías intraorales/Antes y Después (almacenadas en disco privado autenticado).',
                    'Pulsa Visor Rx / Medir para ajustar Brillo, Contraste o Invertir Escala (Negativo Rx), calibrar una medida de referencia en mm (Calibrar -> Medir) y colocar pines manuales.',
                    'Usa Analizar IA dentro de Rayos X IA (Asistencia) para pre-evaluar zonas de interés y agregar pines sugeridos con + Pin antes de guardar anotaciones y filtros.',
                    'En Documentos genera consentimientos informados, recetas o certificados desde plantillas, recaba la firma táctil del paciente y descarga el PDF oficial.',
                ],
                'screenshots' => [
                    ['file' => '10-visor-radiografico.jpg', 'caption' => 'Visor radiográfico diagnóstico con calibración milimétrica (mm), filtros, pines y módulo Rayos X IA.'],
                    ['file' => '11-documentos.jpg', 'caption' => 'Emisión de recetas médicas y consentimientos informados con firma digital táctil e impresión PDF.'],
                ],
            ],
            [
                'id' => 'agenda-whatsapp',
                'number' => '08',
                'title' => 'Agenda por Sillón, Recordatorio WhatsApp y Reserva Link',
                'category' => 'Agenda y Recepción',
                'roles' => ['Administrador', 'Odontólogo', 'Asistente'],
                'summary' => 'Calendario clínico sin traslapes por sillón y profesional, reprogramación mediante arrastrar y soltar, recordatorios WhatsApp con enlace público de confirmación y botón de copia para Reserva Online 24/7.',
                'tip' => 'Desde la barra superior de Agenda, pulsa Link Reserva Online 24/7 para copiar el enlace público del consultorio y compartirlo en tu web, Instagram o WhatsApp Business.',
                'steps' => [
                    'En Agenda filtra por día, semana, mes, odontólogo o sillón dental y crea o arrastra citas entre días (con bloqueo automático de conflictos de horario).',
                    'Actualiza el estado de cada cita en el flujo clínico: Programada -> Confirmada -> En sala -> Atendida (o No asistió).',
                    'Haz clic en Recordar WhatsApp dentro de cualquier cita para abrir WhatsApp con mensaje prearmado y enlace público único (/confirmar-cita) sin requerir contraseña al paciente.',
                    'Revisa también las reservas entrantes desde el portal público 24/7 que quedan registradas automáticamente en Agenda sin cruce de horarios.',
                ],
                'screenshots' => [
                    ['file' => '12-agenda.jpg', 'caption' => 'Agenda clínica por sillón y profesional con control de estados, arrastrar/soltar y recordatorio WhatsApp en 1 clic.'],
                ],
            ],
            [
                'id' => 'tratamientos-laboratorio',
                'number' => '09',
                'title' => 'Catálogo de Tratamientos y Laboratorio Dental',
                'category' => 'Tarifario y Prótesis',
                'roles' => ['Administrador', 'Odontólogo', 'Asistente (solo Lab)'],
                'summary' => 'Tarifario maestro editable de procedimientos odontológicos vinculado al odontograma y seguimiento completo de trabajos en laboratorio dental externo con alertas por fecha de cita.',
                'tip' => 'Cuando un trabajo de laboratorio aún no figura como recibido faltando menos de 48 horas para la cita del paciente, aparece una alerta preventiva automática en el panel Inicio.',
                'steps' => [
                    'En Tratamientos crea, edita o desactiva procedimientos especificando código, categoría, precio base, duración estimada en minutos y hallazgo del odontograma asociado.',
                    'En Laboratorio registra órdenes para prótesis, coronas, incrustaciones o alineadores con paciente, pieza, colorimetría, taller externo, costo y fecha comprometida.',
                    'Actualiza cada etapa (Por enviar -> En laboratorio -> Recibido -> Probado -> Instalado / Rehacer) para mantener al equipo clínico sincronizado.',
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
                'summary' => 'Bitácora sanitaria de esterilización en autoclave con controles físicos, químicos y lectura biológica de esporas, junto al Kardex de insumos críticos con lotes, vencimientos y alertas de stock.',
                'tip' => 'Los insumos críticos bajo stock mínimo o próximos a vencer generan avisos automáticos tanto en Inicio como en el panel de Contralor IA.',
                'steps' => [
                    'En Esterilización / Kardex registra cada ciclo de autoclave con número de lote, temperatura, presión, tiempo y lectura química.',
                    'Completa el resultado del control biológico por esporas (Pendiente / Negativo aprobado / Positivo alerta) una vez concluida la incubación.',
                    'Administra en Kardex materiales críticos (anestésicos, resinas, suturas, implantes) registrando ingresos por compra y salidas asociadas a atención o paciente.',
                ],
                'screenshots' => [
                    ['file' => '15-esterilizacion.jpg', 'caption' => 'Registro de ciclos de autoclave con indicadores químicos/biológicos y Kardex de insumos críticos.'],
                ],
            ],
            [
                'id' => 'caja-sunat-cuotas',
                'number' => '11',
                'title' => 'Caja Diaria, Tickets Térmicos, Cuotas y SUNAT UBL 2.1',
                'category' => 'Finanzas y Facturación',
                'roles' => ['Administrador', 'Contador', 'Asistente (Caja)'],
                'summary' => 'Gestión financiera integral: cobros multipago, tickets térmicos 80/58 mm, comprobantes electrónicos XML SUNAT UBL 2.1 con consulta RUC, financiamiento por cuotas con morosidad y cierre diario.',
                'tip' => 'Al cerrar caja o revisar cualquier cierre histórico puedes imprimir en formato térmico 80 mm o 58 mm el arqueo consolidado desglosado por cada medio de pago.',
                'steps' => [
                    'En Caja y cobros o desde la pestaña Pagos y Saldo del paciente, registra cobros indicando medio de pago (Efectivo, Yape/Plin, Tarjeta, Transferencia) y tipo de comprobante (Recibo, Boleta o Factura con RUC apiperu.dev).',
                    'Descarga al instante el comprobante en PDF A4, Ticket Térmico 80/58 mm o archivo XML SUNAT UBL 2.1 con código QR.',
                    'Genera cronogramas de cuotas de financiamiento y controla alertas automáticas de morosidad desde la vista de Caja y Contralor IA.',
                    'Al concluir la jornada ejecuta Cerrar caja del día e imprime el reporte térmico consolidado por medio de pago.',
                ],
                'screenshots' => [
                    ['file' => '16-caja.jpg', 'caption' => 'Caja diaria con desglose por medios de pago, tickets térmicos 80/58 mm, cuotas vencidas y cierre diario.'],
                ],
            ],
            [
                'id' => 'reportes-gerenciales-ia',
                'number' => '12',
                'title' => 'Reportes Gerenciales, Comisiones y Reportes IA',
                'category' => 'Finanzas y Analítica',
                'roles' => ['Administrador', 'Odontólogo', 'Asistente', 'Contador'],
                'summary' => 'Indicadores financieros y clínicos por rango de fechas, liquidación de comisiones por odontólogo, exportación a Excel (CSV) y Analítica Conversacional IA (Reportes IA) para consultar datos en lenguaje natural.',
                'tip' => 'En Reportes usa los accesos sugeridos de Analítica Conversacional IA (Reportes IA) como "¿Cuánto recaudamos y cuál fue el medio de pago líder?" o "¿Cuánto corresponde de comisión a cada odontólogo?" para obtener respuestas inmediatas.',
                'steps' => [
                    'Ingresa a Reportes y define el rango de fechas (Desde / Hasta) para actualizar los KPIs de ingresos, pacientes nuevos, tasa de inasistencia y cuentas por cobrar.',
                    'En la tarjeta Analítica Conversacional IA (Reportes IA) escribe una pregunta en lenguaje natural o haz clic en cualquiera de las consultas rápidas y pulsa Preguntar a IA.',
                    'Revisa la producción y porcentaje de comisión por profesional en Producción y Comisiones por Odontólogo y las deudas pendientes por paciente.',
                    'Pulsa Exportar a Excel (CSV) para descargar el consolidado del periodo seleccionado.',
                ],
                'screenshots' => [
                    ['file' => '17-reportes.jpg', 'caption' => 'Reportes gerenciales con KPIs, recaudación por método, comisiones médicas y exportación CSV junto a Reportes IA.'],
                ],
            ],
            [
                'id' => 'contralor-ia',
                'number' => '13',
                'title' => 'Contralor IA (Auditoría Clínica, Legal y Financiera)',
                'category' => 'Inteligencia Artificial',
                'roles' => ['Administrador', 'Odontólogo', 'Contador (solo lectura)'],
                'summary' => 'Auditor inteligente continuo inspirado en plataformas líderes LATAM: calcula el porcentaje de cumplimiento del consultorio y detecta fuga de ingresos y brechas médico-legales con botones de acción directa.',
                'tip' => 'Revisa Contralor IA al inicio o cierre de cada día filtrando por Prioridad Alta para resolver primero planes sin consentimiento firmado y evoluciones SOAP pendientes de sellado SHA-256.',
                'steps' => [
                    'Abre Contralor IA en el menú lateral para visualizar el indicador general de Cumplimiento (%) sobre las fichas auditadas.',
                    'Analiza las tarjetas superiores: Consentimientos faltantes, Notas SOAP sin firmar, Historias HCE incompletas y Potencial sin presupuestar (fuga de ingresos en odontograma).',
                    'Filtra por Todas, Prioridad Alta, Prioridad Media o Preventivas / Stock (cuotas vencidas, entregas de laboratorio e insumos bajos).',
                    'Ejecuta la corrección en 1 clic desde cada tarjeta de brecha usando Emitir y firmar, Convertir a presupuesto, Cobrar / Recordar o Abrir Kardex.',
                ],
                'screenshots' => [
                    ['file' => '22-contralor-ia.jpg', 'caption' => 'Panel Contralor IA con puntuación de cumplimiento normativo, fuga de ingresos y acciones correctivas en 1 clic.'],
                ],
            ],
            [
                'id' => 'crm-retencion-reserva-online',
                'number' => '14',
                'title' => 'CRM Odontológico WhatsApp IA y Reserva Online 24/7',
                'category' => 'Inteligencia Artificial y Captación',
                'roles' => ['Administrador', 'Odontólogo', 'Asistente', 'Contador (lectura)'],
                'summary' => 'Embudo comercial y de fidelización para pacientes (desde nuevo contacto hasta mantenimiento preventivo de 6 meses y reactivación), asistente IA para redactar WhatsApp y portal público de autogestión de citas 24/7.',
                'tip' => 'Desde CRM y Retención o Agenda puedes copiar el enlace /reservar-cita con Copiar link Reserva Online 24/7; el portal consulta disponibilidad real de horarios y asigna sillón libre automáticamente.',
                'steps' => [
                    'Accede a CRM y Retención para visualizar el tablero por etapas: Nuevo contacto, Presupuesto pendiente, En tratamiento, Mantenimiento 6m y Reactivación.',
                    'Cambia la Etapa CRM de cualquier paciente directamente desde la tabla según el avance de su presupuesto o control semestral.',
                    'Haz clic en Redactar IA en la columna Asistente WhatsApp IA para generar un mensaje personalizado acorde a su deuda, presupuesto abierto o campaña de retención y enviarlo por WhatsApp.',
                    'Comparte el portal público /reservar-cita para que pacientes nuevos o recurrentes elijan servicio, odontólogo, fecha y horario disponible en tiempo real sin necesidad de registro.',
                ],
                'screenshots' => [
                    ['file' => '23-crm-retencion.jpg', 'caption' => 'CRM Odontológico con embudo por etapas, detección de campañas sugeridas y botón Redactar IA para WhatsApp.'],
                    ['file' => '24-reserva-online.jpg', 'caption' => 'Portal público de Reserva de Citas Online 24/7 conectado en vivo con la disponibilidad de sillones y odontólogos.'],
                ],
            ],
            [
                'id' => 'auditoria-ajustes-seguridad',
                'number' => '15',
                'title' => 'Auditoría, Ajustes (Motor IA / RENIEC), Equipo y Respaldos',
                'category' => 'Administración y Gobierno',
                'roles' => ['Administrador', 'Contador (solo Auditoría)'],
                'summary' => 'Control integral del sistema: bitácora normativa e intentos de acceso, identidad/logotipo/impresoras térmicas, integración apiperu.dev, motor IA (Local / Gemini / OpenAI), protección del Administrador y respaldos portables compatibles con PHP 8.3.',
                'tip' => 'El rol Administrador del consultorio lleva la etiqueta Rol protegido (inmutable en servidor e interfaz). Los respaldos JSON incluyen firma HMAC-SHA256 y funcionan en hostings PHP 8.3 como BanaHosting/cPanel incluso si mysqldump está restringido.',
                'steps' => [
                    'En Auditoría supervisa creaciones, firmas SHA-256, exportaciones PDF/FHIR, restauraciones e inicios de sesión exitosos o fallidos con usuario, IP y fecha.',
                    'En Ajustes > Consultorio y Logo e Impresiones y Formatos configura logotipo, RUC, sillones, notación FDI/Universal, auto-bloqueo y ticket térmico 80/58 mm.',
                    'En Ajustes > RENIEC, IA y Reserva guarda el token Bearer cifrado de apiperu.dev (con probador en vivo), selecciona el Proveedor de IA Clínica (Motor Clínico IA Integrado sin costo, Google Gemini API u OpenAI GPT-4o mini) y activa o pausa el portal /reservar-cita.',
                    'En Ajustes > Equipo y Roles gestiona usuarios, colegiaturas y comisiones; en Respaldo y Reinicio descarga respaldos JSON HMAC-SHA256 o volcados MySQL y administra 2FA/Passkeys en tu perfil.',
                ],
                'screenshots' => [
                    ['file' => '18-auditoria.jpg', 'caption' => 'Bitácora de auditoría normativa con usuario, acción, IP y resumen trazable.'],
                    ['file' => '19-ajustes.jpg', 'caption' => 'Centro de ajustes del consultorio: logotipo, sillones, impresiones térmicas e integraciones RENIEC, IA y Reserva.'],
                    ['file' => '20-equipo-roles.jpg', 'caption' => 'Gestión del equipo odontológico con protección inmutable del rol Administrador del consultorio.'],
                    ['file' => '21-perfil-seguridad.jpg', 'caption' => 'Ajustes personales de cuenta, contraseña, autenticación en dos pasos (2FA) y Passkeys biométricas.'],
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
            ['module' => 'Inicio, Búsqueda ⌘K y Manual (Web / PDF)', 'admin' => 'Completo', 'dentist' => 'Completo', 'assistant' => 'Completo', 'accountant' => 'Completo'],
            ['module' => 'Pacientes, DNI RENIEC e IA Pre-Consulta', 'admin' => 'Crear/Editar/Ver + IA', 'dentist' => 'Completo + IA', 'assistant' => 'Admisión/Ver ficha', 'accountant' => 'Solo lectura reportes'],
            ['module' => 'HCE, Odontograma, Perio, Copiloto SOAP Voz y Rx IA', 'admin' => 'Completo + Firma + IA', 'dentist' => 'Completo + Firma + IA', 'assistant' => 'Restringido (solo vista ficha)', 'accountant' => 'Sin acceso'],
            ['module' => 'Agenda, WhatsApp 1-Clic y Reserva Online 24/7', 'admin' => 'Completo', 'dentist' => 'Completo', 'assistant' => 'Completo', 'accountant' => 'Sin acceso'],
            ['module' => 'Catálogo de Tratamientos', 'admin' => 'CRUD Completo', 'dentist' => 'CRUD Completo', 'assistant' => 'Consulta', 'accountant' => 'Consulta'],
            ['module' => 'Laboratorio Dental y Autoclave / Kardex', 'admin' => 'Completo', 'dentist' => 'Completo', 'assistant' => 'Completo', 'accountant' => 'Sin acceso'],
            ['module' => 'Caja Diaria, Cierre Térmico, Cuotas y SUNAT UBL', 'admin' => 'Completo', 'dentist' => 'Solo vista ficha', 'assistant' => 'Cobros y Cierre', 'accountant' => 'Completo'],
            ['module' => 'Reportes Financieros, Reportes IA y Comisiones', 'admin' => 'Completo + CSV/IA', 'dentist' => 'Completo + CSV/IA', 'assistant' => 'Completo + CSV/IA', 'accountant' => 'Completo + CSV/IA'],
            ['module' => 'Contralor IA (Auditoría Clínica/Legal/Financiera)', 'admin' => 'Completo', 'dentist' => 'Completo', 'assistant' => 'Sin acceso', 'accountant' => 'Lectura'],
            ['module' => 'CRM Retención WhatsApp IA y Campañas', 'admin' => 'Completo', 'dentist' => 'Completo', 'assistant' => 'Completo', 'accountant' => 'Lectura / Campañas'],
            ['module' => 'Bitácora de Auditoría Normativa', 'admin' => 'Completo', 'dentist' => 'Sin acceso', 'assistant' => 'Sin acceso', 'accountant' => 'Lectura'],
            ['module' => 'Ajustes (Logo, RENIEC, Motor IA, Reserva 24/7, Respaldos)', 'admin' => 'Exclusivo (Rol protegido)', 'dentist' => 'Sin acceso', 'assistant' => 'Sin acceso', 'accountant' => 'Sin acceso'],
        ];
    }
}
