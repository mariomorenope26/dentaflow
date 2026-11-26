# Sistema Web de Historias Clínicas para Consultorios Odontológicos

> Documento de planificación funcional y de producto. No incluye código fuente.
> Versión 1.0 · Noviembre 2026

---

## 1. Resumen del proyecto

Plataforma web (SaaS) para que consultorios y clínicas odontológicas gestionen pacientes, historias clínicas, **odontograma interactivo**, tratamientos, citas y pagos desde cualquier dispositivo. Es instalable como **PWA** y está pensada para un uso simple, rápido y con pocos clics.

**Propuesta de valor**

- Odontograma visual, rápido de llenar y con historial de cambios.
- Historia clínica completa, ordenada y lista para imprimir o exportar.
- Funciona en tablet, celular y PC, e incluso con conexión inestable.
- Planes escalables: Básico, Profesional y Premium.

---

## 2. Objetivos

| Objetivo                                              | Indicador de éxito                 |
| ----------------------------------------------------- | ---------------------------------- |
| Registrar una atención completa en menos de 5 minutos | Tiempo medio de registro           |
| Que un odontólogo nuevo lo use sin capacitación larga | Onboarding menor a 30 minutos      |
| Reducir el papel y las fichas físicas                 | Consultorios 100 % digitales       |
| Disponibilidad en el sillón dental (tablet)           | Uso táctil fluido                  |
| Seguridad de datos de salud                           | Cumplimiento normativo y auditoría |

---

## 3. Usuarios y roles

| Rol                               | Qué puede hacer                                                               |
| --------------------------------- | ----------------------------------------------------------------------------- |
| **Administrador del consultorio** | Configura el consultorio, usuarios, planes, sedes, precios y reportes         |
| **Odontólogo**                    | Historia clínica, odontograma, diagnósticos, tratamientos, recetas, evolución |
| **Asistente / Recepción**         | Agenda, registro de pacientes, cobros, recordatorios                          |
| **Contador / Caja** (opcional)    | Pagos, comprobantes y reportes financieros                                    |
| **Paciente** (Premium)            | Ve citas, presupuestos, pagos, documentos y consentimientos                   |
| **Superadministrador (CodeFast)** | Gestiona clientes, licencias, planes y soporte de la plataforma               |

Permisos por rol configurables y con registro de auditoría.

---

## 4. Planes de la plataforma

### Plan Básico

- 1 odontólogo, hasta 300 pacientes
- Ficha del paciente y filiación
- Historia clínica básica
- Odontograma estándar (adulto y niño)
- Agenda simple
- Exportar a PDF

### Plan Profesional

Todo lo del Básico, más:

- Hasta 5 usuarios y pacientes ilimitados
- Odontograma avanzado (inicial y evolutivo, historial por fecha)
- Planes de tratamiento y presupuestos
- Control de pagos y deudas
- Recetas médicas e indicaciones
- Consentimientos informados digitales con firma en pantalla
- Galería de imágenes y radiografías
- Recordatorios de cita por WhatsApp o correo
- Reportes básicos

### Plan Premium

Todo lo del Profesional, más:

- Multisede y usuarios ilimitados
- Portal del paciente
- Facturación electrónica SUNAT (boleta y factura)
- Inventario y control de insumos
- Comisiones por profesional
- Reportes avanzados y panel gerencial
- Periodontograma y ortodoncia (seguimiento de controles)
- Fotos clínicas antes y después con comparador
- Respaldos automáticos extendidos y exportación total de datos
- Soporte prioritario
- Marca personalizada (logo y colores del consultorio en documentos)

---

## 5. Módulos funcionales

### 5.1 Pacientes

- Registro con datos personales, contacto, ocupación, apoderado (si es menor) y seguro.
- Búsqueda rápida por nombre, DNI o historia clínica.
- Número de historia clínica autogenerado.
- Etiquetas (ortodoncia, niño, crónico, VIP).
- Línea de tiempo con todo lo ocurrido con el paciente.

### 5.2 Historia clínica

- **Anamnesis:** motivo de consulta, enfermedad actual, antecedentes médicos, alergias, medicación, hábitos, embarazo, antecedentes odontológicos.
- **Alertas médicas visibles** en toda la ficha (alergias, anticoagulantes, diabetes, hipertensión).
- **Examen clínico:** extraoral, intraoral, ATM, tejidos blandos, periodonto, oclusión.
- **Diagnóstico** presuntivo y definitivo (con CIE-10 opcional).
- **Evolución:** nota por cada atención, con fecha, hora, profesional y procedimiento.
- Plantillas de notas para llenar más rápido.
- Bloqueo de registros firmados (no se editan; solo se agregan correcciones con trazabilidad).
- Impresión o exportación a PDF con el formato del consultorio.

### 5.3 Odontograma (módulo central)

**Tipos**

