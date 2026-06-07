#!/bin/bash

set -e

ORIGEN="/home/amr/Descargas"
DESTINO="/var/www/html/vlexplosion.php.Dic.2025"

echo "Creando backup..."

BACKUP="$DESTINO/backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP"

cp "$DESTINO/app/views/vinyls/create.php" "$BACKUP/" 2>/dev/null || true
cp "$DESTINO/app/Controllers/VinylController.php" "$BACKUP/" 2>/dev/null || true
cp "$DESTINO/app/Models/Vinyl.php" "$BACKUP/" 2>/dev/null || true

echo "Copiando archivos..."

cp "$ORIGEN/create.php" \
   "$DESTINO/app/views/vinyls/create.php"

cp "$ORIGEN/VinylController.php" \
   "$DESTINO/app/Controllers/VinylController.php"

cp "$ORIGEN/Vinyl.php" \
   "$DESTINO/app/Models/Vinyl.php"

mkdir -p "$DESTINO/public/uploads/covers"

echo
echo "======================================="
echo "Instalación completada"
echo "Backup: $BACKUP"
echo "======================================="
