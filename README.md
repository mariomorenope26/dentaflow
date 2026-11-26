# DentaFlow · Historia Clínica Electrónica Odontológica

Sistema web profesional para la gestión integral de **consultorio odontológico único**, diseñado bajo estándares clínicos internacionales (**HL7 FHIR R4**, **ISO/EN 13606 HCE**, **ISO 3950 / FDI**, **CIE-10 OMS**, **SUNAT UBL 2.1**) y protección de datos de salud (**Ley N.º 29733**).

## Características principales

- **Historia Clínica Electrónica Homologada:** anamnesis por sistemas, riesgo anestésico ASA I–V, antropometría con IMC automático, examen estomatognático, notas SOAP firmadas con sellado criptográfico SHA-256 y exportación de bundle **HL7 FHIR R4**.
- **Odontograma y Especialidades:** odontograma anatómico interactivo (FDI / Universal, adulto, mixto y temporal), periodontograma de 6 sitios con cálculo automático de sangrado, y controles ortodónticos.
- **Visor Radiográfico Medible (mm):** calibración milimétrica para conductometría y altura ósea, ajustes de brillo/contraste/negativo, pines de hallazgo y comparador Antes/Después.
- **Agenda, WhatsApp y Laboratorio:** recordatorios de cita en 1 clic con confirmación o solicitud de reprogramación pública sin inicio de sesión y trazabilidad de trabajos en laboratorio dental.
- **Bioseguridad y Kardex:** bitácora de ciclos de autoclave (controles químicos/biológicos) e inventario crítico con lotes y vencimientos.
- **Caja Térmica, SUNAT y Financiamiento:** cierre de caja y tickets térmicos 80/58 mm por medios de pago, comprobantes electrónicos XML UBL 2.1 con QR, consulta RENIEC DNI y SUNAT RUC vía `apiperu.dev`, plan de cuotas y liquidación de honorarios.
- **Respaldo y Seguridad:** copias de seguridad portables JSON firmadas (HMAC-SHA256), exportación MySQL, protección inmutable del rol **Administrador del consultorio**, bloqueo automático por inactividad y registro completo de auditoría.

## Requisitos de servidor (Producción · BanaHosting / cPanel PHP 8.3+)

- **PHP 8.3 – 8.5** (paquete `composer.lock` y `vendor/` compilados con plataforma mínima **PHP 8.3.0**; en BanaHosting cPanel seleccionar **PHP 8.3** en _Select PHP Version_ con `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`, `curl`, `zip`)
- **MySQL 8.0+ / MariaDB 10.6+** o **SQLite 3**
- HTTPS recomendado en producción (WebAuthn / Passkeys y PWA requieren contexto seguro)

## Instalación rápida con el Instalador PHP (Paquete ZIP)

1. Descarga o genera el paquete ZIP (`dentaflow-instalador-v1.0.zip`) usando:
    ```bash
    bash scripts/package-installer.sh v1.0
    ```
2. Descomprime el ZIP en el servidor y apunta el `DocumentRoot` a `public/`.
3. Ingresa desde el navegador a `https://tu-dominio.com/install.php`.
4. Sigue el asistente guiado (verificación de requisitos, base de datos, datos del consultorio y creación del Administrador principal).
5. Al concluir, elimina `install.php` desde el botón integrado de limpieza.

## Instalación para desarrollo local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
```

---

Desarrollado por **Grupo JMSPERU 2026** :: [Contacto WhatsApp 959199368](https://wa.me/51959199368?text=Hola%20Grupo%20JMSPERU%2C%20deseo%20informaci%C3%B3n%20sobre%20DentaFlow.)
