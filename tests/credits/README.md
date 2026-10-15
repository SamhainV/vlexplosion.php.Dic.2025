# Pruebas de créditos

Requisitos: PHP CLI con PDO MySQL, MariaDB (mariadbd, mariadb-install-db y cliente), Python 3 y Git. Para navegador: Node.js, Playwright y Chromium. Ejecutar sin sudo desde un clon con escritura en tests/runtime. No iniciar varios ensayos a la vez. Usar una ruta corta para el clon: el socket Unix tiene un límite de 100 bytes en este arnés.

El arnés crea una instancia MariaDB vacía, aislada por socket, con TCP deshabilitado y buffer de 32 MiB. Prepara allí el esquema y las migraciones de prueba, nunca en MariaDB del sistema. No lee .env. No ejecutar estos tests desde la aplicación de producción. Las respuestas de MusicBrainz están simuladas; no se consulta el proveedor.

Regresiones dirigidas, cada una sobre su base propia:

```bash
python3 -B tests/credits/run.py --manual-projection-only
python3 -B tests/credits/run.py --progress-only
python3 -B tests/credits/run.py --scope-only
python3 -B tests/credits/run.py --regression-only
python3 -B tests/credits/run.py --presentation
```

`--presentation` reproduce el grafo musical público, la separación de contexto y la presentación agrupada con datos personales ficticios. Para capturas y regresión de navegador:

```bash
export VLEX_TEST_BROWSER=/ruta/a/chromium
# Node y Playwright deben estar disponibles en PATH y en la resolución de módulos.
python3 -B tests/credits/run.py --presentation --browser --port 8165
```

Se admite VLEX_TEST_NODE para seleccionar Node y NODE_PATH para sus módulos. Cambiar el puerto si está ocupado. Los archivos antes/después son fixtures visuales de la plantilla y CSS anteriores; no son material de producción. El router de comparación está destinado solo a este arnés aislado.

Las opciones --progress-only --browser y --scope-only --browser cubren las pantallas específicas. `run.py` sin selector ejecuta las comprobaciones más amplias, incluida compatibilidad con el código histórico b0d935e disponible en Git. No es necesario repetirlas para una publicación sin cambios de aplicación.

El directorio tests/runtime conserva logs, estados, perfiles de navegador y bases privadas de pruebas. Está ignorado: no publicarlo. Los scripts de reanudación aceptan solo rutas de runtime de este clon, no copias de producción. `tools/fetch-credit-reference.php` es una herramienta opcional y explícita de captura pública con consultas GET acotadas; no forma parte de las pruebas offline ni se ejecuta automáticamente.
