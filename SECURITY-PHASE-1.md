# Primera fase: seguridad

Cambios limitados a esta copia. No se han importado credenciales ni datos de colección.

- CSRF en login, logout, alta y eliminación; verificación antes de invocar el controlador POST.
- Cookies HttpOnly y SameSite=Lax, Secure en HTTPS o mediante configuración explícita.
- Modo estricto, renovación en login/logout, expiración a los 30 minutos de inactividad y 12 horas absolutas.
- Límites locales de login: 5 intentos por identificador y 30 por IP en 15 minutos; estado compartido con bloqueo de archivo, independiente de la sesión. IP tomada de REMOTE_ADDR, no de cabeceras reenviadas.
- Validación de tipo, UTF-8, longitud, números, año, flags y pertenencia a catálogos antes de subir imágenes.
- Respuestas de error genéricas; registro local solo de clase/código, sin mensajes SQL, entradas ni secretos.
- Conexión PDO bloqueada salvo DB_ENABLED=1. .env.example solo contiene valores ficticios y contraseña vacía.
- Reglas .htaccess locales para raíz privada y directorio público; requieren Apache 2.4 y AllowOverride compatible. No se ha cambiado ni validado la configuración del servidor.

Pruebas: `php tests/security.php` y `find app public tests -name '*.php' -exec php -l {} \;` desde esta copia. No requieren paquetes ni MySQL. Los modelos ficticios de tests/fixtures.php nunca acceden a PDO. Las pruebas crean solo sesiones, logs y estado ficticio dentro de storage/ y tests/runtime/ de esta copia; no se borran archivos.

No habilitar DB_ENABLED en las pruebas. No iniciar esta copia con credenciales de producción. La raíz web debe ser public/; las reglas locales no sustituyen su configuración. El login por HTTPS detrás de un proxy necesita SESSION_COOKIE_SECURE=1; no se confía automáticamente en cabeceras de proxy.

Límites pendientes: pruebas HTTP reales de cookies/redirecciones, límites con procesos concurrentes y conexión con una BD aislada; configuración Apache y exposición efectiva de archivos; rotación de credenciales expuestas del original; normalización Unicode/alias email-usuario en los límites por cuenta. El límite agregado por IP aporta una segunda barrera.

Fuera de esta fase: ciclo de vida de imágenes, edición, filtros, mensajes de borrado, reorganización general, índices y esquema. No se aplican migraciones ni modificaciones a servicios.

## Actualización posterior

Este documento conserva el contexto de la fase inicial. La revisión posterior y los resultados actuales están en `INFORME-REVISION.md`. Las pruebas ahora evitan .env y storage operativos. Se han ejecutado SQL en MariaDB ficticia, HTTP aislado y concurrencia; HTTPS/Apache y evaluación visual siguen pendientes.
