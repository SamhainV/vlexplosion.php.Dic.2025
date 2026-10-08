# Rediseño de edición y auditoría funcional del CRUD

Ámbito: exclusivamente `/home/amr/Proyectos/vlexplosion-revision`. No se ha accedido al proyecto original, leído credenciales reales, conectado a VinylLibraryDB, modificado servicios, instalado dependencias ni realizado operaciones Git.

## Defectos confirmados y correcciones

- `app/views/vinyls/create.php`: alta y edición usaban un overlay fijo, `max-w-sm`, límite de altura y scroll interno. El JavaScript marcaba cabecera y pie como `inert`. Se sustituyó la estructura por una página normal de anchura máxima 1040 px, h1, campos en dos columnas desde 768 px y una en móvil, secciones de datos/carátula/colección y acciones. La página usa su desplazamiento normal, mantiene la cabecera y el pie activos y conserva nombres de campos, CSRF y contexto de navegación. Edición muestra «Guardar cambios».
- `app/Controllers/VinylController.php`: la vista de edición no recibía la ruta de la carátula actual. Ahora recibe esa información, también al devolver un error de validación. No se modifican rutas almacenadas. Se reutiliza CoverStore y su fallback existente.
- `app/views/vinyls/create.php`: se muestra la imagen actual junto al selector y la previsualización de la nueva imagen; las URL temporales se revocan. Se conservan autores múltiples y sus controles, favoritos y deseados. Se añade bloqueo de envíos repetidos del mismo formulario y restauración al regresar con historial.
- `app/views/vinyls/index.php`: borrado dependía de `window.confirm`. Se sustituye por un diálogo nativo `<dialog>` estilizado con Tailwind, título del vinilo insertado con textContent, descripción, Cancelar y Eliminar definitivamente. Cancelar recibe el foco, Escape cierra y el foco retorna al botón original. El POST existente conserva token CSRF, propietario y contexto. Los controles de edición usan verde coherente con la ficha.
- `public/assets/js/delete-confirmation.js`: gestiona confirmación, foco, bloqueo de doble envío y recuperación al volver con historial. En navegadores sin showModal usa confirm como alternativa. Sin JavaScript los botones de eliminar permanecen desactivados y se muestra una explicación, evitando eliminar sin confirmación.
- Alta y edición disponen de mensajes de éxito en sesión, establecidos únicamente tras guardar correctamente. El listado los presenta como un estado accesible y mantiene el resaltado del registro. Los mensajes de borrado siguen dependiendo del resultado real.

No se cambiaron consultas SQL, esquema, autenticación, sesiones, permisos, CSRF ni mecanismos de validación. No se sobrescribieron ni eliminaron carátulas de la colección.

## Auditoría y pruebas

Se ejecutaron las suites existentes y se ampliaron HTTP y navegador:

| Suite | Comprobaciones superadas | Cobertura |
|---|---:|---|
| `php tests/security.php` | 134 | Sesiones, CSRF, entrada, errores, acceso y fixtures ficticios |
| `php tests/covers.php` | 22 | Rutas relativas/absolutas, fallback, recorrido y enlaces |
| `python3 tests/run_database.py` — SQL/imágenes | 501 | CRUD real, usuarios separados, autores múltiples, 12 ordenaciones, búsqueda, filtros, paginación, rollback, imágenes compartidas, compensación, concurrencia |
| mismo comando — HTTP | 44 | Login, POST multipart, CSRF, CRUD, conservar/sustituir imagen, rollback de alta, edición inválida conservando datos, GET sin borrado, permisos, sesión y mensajes reales |
| mismo comando — navegador CRUD | 13 | Alta con imagen/autores, confirmación de alta, edición de título/productor/año/autores/deseado, cancelación, borrado, doble clic con un solo POST, registro eliminado y ausencia de errores JS |
| `python3 tests/run_covers_browser.py` | 64 | Ficha/listado/fallback, edición desktop/móvil, columnas, ausencia de modal/scroll interno/desbordamiento, cabecera activa, carátula actual, preview, autores, foco, Escape y diálogo de borrado |

Total: **778 comprobaciones**. Además, lint correcto en 37 archivos PHP y comprobación sintáctica Node de los JavaScript nuevos. La primera ejecución de la prueba del doble clic falló por una espera que aceptaba la URL del listado anterior; se corrigió para esperar el POST y su mensaje de resultado y se volvió a ejecutar con éxito.

Las escrituras se realizaron solo en MariaDB nueva con `--no-defaults`, socket propio bajo tests/runtime, `--skip-networking`, usuario ficticio test_runner y esquema ficticio vlexplosion_test. Cada proceso creado por las pruebas se detuvo al finalizar. Los servidores HTTP usan puertos locales libres, no 8080 ni servicios existentes. El router es exclusivo de pruebas y evita .env; sirve `/assets/` como archivos estáticos. No se utiliza public/index.php como router. El arranque normal sigue siendo `php -S 127.0.0.1:8080 -t public`.

## Verificación visual

Chromium real con Tailwind actual, recursos cargados, sin sustituir su mecanismo CDN. Ficha revisada a 1280×900, 768×1024 y 375×812. Edición revisada a 1280×900 y 375×812: 1040 px máximo en escritorio, dos columnas, una columna móvil, sin overlay ni desplazamiento interno; navegación y acciones integradas. Se inspeccionaron capturas reales de edición escritorio, móvil y confirmación de borrado móvil. Los campos y botones son accesibles por teclado; diálogo nativo contiene el foco y soporta Escape.

Capturas ficticias: `tests/runtime/covers-66f531cf/edit-desktop.png`, `edit-mobile.png`, `delete-dialog.png`, `desktop.png`, `tablet.png`, `mobile.png`.

## Archivos modificados o nuevos

- app/Controllers/VinylController.php
- app/views/vinyls/create.php
- app/views/vinyls/index.php
- public/assets/js/delete-confirmation.js (nuevo)
- tests/http_router.php (solo pruebas: servir assets estáticos)
- tests/http_tests.py
- tests/covers-browser.cjs
- tests/crud-browser.cjs (nuevo)
- CRUD-EDICION.md (este informe)

Las pruebas dejan logs, perfiles, capturas e instancias ficticias detenidas bajo tests/runtime y tests/t, sin borrar archivos existentes.

## Límites y pendientes

- No se comprobó la colección real ni el esquema/datos de VinylLibraryDB: se verificó el código con esquema ficticio y permisos separados.
- Las carátulas reales excluidas de uploads no se recuperaron; las referencias sin archivo físico muestran el fallback. Los tests de interfaz usan imágenes sintéticas; SQL/HTTP comprueban uploads reales aislados.
- Comprobación visual real en Chromium; no se probó Firefox, Safari ni tecnologías de asistencia. Accesibilidad básica verificada, sin auditoría WCAG completa.
- El bloqueo de doble envío protege interacciones accidentales del navegador; no garantiza idempotencia frente a POST independientes o reintentos de red. No existe criterio para prohibir dos discos deliberadamente iguales, por lo que no se añadió una restricción de unicidad.
- Se verificó rollback SQL en alta y actualización y compensación del archivo en alta fallida. No se simuló caída abrupta del proceso o del equipo entre transacción y limpieza de archivos.
- Un fallo interno sigue devolviendo la respuesta genérica segura existente; no se ha añadido un sistema de recuperación de borradores después de errores 500.
- Tailwind sigue dependiendo del CDN por requisito del usuario. No se verificó renderizado sin red.

En las comprobaciones ejecutadas no quedaron fallos. Esto acredita esos escenarios, no garantiza ausencia absoluta de defectos ni confirma el comportamiento sobre datos de producción.