- Adulto (32 piezas), niño (20 temporales) y mixto.
- Numeración **FDI** (por defecto), con opción de Universal.

**Interacción**

- Clic o toque en cada pieza y en cada cara (vestibular, lingual/palatina, mesial, distal, oclusal/incisal).
- Selección de hallazgo desde una barra lateral o flotante, con herramientas rápidas.
- Aplicar a varias piezas a la vez (por ejemplo, prótesis fija o puente).
- Deshacer, rehacer y borrar.
- Zoom y desplazamiento en tablet.

**Hallazgos y condiciones (ejemplos)**

- Caries, obturación, sellante, corona, endodoncia, implante, prótesis fija y removible.
- Ausente, extracción indicada, fractura, desgaste, movilidad, retenido, impactado.
- Diastema, giroversión, migración, ortodoncia (brackets).
- Lesión periapical, recesión, tratamiento realizado frente a tratamiento por realizar.

**Convención visual**

- Código claro: **azul = realizado/buen estado**, **rojo = patología o por realizar**, según norma usual.
- Leyenda siempre visible y configurable.

**Historial**

- Odontograma inicial (primera consulta) y evolutivo (cada atención).
- Comparación entre fechas lado a lado.
- Cada cambio queda registrado con fecha, profesional y motivo.

**Conexión con el resto del sistema**

- Cada hallazgo puede convertirse en un **tratamiento propuesto** con un clic.
- Al realizar el tratamiento, se actualiza el odontograma y se registra el cobro.

**Salida**

- Impresión y PDF del odontograma con datos del paciente.

### 5.4 Plan de tratamiento y presupuesto

- Catálogo de tratamientos con precios y categorías.
- Armado del plan por fases y prioridad.
- Presupuesto con descuentos, aprobación del paciente y vigencia.
- Seguimiento: pendiente, en curso, realizado.
- Presupuesto enviable por WhatsApp o correo, y exportable a PDF.

### 5.5 Agenda y citas

- Vista diaria, semanal y mensual, por profesional y por sillón.
- Arrastrar y soltar para reprogramar.
- Estados: reservada, confirmada, en sala, atendida, cancelada, no asistió.
- Recordatorios automáticos.
- Lista de espera.
- Cita rápida desde la ficha del paciente.

### 5.6 Caja, pagos y deudas

- Cobros parciales o totales, con varios medios de pago (efectivo, tarjeta, Yape/Plin, transferencia).
- Cuenta corriente del paciente (saldo y deuda).
- Cierre de caja diario.
- Comprobantes de pago; en Premium, boletas y facturas electrónicas.

### 5.7 Documentos clínicos

- Consentimiento informado por procedimiento, con firma digital en pantalla.
- Recetas y órdenes de exámenes.
- Certificados e informes.
- Plantillas editables por el consultorio.

### 5.8 Imágenes y archivos

- Carga de radiografías, fotos intraorales y documentos.
- Organización por fecha y tipo.
- Visor con zoom y comparador antes y después.
- Captura directa desde la cámara del celular o tablet.

### 5.9 Inventario (Premium)

- Insumos y materiales con stock mínimo y alertas.
- Descuento automático por procedimiento (opcional).

### 5.10 Reportes

- Pacientes nuevos, atenciones y tratamientos más frecuentes.
- Ingresos por periodo, profesional y tratamiento.
- Deudas pendientes.
- Tasa de inasistencia.
- Exportación a Excel y PDF.

### 5.11 Portal del paciente (Premium)

- Acceso con enlace o código seguro.
- Ver próximas citas, presupuestos, pagos y documentos.
- Aprobar presupuesto y firmar consentimientos.

---

## 6. Soporte PWA

| Aspecto                      | Comportamiento esperado                                                                                             |
| ---------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| **Instalable**               | Se instala en celular, tablet y PC con ícono propio, sin tienda de apps                                             |
| **Pantalla completa**        | Abre como aplicación, sin barra del navegador                                                                       |
| **Modo sin conexión**        | Se puede consultar la agenda del día, fichas recientes y odontograma, y registrar atenciones con conexión inestable |
| **Sincronización**           | Los cambios hechos sin conexión se envían automáticamente al volver el internet, con aviso claro de estado          |
| **Resolución de conflictos** | Si dos personas editan lo mismo, se muestra una comparación y se elige qué conservar                                |
| **Rapidez**                  | Carga inicial rápida y navegación instantánea gracias al almacenamiento local                                       |
| **Actualizaciones**          | Aviso discreto de "nueva versión disponible" y actualización con un toque                                           |
| **Notificaciones**           | Recordatorios de citas y avisos internos (según permiso del dispositivo)                                            |
| **Cámara**                   | Acceso directo para tomar fotos clínicas                                                                            |
| **Seguridad local**          | Datos sensibles protegidos en el dispositivo y cierre de sesión automático por inactividad                          |

