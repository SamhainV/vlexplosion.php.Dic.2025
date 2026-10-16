# Regresiones CAA sin datos de colección

Ejecutar únicamente en un clon de desarrollo, sin sudo, con PHP CLI/PDO MySQL/fileinfo, MariaDB (cliente, mariadbd y mariadb-install-db), Python 3, ImageMagick y utilidades curl/getent/timeout. Para navegador: Node.js, Playwright y Chromium. Usar una ruta corta para el clon por el límite del socket Unix. Comprobar recursos y no ejecutar instancias paralelas.

```bash
python3 -B tests/cover-art/run.py
# Con Node en PATH y Playwright instalado:
VLEX_TEST_BROWSER=/ruta/a/chromium python3 -B tests/cover-art/run.py --browser
```

`VLEX_TEST_NODE` y `NODE_PATH` permiten seleccionar Node y sus módulos. El puerto de navegador es 8153; se rechaza uno ya ocupado. Las respuestas CAA se inyectan y el navegador bloquea recursos externos, incluido el CDN de estilos. No se realizan descargas reales. Las capturas generadas quedan en tests/runtime, ignorado por Git.

El runner crea un datadir propio marcado SYNTHETIC-COVERS, verifica su identidad y TCP deshabilitado, usa un buffer de 32 MiB y cierra sus procesos. No lee `.env`, credenciales ni respaldos; no admite restauraciones privadas. Los usuarios y contraseñas de fixtures son ficticios y viven solamente en esa instancia. Los runtimes se conservan fuera del repositorio. `--reuse db-enc-XXXXXXXX` reutiliza únicamente un datadir sintético de este clon y reconstruye sus fixtures; nunca acepta una base externa.

Cobertura: HTTPS/redirecciones/SSRF, límites, formatos maliciosos, normalización, caché y errores, conservación manual, autorización por propietario, lotes acotados, CRUD/filtros/doce ordenaciones/paginación y navegación Premium. `archive-dn.php` reproduce redirecciones oficiales con respuestas simuladas.

`visual-source.php` usa álbumes, ediciones, pistas, participante y corrección personal ficticios. Comprueba confirmación explícita, rechazo de contexto obsoleto y otras ediciones/propietarios, idempotencia, invalidación, retirada y conservación de todas las tablas originales, con descarga sintética. Las migraciones 007 y 008 se aplican exclusivamente al servidor privado.

Los ensayos de restauración de colecciones reales, certificados de despliegue y capturas privadas se conservan fuera de la publicación. No ejecutar las suites desde producción.
