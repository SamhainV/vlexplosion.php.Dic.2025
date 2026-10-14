# Arquitectura de la enciclopedia

`EncyclopediaController` coordina búsqueda, revisión e importación. `MusicBrainzClient` valida consultas y UUID, utiliza caché persistente y un bloqueo para espaciar peticiones, respeta Retry-After y limita los reintentos. `MusicBrainzNormalizer` separa grupo de lanzamiento, edición, soportes, pistas, grabaciones, obras, sellos y créditos. Los campos ausentes permanecen ausentes; no se inventan fechas, duraciones o soportes.

`EncyclopediaCollection` guarda el ejemplar junto con sus relaciones musicales en una transacción, con una clave de idempotencia. `EncyclopediaLink` asocia un ejemplar ya existente: conserva su Id y datos personales, valida propietario, revisión y selección confirmada y registra la procedencia en overrides_json. Las ediciones CD se identifican explícitamente y la búsqueda dispone de preferencia por vinilo.

`EncyclopediaManual` gestiona ediciones musicales privadas y conflictos de revisión. La edición global de entidades compartidas exige un permiso configurado explícitamente. `EncyclopediaRecord` resuelve la ficha musical y sus diferencias privadas; `EncyclopediaEntity` permite navegar a artistas, sellos, obras y grabaciones relacionados. Las vistas muestran soportes, catálogo, país, pistas, duraciones y créditos con instrumento cuando existe.

El formulario distingue colección personal y metadatos musicales. Modificar el título personal no modifica el título canónico de una edición vinculada. Para un borrador privado sin edición, se sincroniza el título musical con el título personal al guardar; la condición `c.edition_id IS NULL` protege las ediciones enlazadas. Las regresiones de workflows verifican ambos casos.

Seguridad: autenticación, propiedad de ejemplares, CSRF, validación, SQL parametrizado, límites de longitud y subidas, restricción de rutas, transacciones y control de concurrencia. Los errores públicos no muestran SQL, credenciales ni detalles internos.

IA: `AiFeature` solo permite activar el prototipo bajo VLEXPLOSION_TESTING; en ejecución habitual devuelve indisponibilidad. Se conserva el controlador instalado y la vista informativa para reproducir esa ruta, pero no se incluyen proveedores OpenAI, vistas de reconocimiento ni servicios experimentales que no están instalados. PhotoSearchProposal y FieldReview son auxiliares puros necesarios para las regresiones, sin proveedor ni peticiones de pago.

MusicBrainz usa su API pública y exige acceso a Internet para búsquedas reales. Las pruebas normales sustituyen el transporte por fixtures. Las referencias JSON contienen exclusivamente metadatos musicales públicos de MusicBrainz, no datos procedentes de una colección; su reutilización debe respetar las condiciones de los datos de MusicBrainz. El proyecto no incorpora una licencia nueva ni cambia los derechos de recursos existentes.
