# Esquema y migraciones

`database/schema-pre005.sql` es una estructura vacía reproducible de 31 tablas: 12 tradicionales/de control y 19 enciclopédicas. Se normalizaron únicamente los contadores AUTO_INCREMENT de tabla, que dependen de una colección. Mantiene tipos, índices, nulabilidad, claves foráneas y comprobaciones. No contiene INSERT, cuentas, contraseñas, triggers, datos de usuarios ni sentencias destructivas.

Para una instalación nueva, carga esa estructura en una base vacía y aplica únicamente 005_up. La versión resultante añade `ENC_CREDITS.instrument VARCHAR(120) NULL`, sin modificar las otras columnas. No hay migración automática al abrir la aplicación.

Se conservan las migraciones de desarrollo para reproducir las pruebas:

- 001_up/001_down: prototipo anterior de álbum/edición/ejemplar; crea ALBUMS_TBL y MUSICAL_RELEASES_TBL. Lo usa la suite ficticia para comprobar compatibilidad. **No forma parte de la estructura actual de producción ni debe aplicarse a una colección real.**
- 002: asociación del prototipo legado, únicamente para el ensayo ficticio de 001.
- 003: crea las 19 entidades ENC_* en una base de ensayo que ya tenga el esquema tradicional. No convierte automáticamente los discos existentes.
- 004: incorpora imported_at, credit_key y overrides_json al esquema enciclopédico de ensayo.
- 005_up: añade instrument; revisión previa, respaldo y ensayo aislado obligatorios para cualquier actualización real.
- 005_down: elimina instrument y **pierde los valores almacenados en esa columna**. Se incluye como fuente revisable; no es una recuperación automática ni se debe ejecutar indiscriminadamente.

`tests/schema.sql` es exclusivamente una fixture: crea vlexplosion_test, usuarios y vocabularios ficticios, una cuenta local de pruebas sin contraseña y un trigger deliberado de fallo. Solo se utiliza dentro del servidor desechable sin TCP que construye el runner. Nunca lo importes en un servidor compartido o real.

`LegacyEncyclopediaMigration` es un auxiliar explícito utilizado por las pruebas. No tiene ruta pública ni ejecución automática: crea borradores privados con una instantánea de los campos tradicionales, sin inferir ediciones o identidades. La vinculación funcional de la aplicación se realiza mediante EncyclopediaLink y conserva el identificador del ejemplar.

Esta publicación no incluye volcados ni instrucciones automáticas para modificar una instalación existente. La recuperación de datos personales requiere respaldos privados independientes.
