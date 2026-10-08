# Carátulas y ficha de vinilo

Cambios exclusivamente en la revisión. No se leyó .env, conectó la colección real, modificó SQL, autenticación, sesiones, CSRF o archivos de carátulas reales. No se accedió al original, Git ni servicios existentes.

## Defectos y correcciones

- Antes, una ruta absoluta local se devolvía sin comprobar su archivo. Ahora se comprueban rutas locales relativas, absolutas web y físicas dentro de public/ de esta copia; si faltan, se utiliza `/assets/images/default-cover.webp`.
- El fallback anterior anulaba onerror. En un popup reutilizado, después de un fallo posterior podía aparecer una imagen rota. El listener compartido permanece activo y compara la URL actual con la predeterminada para no reintentar si también falla esta última.
- El fallback predeterminado tiene URL raíz explícita, independiente del prefijo del script.
- Se admiten prefijos históricos public/, ./ y rutas web con /public/, sin cambiar ninguna referencia de la BD ni consultar el filesystem del original. Las rutas externas HTTP/HTTPS se conservan y sus errores los maneja el navegador, sin peticiones de servidor.
- Se rechazan traversal y enlaces simbólicos locales. Se conservan parámetros de URLs locales válidas.
- Listado, popup, ficha y previsualización utilizan la protección compartida. El popup se inicializa con la imagen predeterminada, evitando src vacío.

## Ficha rediseñada

Contenedor centrado de hasta 1040 px; carátula a la izquierda y datos a la derecha desde 768 px. En móvil se apilan. Título, autores y etiquetas destacados; siete datos en dl/dt/dd; botones Editar y Volver con contexto de filtros, orden y página; fondo oscuro y verdes discretos. No hay truncamiento de datos. Se añadieron texto alternativo, foco visible y contraste más claro para etiquetas.

## Pruebas ejecutadas

- `php tests/covers.php`: 22 casos de rutas, sin BD ni escrituras de imágenes.
- `python3 -B tests/run_covers_browser.py`: 37 comprobaciones en Chrome headless, con modelos y carátula sintéticos; no conecta con ninguna BD.
- `php tests/security.php`: 134 comprobaciones superadas.
- `python3 -B tests/run_database.py`: 501 comprobaciones SQL/imágenes/concurrencia y 40 HTTP sobre MariaDB ficticia sin TCP.
- 37 archivos PHP pasan sintaxis. La vista final y fixture se volvieron a comprobar tras los ajustes de contraste/título largo; el script covers.js y el test de navegador pasan Node --check.

Total: 734 comprobaciones. El número incluye aserciones, no implica 734 escenarios independientes.

Navegador: carátula existente, null, ruta relativa ausente, ruta absoluta ausente, URL que falla; ficha y listado; reapertura del popup; fallo del propio default con una sola solicitud y sin bucle; botones de edición/retorno y sus parámetros; textos completos, semántica, alt y foco. Layouts comprobados a 1280x900, 768x1024 y 375x812, sin overflow horizontal. Caso adicional de título continuo de 65 caracteres.

Chrome usa un perfil nuevo en tests/runtime y temporales en tests/t. El primer intento de arranque falló porque la ruta del socket temporal era demasiado larga; se ajustó a tests/t, siempre dentro de la revisión. No se instaló navegador o dependencia. Los procesos temporales se cerraron; no se utilizó el servidor 8080.

Capturas finales (datos ficticios):
- tests/runtime/covers-30cb1e47/desktop.png
- tests/runtime/covers-30cb1e47/tablet.png
- tests/runtime/covers-30cb1e47/mobile.png

Las capturas anteriores también se inspeccionaron visualmente. No se ha evaluado lector de pantalla ni exhaustivamente otros navegadores, zoom extremo o el despliegue real. Las imágenes realmente ausentes no se han copiado o restaurado: se muestra la predeterminada.

## Archivos modificados/añadidos

Aplicación:
- app/Core/CoverStore.php: resolución y URL predeterminada; subida y limpieza sin cambios.
- app/views/vinyls/show.php: nueva ficha.
- app/views/vinyls/index.php: fallback de tarjetas y popup.
- app/views/vinyls/create.php: fallback de previsualización.
- app/views/layouts/header.php: carga del script común.
- public/assets/js/covers.js: nuevo listener de errores de imagen.

Pruebas/documentación:
- tests/covers.php
- tests/cover_fixtures.php
- tests/cover_router.php
- tests/covers-browser.cjs
- tests/run_covers_browser.py
- .gitignore: excluye tests/t.
- CARATULAS-FICHA.md

No se modificaron modelos, controladores, consultas SQL o configuración de seguridad.
