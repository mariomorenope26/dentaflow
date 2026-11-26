#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VERSION="${1:-v1.0}"
OUT_DIR="${ROOT_DIR}/dist"
STAGE_DIR="${OUT_DIR}/.stage-dentaflow-${VERSION}"
ZIP_PATH="${OUT_DIR}/dentaflow-instalador-${VERSION}.zip"

echo "==> Empaquetando DentaFlow (${VERSION}) con instalador PHP..."

cd "${ROOT_DIR}"

if [ ! -f "public/build/manifest.json" ]; then
    echo "==> Compilando assets frontend de producción..."
    npm run build
fi

rm -rf "${STAGE_DIR}" "${ZIP_PATH}"
mkdir -p "${STAGE_DIR}" "${OUT_DIR}"

rsync -a \
    --exclude='.git' \
    --exclude='.github' \
    --exclude='.env' \
    --exclude='.env.*' \
    --exclude='!.env.example' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='dist' \
    --exclude='tests' \
    --exclude='.phpunit*' \
    --exclude='public/hot' \
    --exclude='public/fonts-manifest.dev.json' \
    --exclude='public/storage' \
    --exclude='database/*.sqlite*' \
    --exclude='storage/logs/*.log' \
    --exclude='storage/app/public/*' \
    --exclude='storage/app/private/*' \
    --exclude='storage/framework/cache/data/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*.php' \
    --exclude='storage/installed.lock' \
    --exclude='bootstrap/cache/*.php' \
    --exclude='.DS_Store' \
    "${ROOT_DIR}/" "${STAGE_DIR}/"

# Asegurar estructura limpia de carpetas en storage y cache
mkdir -p \
    "${STAGE_DIR}/storage/app/public" \
    "${STAGE_DIR}/storage/app/private" \
    "${STAGE_DIR}/storage/framework/cache/data" \
    "${STAGE_DIR}/storage/framework/sessions" \
    "${STAGE_DIR}/storage/framework/views" \
    "${STAGE_DIR}/storage/logs" \
    "${STAGE_DIR}/bootstrap/cache"

cp "${ROOT_DIR}/.env.example" "${STAGE_DIR}/.env.example"

echo "==> Instalando dependencias PHP de producción (--no-dev) en staging..."
composer install \
    --working-dir="${STAGE_DIR}" \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress

# Colocar el instalador PHP en public/install.php y raíz/install.php
cp "${ROOT_DIR}/installer/install.php" "${STAGE_DIR}/public/install.php"
cp "${ROOT_DIR}/installer/install.php" "${STAGE_DIR}/install.php"

cat <<'EOF' > "${STAGE_DIR}/INSTALACION-RAPIDA.txt"
DentaFlow - Guía Rápida de Instalación en Servidor PHP (BanaHosting / cPanel PHP 8.3+)
=====================================================================================

Requisitos: PHP 8.3+ (recomendado PHP 8.3 en cPanel -> Select PHP Version), MySQL/MariaDB o SQLite,
extensiones pdo, pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, gd, zip, curl, fileinfo.
Las dependencias vendor/ de este paquete están compiladas para PHP 8.3+.

Pasos:
1. En BanaHosting / cPanel, entra a "Select PHP Version", elige PHP 8.3 y verifica las extensiones.
2. Sube y descomprime este archivo ZIP en tu hosting (por ejemplo en ~/dentaflow o una subcarpeta).
3. Configura el DocumentRoot de tu dominio o subdominio apuntando a la carpeta `public/` (recomendado por seguridad).
4. En cPanel -> "Bases de datos MySQL", crea una base de datos y usuario con todos los privilegios (usa host `localhost` y deja desmarcada la casilla de crear BD automática).
5. Verifica permisos de escritura en la raíz (para `.env`), `storage/` y `bootstrap/cache/`.
6. Abre en tu navegador: https://tu-dominio.com/install.php
7. Completa el asistente y, al finalizar, pulsa el botón para eliminar `install.php` automáticamente.

Soporte Técnico: Grupo JMSPERU 2026 :: WhatsApp +51 959199368
EOF

echo "==> Comprimiendo paquete ZIP..."
cd "${STAGE_DIR}"
zip -rq "${ZIP_PATH}" .

rm -rf "${STAGE_DIR}"

echo "==> Paquete creado: ${ZIP_PATH}"
ls -lh "${ZIP_PATH}"
