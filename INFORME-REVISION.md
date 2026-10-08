# VLexPlosion: regresión, correcciones y verificación final

Fecha: 8 de octubre de 2026. Ámbito exclusivo: `/home/amr/Proyectos/vlexplosion-revision`.

## Resultado general

Se han ejecutado las fases A–E autorizadas. Se conserva PHP, MVC, PDO y el esquema de la aplicación. Se han implementado edición, búsqueda, filtros, tratamiento de varios autores y coordinación entre registros e imágenes. Las verificaciones ejecutadas suman **675 comprobaciones**: 134 de seguridad, 501 de SQL/imágenes/concurrencia y 40 HTTP. Además, 34 archivos PHP pasan la sintaxis y cuatro bloques JavaScript pasan el análisis sintáctico con Node, sustituyendo las expresiones PHP por valores de prueba.

No se ha accedido al proyecto original, ejecutado Git, modificado Apache o instalado software. No se ha leído el `.env` operativo ni utilizado su conexión. No se ha ejecutado SQL sobre `VinylLibraryDB`. No se han fusionado autores, alterado el esquema real, cambiado índices ni sobrescrito/eliminado respaldos. La prueba manual previa en Firefox/8080 procede del usuario; no se presenta como verificación propia.

**La evidencia respalda los flujos probados en el entorno ficticio; no permite afirmar seguridad absoluta, compatibilidad con todo despliegue o corrección de todos los registros reales.**

## Fase A: regresión de seguridad

Defecto confirmado y corregido: la suite anterior cargaba el `.env` operativo y los dobles HTTP actualizaban el archivo de límites utilizado por la aplicación. Se ha añadido un modo explícito de pruebas definido por los scripts PHP, no por una petición HTTP. En ese modo no se carga `.env`, la apertura ordinaria de PDO está bloqueada y sesiones, límites, logs y carátulas se escriben en `tests/runtime/application/<ejecución>/`.

Los ensayos de SQL inyectan un PDO conectado únicamente a un socket de prueba comprobado bajo `tests/runtime/db-*`. Los ensayos de seguridad emplean modelos ficticios. Cada ejecución de integración tiene almacenamiento independiente: una prueba reprodujo la interferencia de intentos entre ejecuciones y se corrigió el aislamiento por identificador.

Se ejecutaron pruebas de tokens válidos/ausentes/incorrectos/arrays, renovación y caducidad de sesión, limpieza de datos privados, validación y límites. Mediante HTTP real se comprobó login/logout, renovación de cookie, HttpOnly/SameSite=Lax, rechazo CSRF de alta/baja/edición y caducidad por inactividad. HTTPS real y configuraciones de proxy no se han probado.

## Fase B: funciones corregidas

- **Edición inexistente:** rutas GET `/vinyls/edit` y POST `/vinyls/update`; formulario común de alta/edición; verificación del propietario antes del formulario y del guardado; actualización transaccional. Una edición sin cambios también es una operación válida. Mantiene la imagen si no se selecciona otra.
- **Búsqueda ignorada:** consulta por título, productor y cualquiera de los autores. Se usan parámetros PDO y escape literal de `%`, `_` y `!`. El texto de prueba de SQL Injection devuelve una búsqueda literal, no registros ajenos.
- **Filtros ignorados:** favoritos y deseados, individualmente o combinados con búsqueda. Listado, conteo y cálculo de página comparten condiciones.
- **Contexto perdido:** búsqueda/filtros/orden se conservan en enlaces, campos ocultos y redirecciones. Un disco editado que deje de cumplir un filtro no aparecerá en ese resultado; el filtro no se cambia para forzar su aparición.
- **Múltiples autores:** el listado pagina vinilos, no relaciones. Los autores se recuperan en una consulta adicional por lote y se muestran conjuntamente. Se evita multiplicar tarjetas y truncar nombres mediante GROUP_CONCAT. El formulario permite entre uno y veinte autores; el campo antiguo `author` sigue aceptándose como alternativa.
- **Autores duplicados:** búsqueda determinista del autor existente; preservación de la identidad ya vinculada cuando el nombre no cambia; sin fusiones ni bajas de AUTHORS_TBL. Los nombres repetidos de un formulario se deduplican, pero no se fusionan filas de la tabla.
- **Concurrencia:** lecturas con bloqueo y reintentos limitados tras rollback para errores 1020, 1205 y 1213. El error 1020 se reprodujo realmente con dos procesos y se incorporó a la cobertura. Los reintentos son acotados; no garantizan ausencia de conflictos bajo cualquier carga o intervención externa.
- **Confirmación engañosa de eliminación:** mensaje de sesión generado por el resultado real. Un identificador inexistente informa que no se eliminó nada. El parámetro `deleted=1` ya no puede fabricar el mensaje de éxito.

