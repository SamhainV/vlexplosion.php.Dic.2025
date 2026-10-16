# Carátulas automáticas y fuentes visuales independientes

El código publicado corresponde al módulo instalado y validado: Cover Art Archive (CAA), avisos de actividad y confirmación, y selección visual para revisiones musicales privadas. No cambia la identidad musical ni actualiza créditos al consultar una portada.

## Componentes

- `CoverArtHttp`: HTTPS con hosts permitidos, validación de IP pública, DNS fijado, redirecciones acotadas y límites de tiempo y tamaño. Requiere `/usr/bin/curl`, `/usr/bin/getent` y `/usr/bin/timeout`.
- `CoverArtClient`: portada frontal aprobada, caché positiva/negativa de 24 horas, Retry-After, bloqueo compartido y conservación de una imagen válida si una actualización falla. Requiere ImageMagick en `/usr/bin/convert`; decodifica y normaliza a PNG eliminando metadatos. Máximo de 5 MiB para la descarga y 18 segundos de red por operación.
- `CoverArt`: propiedad del ejemplar, prioridad manual, selección explícita de alternativas aproximadas, precarga del listado y lotes voluntarios. Las imágenes se sirven por una ruta autenticada, nunca exponiendo `storage`.
- `CoverArtSource`: confirmación de una edición para la imagen cuando existe una revisión musical privada sin Release MBID activo. La ascendencia manual debe ser verificable y la edición debe estar almacenada; no se infiere por títulos ni se consulta automáticamente el proveedor.
- Controlador, vistas y `cover-art.js`: mensajes de sesión tras POST/302, indicadores accesibles, bloqueo temporal de botones y controles de lote. Los mensajes de éxito dependen de la respuesta del servidor; no simulan fases desconocidas.

## Esquema y actualización

La instalación requiere el esquema enciclopédico hasta 006. No hay migración automática al abrir una página.

007 crea únicamente `CAA_IMAGES`, `CAA_PREFERENCES` y `CAA_BATCHES`. No cambia las tablas musicales ni las referencias manuales antiguas. 008 añade exclusivamente `CAA_PREFERENCES.visual_source_json MEDIUMTEXT NULL`, con comprobación JSON_VALID. Las preferencias anteriores reciben NULL.

En una instalación **nueva y vacía**, la secuencia es `database/schema-pre005.sql`, 005_up, 006_up, 007_up y 008_up. No aplicar además 001–004: están incorporadas al esquema inicial. Las migraciones solo contienen estructura; no requieren colecciones, cuentas ni certificados privados. El usuario debe preparar sus credenciales y datos locales por separado.

Para una instalación existente: pausar escrituras, tomar un respaldo privado completo de MariaDB y de los archivos (incluido `storage/cover-art`), verificar SHA-256 y restauración aislada, contrastar esquema actual y ensayar únicamente las migraciones pendientes. Aplicar estructura e instalar código como pasos supervisados independientes; verificar integridad antes del arranque manual. No ejecutar estas instrucciones automáticamente contra una colección real.

## Preferencias e imágenes

La portada manual tiene prioridad por defecto. Seleccionar otra fuente no borra el archivo manual. Las alternativas de grupo se identifican como aproximadas y requieren selección expresa.

Una fuente visual independiente guarda edición, MBID, huella del contexto y confirmación en la preferencia CAA del ejemplar. No copia MBID a ENC_ALBUMS/ENC_EDITIONS ni altera canciones, créditos, fuentes o datos personales. Si cambian la edición o el contexto, debe revisarse la confirmación. Retirar la preferencia conserva todas las imágenes.

Los lotes admiten hasta 1.000 candidatos, con acciones de un disco o hasta cinco seguidos. Persisten progreso y pausas, limitan intentos y revalidan la autorización visual antes de descargar; retirar una confirmación impide usar una entrada antigua de la cola. Abrir una ficha o preparar un lote no inicia descargas.

Los PNG normalizados se guardan por SHA-256 en `storage/cover-art`, fuera de `public`, con archivos 0600 y directorios nuevos 0700. La identidad que ejecuta PHP debe poder leerlos y escribir en ese directorio; no se necesita abrir permisos globalmente. Las imágenes, cachés, preferencias privadas, sesiones y respaldos no se distribuyen con Git.

## Recuperación

Conservar los archivos anteriores y sus sumas. La recuperación del código no elimina imágenes ni preferencias: el código anterior puede ignorar la nueva columna. No ejecutar automáticamente un down ni restaurar destructivamente MariaDB.

008_down se niega a descartar confirmaciones existentes. 007_down se niega si hay imágenes, preferencias o lotes registrados. Antes de una reversión de esquema se requiere un procedimiento explícito que conserve y recupere esa información; no borrar registros para eludir esas protecciones.

## Pruebas y límites

Consultar `tests/cover-art/README.md`: fixtures ficticias, MariaDB con socket privado y sin TCP, sin `.env` ni peticiones a proveedores. Las pruebas dependientes de restauraciones reales, certificados operativos y capturas de colecciones no forman parte del repositorio.

Se reutilizaron los ensayos de instalación/recuperación y de compatibilidad ya aprobados. La validación funcional incluyó una edición vinculada y una revisión privada con selección visual independiente. La disponibilidad de portadas depende de CAA; no se garantiza una imagen para cada edición. No se descargan carátulas al publicar el código.

Quedan documentadas para operaciones independientes la posible existencia de dos ejemplares de una misma edición y una referencia manual histórica cuyo archivo ya faltaba. Esta publicación no fusiona registros, no reconstruye imágenes ausentes ni cambia referencias personales.