Prioridad de diseño: **tablet en el sillón dental**, con botones grandes y uso con guantes o dedo.

---

## 7. Diseño y experiencia de uso

### 7.1 Principios

1. **Pocos clics:** las acciones frecuentes a un toque.
2. **Una pantalla, una tarea:** sin saturar de información.
3. **Lenguaje claro y profesional**, sin tecnicismos de sistema.
4. **Mismo patrón en todo el sistema:** buscar, ver, editar, guardar.
5. **Guardado automático** en la historia clínica para evitar pérdidas.
6. **Accesible:** buen contraste, textos legibles y botones amplios.

### 7.2 Paleta de colores

Colores de la línea clínica, tranquilos y con buen contraste, para trabajar muchas horas sin cansar la vista.

| Uso                                              | Color                 | Referencia |
| ------------------------------------------------ | --------------------- | ---------- |
| **Primario** (acciones principales, menú activo) | Azul turquesa clínico | `#0E8FA3`  |
| **Primario oscuro** (encabezados, hover)         | Azul petróleo         | `#0A6577`  |
| **Secundario** (detalles y fondos suaves)        | Verde menta           | `#5CC2A7`  |
| **Fondo general**                                | Blanco hielo          | `#F6FAFB`  |
| **Superficies / tarjetas**                       | Blanco                | `#FFFFFF`  |
| **Texto principal**                              | Gris azulado oscuro   | `#1F2D3A`  |
| **Texto secundario**                             | Gris medio            | `#5F6F7C`  |
| **Bordes y divisores**                           | Gris claro            | `#DCE6EA`  |
| **Éxito / realizado**                            | Verde                 | `#2E9E6B`  |
| **Advertencia / pendiente**                      | Ámbar                 | `#F0A92E`  |
| **Error / patología / alerta**                   | Rojo suave            | `#D64545`  |
| **Información**                                  | Azul                  | `#2F7FD1`  |

**Modo oscuro** opcional con la misma lógica, para trabajar en ambientes con poca luz.

**Colores del odontograma:** azul para tratamiento realizado, rojo para patología o tratamiento por realizar, gris para pieza ausente y verde para pieza sana. Siempre acompañados de símbolo o texto (no depender solo del color).

### 7.3 Tipografía y componentes

- Tipografía sans-serif legible (por ejemplo Inter o similar), tamaño base cómodo.
- Esquinas suavemente redondeadas, sombras ligeras, íconos lineales consistentes.
- Botón principal siempre del color primario y en la misma posición.
- Mensajes de confirmación y error claros, sin alarmar.

### 7.4 Navegación

- **Escritorio:** menú lateral con Inicio, Pacientes, Agenda, Tratamientos, Caja, Reportes y Configuración.
- **Móvil y tablet:** barra inferior con los accesos principales y botón de acción rápida (+).
- **Búsqueda global** siempre visible.
- **Inicio / panel:** citas del día, pacientes en sala, pendientes de cobro y accesos directos.

### 7.5 Pantalla de la ficha del paciente

- Cabecera con foto, datos clave y **alertas médicas** destacadas.
- Pestañas: Resumen, Historia, Odontograma, Tratamientos, Pagos, Imágenes, Documentos.
- Acciones rápidas: nueva cita, nueva atención, cobrar, enviar presupuesto.

---

## 8. Arquitectura propuesta

_(Sugerencia, ajustable según preferencias del desarrollo.)_

| Capa                        | Propuesta                                                          |
| --------------------------- | ------------------------------------------------------------------ |
| **Backend**                 | Laravel (PHP) con API y panel web                                  |
| **Base de datos**           | MySQL                                                              |
| **Frontend**                | Interfaz web responsiva con componentes reutilizables              |
| **Odontograma**             | Componente gráfico vectorial interactivo (SVG), ligero y táctil    |
| **PWA**                     | Manifiesto de la aplicación, service worker y almacenamiento local |
| **Archivos**                | Almacenamiento seguro en servidor o nube, con miniaturas           |
| **Tareas en segundo plano** | Cola para recordatorios, respaldos y envíos                        |
| **Multiempresa**            | Cada consultorio con datos aislados (multi-tenant)                 |
| **Mensajería**              | Integración con WhatsApp y correo para recordatorios               |
| **Facturación**             | Integración SUNAT para Premium (por ejemplo vía Greenter)          |

---

## 9. Modelo de datos (entidades principales)

