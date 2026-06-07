#!/bin/bash

set -e

ORIGEN="$HOME/Descargas/vinilos/vlexplosion_caratulas"
DESTINO="/var/www/html/vlexplosion.php.Dic.2025"

echo "Creando copia de seguridad..."

mkdir -p "$DESTINO/backup_$(date +%Y%m%d_%H%M%S)"

BACKUP=$(ls -dt "$DESTINO"/backup_* | head -1)

cp "$DESTINO/app/Models/Vinyl.php" "$BACKUP/"
cp "$DESTINO/app/Controllers/VinylController.php" "$BACKUP/"
cp "$DESTINO/app/views/vinyls/index.php" "$BACKUP/"
cp "$DESTINO/app/views/vinyls/show.php" "$BACKUP/"

echo "Copiando archivos modificados..."

cp "$ORIGEN/Vinyl.php" \
   "$DESTINO/app/Models/Vinyl.php"

cp "$ORIGEN/VinylController.php" \
   "$DESTINO/app/Controllers/VinylController.php"

cp "$ORIGEN/index.php" \
   "$DESTINO/app/views/vinyls/index.php"

cp "$ORIGEN/show.php" \
   "$DESTINO/app/views/vinyls/show.php"

mkdir -p "$DESTINO/public/assets/images"

cp "$ORIGEN/default-cover.webp" \
   "$DESTINO/public/assets/images/default-cover.webp"

echo "Hecho."
echo "Backup guardado en: $BACKUP"