Las doce ordenaciones se conservaron. Se ejecutaron todas contra MariaDB con empates y varias páginas, contrastando el resultado con un orden esperado independiente. También se comprobaron las posiciones calculadas para volver al disco.

## Fase C: imágenes y consistencia

`CoverStore` reúne tamaño, MIME, dimensiones, subida, rutas, bloqueo y limpieza.

- Máximo de 5 MB, 8192 píxeles por lado y 24 millones de píxeles. MIME obtenido con fileinfo y contrastado con cabecera/dimensiones; extensión generada a partir del formato. Se probaron PNG, JPG y WebP generados de forma ficticia.
- `is_uploaded_file()` y `move_uploaded_file()` permanecen obligatorios. Se rechazó un archivo local presentado como subida y se ejecutaron subidas multipart reales.
- Bloqueo compartido entre alta, edición y baja del flujo de imágenes de esta aplicación. Las lecturas relevantes se repiten dentro del bloqueo para no usar una carátula obsoleta al reemplazarla.
- Ante fallo SQL se intenta retirar la imagen nueva solamente si no tiene referencias. Un INSERT fallido por un trigger de prueba generó respuesta 500 genérica y no dejó la imagen subida.
- Después de commit, la imagen anterior o eliminada solo se retira si ninguna ficha la referencia. Se probó una referencia compartida de otro usuario mediante SQL real ficticio.
- Limpieza limitada a `uploads/covers/cover_<32 caracteres hexadecimales>.(jpg|png|webp)`. Se preservan rutas externas, archivos antiguos con otros nombres y enlaces simbólicos. Se prueban rechazo de traversal y preservación del destino de un enlace.
- Ruta relativa inexistente: carátula predeterminada. Fallo de carga del navegador: fallback sin bucle. Se conservan rutas externas admitidas anteriormente, sujetas al mismo fallback.
- Previsualización: revocación al reemplazar la selección y en pagehide.
- Presentación: carga diferida existente y decodificación asíncrona. No se han creado miniaturas ni reescrito imágenes originales.

**Límites:** finfo/getimagesize no equivalen a una decodificación completa ni eliminan metadatos. PHP no tiene GD en este entorno; no se ha instalado. La generación de miniaturas y la recodificación completa requieren decidir y disponer de un decodificador mantenido. Si la BD deja de estar disponible al comprobar referencias, la limpieza conserva el archivo por seguridad y puede quedar pendiente un huérfano. Una caída del proceso entre movimiento y compensación también exige revisión posterior; no se ejecutó una limpieza masiva. No se han examinado o reparado carátulas reales.

## Fase D: interfaz y mantenimiento

- Etiquetas asociadas a campos, atributos required/maxlength, autocomplete del login y campos de autores identificados.
- Formulario con altura limitada y scroll interno; errores anunciados como alertas. Cabecera/pie se vuelven inert durante la vista de formulario.
- Popup con rol de diálogo, nombre accesible, traslado de foco, retención mediante Tab/Shift+Tab, cierre con Escape y restauración de foco.
- Opciones de orden y resolución de imágenes centralizadas; filtros SQL y validación de autores compartidos.
- Eliminada del controlador la implementación duplicada de subidas.
- JSON del popup admite sustitución de UTF-8 inválido y errores explícitos de codificación.
- Exclusión añadida para `.env~`; no se leyó, sobrescribió ni eliminó ese respaldo.

La sintaxis JavaScript se comprobó, pero no se realizó una evaluación visual ni con lector de pantalla. La apariencia conserva Tailwind y las clases actuales. El generador antiguo y `.htaccess.Antiguo` se mantienen sin ejecutar ni borrar: no son la fuente de la aplicación actual y no deben utilizarse para regenerar esta versión.

**Tailwind:** se revisó la dependencia externa. Se mantiene el CDN para conservar la apariencia y evitar introducir un pipeline no comprobado. La documentación oficial indica que Play CDN es para desarrollo. El paso recomendado posteriormente es un CSS estático con versión fijada, tras comprobar equivalencia visual; no se realizó una actualización de versión ni instalación de paquetes.

## Fase E: pruebas ejecutadas y reproducción

