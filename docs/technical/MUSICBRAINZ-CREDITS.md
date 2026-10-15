# Créditos y personal de MusicBrainz

Esta versión consolida la ampliación de créditos, las correcciones R2 del editor manual, el progreso persistente y la presentación agrupada. Conserva la interfaz Premium y el CRUD tradicional.

## Esquema y actualización

La migración `006_encyclopedia_credit_relations_up.sql` parte de 005. Añade identidad/JSON de relación, ámbito de edición, actividad y fecha de consulta en ENC_CREDITS, y ENC_CREDIT_JOBS para los checkpoints. No modifica datos personales de los ejemplares. La migración 005 permanece intacta.

En una instalación nueva se necesita el esquema base y las migraciones indicadas en SCHEMA.md, seguidas por 005 y 006 en orden. En una instalación existente hay que comprobar qué migraciones están aplicadas: 006 no debe ejecutarse dos veces. Antes de actualizar: detener escrituras, hacer respaldo completo, verificar restauración aislada, ensayar y comprobar la integridad de los datos originales. El archivo 006_down incorpora un bloqueo cuando hay información enriquecida; no es un procedimiento de recuperación automática.

La versión publicada incluye ambas migraciones 006 como código fuente. Ningún comando Git ejecuta migraciones.

## Consulta explícita, acotada y recuperable

La ficha de una edición vinculada permite iniciar una actualización sin desvincular ni duplicar el ejemplar. Abrir o recargar la ficha no consulta al proveedor ni crea trabajos.

Se consulta la edición, grupo, grabaciones y obras pertinentes. No se recorren biografías ni discografías de artistas. Los nuevos lookups de obras omiten las relaciones inversas a todas sus grabaciones. Se conserva la información contextual ya almacenada; no se interpreta su omisión deliberada como borrado.

Se reutilizan caché y bloqueo de frecuencia. Cada paso hace como máximo tres consultas; hay tres intentos por entidad, 256 entidades, 400 consultas y 12 MiB por trabajo. Retry-After y las pausas locales se conservan sin agotar artificialmente los intentos.

Estados diferenciados: recopilando, pausado, recopilación completa pendiente de guardar, créditos guardados, cancelado y fallido. Cancelar conserva el checkpoint y no borra créditos guardados. Reanudar no reinicia los presupuestos. Un nuevo trabajo se identifica aparte del último guardado. No hay reintentos recursivos ilimitados.

El guardado es transaccional e idempotente. No se sobrescriben créditos manuales; se conserva su procedencia. La confirmación manual sin cambios no produce el falso duplicado de las antiguas proyecciones importadas. Una reversión explícita se detiene si hay cambios posteriores incompatibles; nunca fuerza una restauración de la base.

## Relaciones y presentación

Se preservan MBID de origen y destino, tipo, dirección, ámbito, atributos e instrumentos, nombres acreditados, fechas, respuesta original disponible y procedencia. ENC_SOURCES y ENC_ASSERTIONS siguen siendo los mecanismos compartidos.

Las relaciones a personas/grupos, sellos y lugares se presentan como créditos; las versiones, grabaciones ajenas, series y enlaces se conservan separadas como contexto. Un tipo desconocido conserva su denominación. Esta clasificación no garantiza que MusicBrainz documente todo el personal del disco.

CreditPresentation es una proyección de lectura. Agrupa identidades y funciones equivalentes entre canciones, manteniendo instrumentos, atributos, acreditaciones y ámbitos distintos cuando corresponde. Cada tarjeta conserva las participaciones originales y sus fuentes. Las identidades sin ID fiable nunca se fusionan por nombre.

Los contadores distinguen participaciones, tarjetas e identidades; no equivalen a personas únicas ni se suman de nuevo al abrir los créditos por canción. Las funciones habituales se muestran en español y el término original sigue accesible. Los nombres propios e instrumentos no se traducen indiscriminadamente.

Las editoriales de una obra no se atribuyen automáticamente a la edición física. Se indican las relaciones que comienzan después de su fecha y se mantienen sus fechas originales. Un origen ajeno al ámbito de la ficha queda advertido, sin asignarlo a sus canciones.

## Configuración y seguridad

Se conserva `VLEX_ENC_ENABLED=1` y el editor compartido queda deshabilitado por defecto (`VLEX_ENC_SHARED_EDITORS=`). No se activa Discogs ni reconocimiento fotográfico. No guardar credenciales en el repositorio; utilizar la configuración local privada. Las cachés, sesiones y cargas de usuarios quedan fuera de Git.

## Pruebas

Consultar [tests/credits/README.md](../../tests/credits/README.md). Los tests SQL utilizan exclusivamente instancias privadas por socket con identidades y datos personales ficticios, nunca la colección real. Las respuestas musicales de referencia proceden de MusicBrainz y permiten pruebas sin red; véase su index.json, SHA256SUMS y el [aviso de datos](../credits/README.md).