- **Consultorio / Sede**
- **Usuario** y **Rol/Permiso**
- **Paciente** y **Apoderado**
- **Historia clínica** (anamnesis, antecedentes, alertas)
- **Atención / Evolución**
- **Odontograma** (versión, fecha, tipo) y **Hallazgo por pieza y cara**
- **Diagnóstico**
- **Catálogo de tratamientos** y **Precios**
- **Plan de tratamiento** e **Ítems del plan**
- **Presupuesto**
- **Cita** y **Agenda / Sillón**
- **Pago**, **Cuenta corriente** y **Caja**
- **Comprobante electrónico**
- **Documento** (consentimiento, receta, certificado) y **Firma**
- **Archivo / Imagen**
- **Insumo** y **Movimiento de inventario**
- **Plan / Suscripción / Licencia**
- **Registro de auditoría**

---

## 10. Seguridad, privacidad y cumplimiento

Los datos de salud son sensibles. Medidas obligatorias:

- Acceso con usuario y contraseña robusta, y verificación en dos pasos (recomendada).
- Cifrado en tránsito (HTTPS) y datos sensibles cifrados en reposo.
- Permisos por rol y registro de auditoría: quién vio, creó o modificó cada dato.
- Cierre de sesión automático por inactividad.
- Respaldos automáticos diarios y pruebas de restauración.
- Exportación de datos para el consultorio y eliminación bajo solicitud.
- Aislamiento total de datos entre consultorios.
- Consentimiento del paciente para el tratamiento de sus datos.

**Marco normativo en Perú (a verificar con asesoría legal):**

- Ley de Protección de Datos Personales (Ley N.º 29733) y su reglamento.
- Norma técnica de gestión de la historia clínica del MINSA.
- Normas de comprobantes electrónicos de SUNAT (para Premium).

---

## 11. Hoja de ruta por fases

### Fase 1 · Base y MVP

- Estructura del proyecto, autenticación, roles y multi-consultorio.
- Pacientes y historia clínica básica.
- Odontograma estándar (adulto y niño).
- Agenda simple.
- Diseño base con la paleta definida y PWA instalable.

### Fase 2 · Núcleo clínico profesional

- Odontograma avanzado con historial y comparación.
- Evolución por atención.
- Plan de tratamiento y presupuestos.
- Pagos y cuenta corriente.
- Recetas, consentimientos con firma e imágenes clínicas.
- Modo sin conexión y sincronización.

### Fase 3 · Gestión y comunicación

- Recordatorios por WhatsApp y correo.
- Reportes y panel de inicio.
- Plantillas personalizables y marca del consultorio.
- Cierre de caja.

### Fase 4 · Premium

- Multisede.
- Facturación electrónica SUNAT.
- Portal del paciente.
- Inventario y comisiones.
- Periodontograma y ortodoncia.
- Reportes avanzados y soporte prioritario.

### Fase 5 · Lanzamiento y mejora continua

- Pruebas con consultorios piloto.
- Ajustes de usabilidad según retroalimentación.
- Documentación y videos de ayuda.
- Planes comerciales y pasarela de suscripción.

---

## 12. Modelo comercial sugerido

- Suscripción mensual o anual por consultorio (según plan).
- Prueba gratuita de 14 a 30 días.
- Costo adicional por sede o usuario extra.
- Servicios opcionales: migración de fichas físicas, capacitación y soporte técnico.

---

## 13. Criterios de aceptación del MVP

- [ ] Un odontólogo registra un paciente y su historia clínica sin ayuda.
- [ ] El odontograma se llena desde una tablet con el dedo, de forma fluida.
- [ ] Los cambios del odontograma quedan guardados con fecha y profesional.
- [ ] Se puede imprimir o exportar la historia clínica en PDF.
- [ ] La aplicación se instala como PWA en celular y PC.
- [ ] La agenda del día se consulta aun con conexión inestable.
- [ ] Cada rol ve únicamente lo que le corresponde.
- [ ] Los datos de un consultorio nunca son visibles para otro.

---

## 14. Riesgos y mitigaciones

| Riesgo                                       | Mitigación                                                       |
| -------------------------------------------- | ---------------------------------------------------------------- |
| Odontograma complejo y lento en tablets      | Componente vectorial optimizado y pruebas en dispositivos reales |
| Conflictos de sincronización sin conexión    | Reglas claras de resolución y registro de versiones              |
| Pérdida o filtración de datos de salud       | Cifrado, auditoría, respaldos y control de accesos               |
| Resistencia al cambio del personal           | Interfaz simple, plantillas y capacitación breve                 |
| Cambios normativos (SUNAT, datos personales) | Revisión periódica y diseño modular                              |
| Alcance demasiado amplio                     | Entrega por fases y MVP temprano                                 |

---

## 15. Próximos pasos

1. Validar el alcance de cada plan con 2 o 3 odontólogos.
2. Definir el catálogo inicial de hallazgos del odontograma y su simbología.
3. Elaborar prototipos de pantallas (ficha del paciente, odontograma, agenda).
4. Confirmar el stack y la infraestructura de despliegue.
5. Iniciar la Fase 1.

---

_Documento de planificación · Sin código fuente_
