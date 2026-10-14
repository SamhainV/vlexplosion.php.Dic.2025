# Revisión de publicación

El código de ejecución y los recursos estáticos coinciden por SHA-256 con la versión instalada revisada. Los 23 archivos de vinculación/edición manual coinciden con su manifiesto aprobado. Las únicas adaptaciones posteriores son la preparación de publicación, documentación, estructura SQL vacía y portabilidad de pruebas; no cambian el código instalado.

Se excluyen .env, credenciales, usuarios y contraseñas reales, uploads, sesiones, caché MusicBrainz, .lock de carátulas, logs, volcados, respaldos, tests/runtime, ensayos privados y certificados operativos. Las SQL incluidas están limitadas a estructura, migraciones y la fixture ficticia documentada. No se incluyen prototipos de reconocimiento o Discogs sin finalizar.

Se retiran del árbol nuevo los respaldos de código antiguos, el generador obsoleto create_app.sh y public/.htaccess.Antiguo; no se ejecutan. La documentación histórica de auditoría se sustituye por esta guía técnica actual.

El historial recibido ya contiene imágenes de uploads y respaldos de código. Su retirada del próximo árbol **no los elimina de los commits históricos**. No se reescribe el historial: cualquier decisión sobre material previamente publicado debe revisarse por separado.

No se ha ejecutado commit ni push. La rama main y el remoto GitHub están verificados localmente; no se ha consultado el estado remoto en línea. Antes de publicar, revisa el diff completo y comprueba que origin/main sigue siendo un ancestro; una actualización remota requiere nueva revisión, nunca push forzado.

## Comprobaciones de esta preparación

- 64 archivos de ejecución y recursos instalados idénticos por SHA-256; entre ellos, 23/23 de la última entrega.
- 980 comprobaciones superadas sobre esta copia: 30 de esquema, 60 de metadatos, 14 de formatos, 134 de seguridad, 22 de carátulas, 501 de base de datos/CRUD, 13 de navegador CRUD, 44 HTTP, 39 de importación, 48 de vinculación/edición manual y 75 de navegador enciclopédico.
- Reconstrucción ensayada separadamente: 31 tablas vacías más la columna instrument de 005, sin datos personales.
- Sintaxis verificada de 78 PHP, 4 Python y 5 JavaScript; diff sin errores de espacios.
- Revisión del árbol nuevo y del historial local buscando claves, tokens, URLs con credenciales y contraseñas literales. Sin credenciales detectadas. El hash bcrypt sintético del controlador de login y los valores deliberadamente ficticios de los tests fueron identificados como tales.
- Reglas de exclusión verificadas con diez rutas privadas/de ejecución y seis excepciones de código/SQL; preparación explícita ensayada con git add --dry-run, sin cambios preparados en el índice.

La suite se ejecutó con las dependencias de navegador disponibles localmente, usando NODE_PATH para resolver Playwright. No se ha ensayado npm install en una máquina nueva. La revisión por patrones no garantiza la ausencia absoluta de secretos: el diff completo sigue requiriendo revisión humana antes de autorizar commit y publicación. El estado remoto se comprobará mediante fetch antes de publicar.
