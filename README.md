# VLexPlosion Enciclopedia

Gestor personal de discos y enciclopedia musical: búsqueda y selección de ediciones MusicBrainz, importación de canciones y créditos, vinculación de ejemplares existentes, edición musical privada y navegación entre artistas, sellos, obras y grabaciones. Conserva el CRUD tradicional y separa los datos personales de los metadatos musicales compartidos.

## Dependencias

PHP 8.2 o posterior con PDO MySQL, fileinfo, mbstring si está disponible, sesiones y JSON; MariaDB con soporte de CHECK y JSON_VALID. No requiere Composer. La presentación utiliza Tailwind desde CDN y los recursos estáticos de `public/assets`.

Las pruebas necesitan Python 3 con Pillow, los ejecutables MariaDB (`mariadb-install-db`, `mariadbd`, `mariadb`), Node.js, Playwright y Chrome. Instala las dependencias de prueba con `npm install --ignore-scripts`; `package.json` fija la versión de Playwright. Configura `VLEX_TEST_BROWSER` si Chrome está en otra ruta. No se incluye un lockfile npm: la versión directa está fijada y aún no se ha ensayado una instalación npm desde cero.

## Reconstruir una instalación nueva

1. Clona el repositorio en una carpeta nueva. No copies el repositorio encima de una aplicación existente.
2. Crea una base **vacía e independiente** y una cuenta MariaDB con permisos adecuados, usando autenticación interactiva y contraseñas locales.
3. Sobre esa base nueva, importa `database/schema-pre005.sql` y después `migrations/005_encyclopedia_instruments_up.sql`. Por ejemplo, sustituyendo los nombres indicados:

   ```bash
   mariadb --no-defaults --protocol=socket -u TU_USUARIO -p --database=TU_BASE_NUEVA < database/schema-pre005.sql
   mariadb --no-defaults --protocol=socket -u TU_USUARIO -p --database=TU_BASE_NUEVA < migrations/005_encyclopedia_instruments_up.sql
   ```

   La estructura contiene 31 tablas y ningún registro, cuenta MariaDB, contraseña, contador de colección o sesión. No ejecutes además 001–004 sobre esta estructura: esas alteraciones ya están incorporadas. No utilices estos comandos para actualizar una base existente.
4. Copia `.env.example` a `.env`, rellena únicamente tus credenciales locales y activa `DB_ENABLED=1`. Restringe el acceso a `.env` y nunca lo añadas a Git. `VLEX_ENC_ENABLED=1` habilita la enciclopedia. Mantén `VLEX_ENC_SHARED_EDITORS` vacío: la edición global no está autorizada por defecto.
5. La base vacía no incluye usuarios ni vocabularios personales. Provisiona privadamente una cuenta de aplicación en `Users_TBL` usando un hash de `password_hash`, y los términos tradicionales que necesites. No existe un registro público de usuarios ni una contraseña inicial. Para recuperar una colección propia, utiliza exclusivamente tu procedimiento privado de restauración, fuera de Git.
6. Ejecuta PHP desde la raíz: `php -d opcache.enable_cli=0 -S localhost:8000 -t public/`. El servidor integrado se documenta para uso local; no se configura Apache ni se instala ningún servicio.

La aplicación crea sus directorios de sesiones, caché y carátulas cuando los necesita; el usuario que ejecuta PHP debe poder escribir en `storage/` y `public/uploads/`. Protege esos datos y sirve únicamente `public/`.

## Pruebas reproducibles y aisladas

Desde una copia de trabajo, con suficiente espacio y sin otra suite en ejecución:

```bash
python3 -B tests/encyclopedia/run.py --empty-schema-check
python3 -B tests/encyclopedia/run.py --web-checks --port 8127
```

El runner crea una instancia MariaDB propia con `--no-defaults`, socket privado, TCP desactivado y buffer de 32 MB. Verifica su identidad antes de usarla, evita `.env` y no accede a la colección real. Las pruebas web usan respuestas MusicBrainz inyectadas, fixtures ficticios y referencias musicales públicas; no realizan peticiones de pago. Los procesos creados se cierran al terminar. Los datos generados se conservan en `tests/runtime`, excluido de Git, para diagnosticar fallos. Ejecuta las suites secuencialmente y revisa disco y memoria antes de repetirlas.

`--serve` y `--live-web-checks` son opciones de desarrollo que pueden consultar MusicBrainz realmente. No son necesarias para las regresiones sin conexión.

## Migraciones y límites

Consulta [la guía de esquema](docs/technical/SCHEMA.md), [la arquitectura](docs/technical/ENCICLOPEDIA.md) y [la revisión de publicación](docs/technical/PUBLICATION.md).

El reconocimiento fotográfico y Discogs permanecen desactivados. La ruta antigua de IA ofrece un estado informativo; sus ramas de prototipo no se publican como una funcionalidad operativa ni deben habilitarse. Git contiene código y pruebas, nunca colecciones, usuarios, carátulas personales, sesiones, cachés, respaldos ni credenciales.