| Bloque | Evidencia ejecutada | Resultado |
|---|---|---|
| Seguridad | `php tests/security.php` | 134 comprobaciones, sin BD |
| SQL, CRUD, filtros, imágenes y concurrencia | `python3 -B tests/run_database.py` | 501 comprobaciones |
| HTTP real | Incluido en el comando anterior | 40 comprobaciones |
| Sintaxis PHP | `php -l` en app/public/tests, excluyendo runtime | 34 archivos sin errores |
| JavaScript | Node --check sobre cuatro bloques inline, con placeholders PHP | Sin errores de sintaxis |

La integración utiliza PHP 8.5.11 y MariaDB 11.8.8. `mariadb-install-db` crea un directorio nuevo bajo tests/runtime; mariadbd se inicia con `--no-defaults`, `--skip-networking`, socket, PID, logs y tmpdir propios. No reutiliza servicios o directorios de datos existentes. El usuario de pruebas solo dispone de SELECT/INSERT/UPDATE/DELETE sobre `vlexplosion_test`; las tablas ficticias y el trigger de fallo se crean únicamente al inicializar esa instancia separada. Los procesos temporales de MariaDB y PHP se detienen en finally.

El servidor HTTP utiliza un puerto loopback elegido para esa ejecución y exige una clave ficticia efímera. No se hacen peticiones a 8080. Los scripts http_router.php/worker.php están fuera de public/ y son exclusivamente de prueba.

Dependencias de pruebas ya disponibles: Python, Pillow, PHP con PDO MySQL/fileinfo y binarios MariaDB. No se instalaron ni actualizaron. Las pruebas no ejecutan DDL sobre la BD real. La prueba de ON DELETE CASCADE se realizó dentro de una transacción ficticia y se revirtió.

Los directorios de pruebas se conservan para inspección y están excluidos de Git. Tras las iteraciones realizadas ocupan aproximadamente 1,1 GB. No se ha ejecutado ninguna limpieza de respaldos ni de datos existentes.

## Riesgos y asuntos pendientes

| Prioridad | Tipo | Asunto y actuación recomendada |
|---|---|---|
| Alta | Riesgo heredado de despliegue | Eficacia de `.htaccess`, raíz pública, HTTPS y secretos. No se ha probado Apache ni rotado ninguna credencial; verificar con autorización separada. |
| Alta | Riesgo de integridad heredado | ON DELETE CASCADE de catálogos puede eliminar fichas. Confirmado en fixture; no se cambió el esquema real. Revisar RESTRICT requiere autorización específica. |
| Media | Límite de imágenes | Falta de decodificación/recodificación completa y miniaturas; conservar los límites y evaluar una extensión mantenida. |
| Media | Recuperación de fallos | Si no puede consultarse la BD, se conserva el archivo. Planificar revisión de huérfanos sin borrar automáticamente archivos reales. |
| Media | Concurrencia externa | Sin UNIQUE de autores no se garantiza unicidad frente a otros clientes/otras configuraciones transaccionales. No fusionar ni cambiar el esquema automáticamente. |
| Media | Autenticación | El límite por identificador utiliza normalización ASCII, no unificación de alias usuario/email o Unicode; el límite por IP aporta otra barrera. No se declara resuelto ese riesgo residual. |
| Media | Dependencia frontend | Tailwind CDN sigue dependiendo de un tercero y no tiene una compilación local reproducible. |
| Baja | Mantenimiento | Generador y configuración antiguos conservados; documentación identifica su carácter obsoleto. |

No se han añadido índices: los JOIN usan las relaciones del esquema y no existe evidencia de carga real que justifique cambios. La búsqueda con comodín inicial y el conteo de posición pueden recorrer registros. No se ha realizado benchmarking de la colección ni EXPLAIN de la BD real. Las optimizaciones futuras deben basarse en volumen y planes reales autorizados.

## Comprobaciones manuales recomendadas

1. En la copia de revisión, comprobar teclado, Tab/Shift+Tab, Escape y restauración de foco del popup; lector de pantalla y zoom al 200 %.
2. Revisar móvil/pantallas bajas, scroll del formulario y la apariencia de los controles nuevos.
3. Verificar las doce ordenaciones con la colación y contenido reales sin escrituras automatizadas.
4. Comprobar HTTPS/proxy y atributos de cookies en el despliegue autorizado; no habilitar producción como entorno de prueba.
5. Verificar que la raíz web sea public/ y que .env, respaldos, sesiones, logs y tests no se sirvan. El servidor PHP de pruebas no interpreta .htaccess.
6. Revisar casos de imágenes externas/no disponibles y límites efectivos upload_max_filesize/post_max_size del SAPI utilizado.
7. Probar edición/baja únicamente con una ficha ficticia creada manualmente en un entorno autorizado, o seguir utilizando la instancia aislada; no usar automatización para modificar la colección.

## Valoración

La revisión dispone de funciones CRUD completas y evidencia automatizada sustancial para los flujos principales sobre PHP 8.5/MariaDB. La seguridad de formularios y sesiones permanece comprobada en los escenarios ejecutados. La gestión de imágenes y autores es más consistente y está cubierta con errores y concurrencia.

Siguen pendientes endurecimiento de despliegue, decodificación completa/miniaturas y revisión visual/accesible. La integridad y rendimiento de los datos reales no se han auditado en esta intervención. No se declara funcionamiento al 100 % ni seguridad absoluta.

## Fuentes técnicas

- [Tailwind: Play CDN](https://tailwindcss.com/docs/installation/play-cdn): destinado a desarrollo.
- [PHP: getimagesize](https://www.php.net/manual/en/function.getimagesize.php): dimensiones y límites de validación.
- [MariaDB: CREATE TABLE y claves foráneas](https://github.com/mariadb-corporation/mariadb-docs/blob/main/server/reference/sql-statements/data-definition/create/create-table.md): semántica de acciones en relaciones.

## Archivos modificados y añadidos

La lista se obtiene comparando hashes previos y actuales exclusivamente de esta copia; no se utilizó Git.

Modificados:

- [app/Controllers/VinylController.php](/home/amr/Proyectos/vlexplosion-revision/app/Controllers/VinylController.php)
- [app/Core/Database.php](/home/amr/Proyectos/vlexplosion-revision/app/Core/Database.php)
- [app/Core/ErrorHandler.php](/home/amr/Proyectos/vlexplosion-revision/app/Core/ErrorHandler.php)
- [app/Core/LoginLimiter.php](/home/amr/Proyectos/vlexplosion-revision/app/Core/LoginLimiter.php)
- [app/Core/SessionManager.php](/home/amr/Proyectos/vlexplosion-revision/app/Core/SessionManager.php)
- [app/Core/VinylInput.php](/home/amr/Proyectos/vlexplosion-revision/app/Core/VinylInput.php)
- [app/Core/bootstrap.php](/home/amr/Proyectos/vlexplosion-revision/app/Core/bootstrap.php)
- [app/Core/helpers.php](/home/amr/Proyectos/vlexplosion-revision/app/Core/helpers.php)
- [app/Models/Vinyl.php](/home/amr/Proyectos/vlexplosion-revision/app/Models/Vinyl.php)
- [app/views/auth/login.php](/home/amr/Proyectos/vlexplosion-revision/app/views/auth/login.php)
- [app/views/vinyls/create.php](/home/amr/Proyectos/vlexplosion-revision/app/views/vinyls/create.php)
- [app/views/vinyls/index.php](/home/amr/Proyectos/vlexplosion-revision/app/views/vinyls/index.php)
- [app/views/vinyls/show.php](/home/amr/Proyectos/vlexplosion-revision/app/views/vinyls/show.php)
- [public/index.php](/home/amr/Proyectos/vlexplosion-revision/public/index.php)
- [tests/request.php](/home/amr/Proyectos/vlexplosion-revision/tests/request.php)
- [tests/security.php](/home/amr/Proyectos/vlexplosion-revision/tests/security.php)
- [.gitignore](/home/amr/Proyectos/vlexplosion-revision/.gitignore)

Añadidos:

- [app/Core/CollectionFilter.php](/home/amr/Proyectos/vlexplosion-revision/app/Core/CollectionFilter.php)
- [app/Core/CoverStore.php](/home/amr/Proyectos/vlexplosion-revision/app/Core/CoverStore.php)
- [tests/database.php](/home/amr/Proyectos/vlexplosion-revision/tests/database.php)
- [tests/http_router.php](/home/amr/Proyectos/vlexplosion-revision/tests/http_router.php)
- [tests/http_tests.py](/home/amr/Proyectos/vlexplosion-revision/tests/http_tests.py)
- [tests/regression-baseline.json](/home/amr/Proyectos/vlexplosion-revision/tests/regression-baseline.json)
- [tests/run_database.py](/home/amr/Proyectos/vlexplosion-revision/tests/run_database.py)
- [tests/schema.sql](/home/amr/Proyectos/vlexplosion-revision/tests/schema.sql)
- [tests/worker.php](/home/amr/Proyectos/vlexplosion-revision/tests/worker.php)
- [INFORME-REVISION.md](/home/amr/Proyectos/vlexplosion-revision/INFORME-REVISION.md)

Documentación actualizada: README.md y SECURITY-PHASE-1.md. No se ha modificado .env ni ningún respaldo.
