# VinylLibraryDB - PHP MVC (PDO) Scaffold

## Requisitos
- PHP 8.1+ (recomendado 8.2/8.3)
- MySQL/MariaDB
- Apache/Nginx (o PHP built-in server)

## Estructura
- `public/` Front controller y assets
- `app/` Código MVC
  - `core/` Núcleo (Router, Controller base, DB, Auth, etc.)
  - `controllers/` Controladores
  - `models/` Modelos (PDO)
  - `views/` Vistas (Tailwind por CDN de momento)
  - `config/` Configuración (DB)
- `storage/` Logs / cache (si lo necesitas)

## Arranque rápido (dev)
Desde la raíz del proyecto:
```bash
php -S localhost:8000 -t public
```
Y abre: http://localhost:8000

## Tailwind
De momento se usa Tailwind por CDN (rápido y simple).
Más adelante, si quieres compilación real (npm + tailwind), lo integramos en `public/assets/`.

## Copia de revisión: seguridad, fase 1

La conexión a base de datos está desactivada por defecto. `.env.example` contiene únicamente configuración ficticia, sin contraseña. No utilices credenciales de producción en esta copia.

Ejecuta `php tests/security.php` desde este directorio para las pruebas sin MySQL. Se crean archivos exclusivamente en `storage/` y `tests/runtime/` de esta copia. No instalan dependencias ni arrancan servicios.

Consulta `SECURITY-PHASE-1.md` para los cambios, requisitos de despliegue y límites de verificación. Las fases posteriores necesitan autorización expresa.

## Revisión de regresión y funciones (fases A–E)

Consulta `INFORME-REVISION.md` para resultados, archivos, riesgos y verificaciones pendientes.

- Seguridad aislada: `php tests/security.php` (no usa .env operativo).
- Integración ficticia SQL/HTTP: `python3 -B tests/run_database.py`. Requiere los binarios MariaDB y Pillow ya presentes. Crea una instancia nueva, sin TCP, y un servidor HTTP temporal independiente; no usa el servidor 8080 ni la colección real.
- Los artefactos permanecen exclusivamente en `tests/runtime/`; no se borran respaldos.

El formulario permite edición y varios autores; el listado incluye búsqueda y filtros. No se han aplicado migraciones o cambios de índices. Los límites y advertencias del informe forman parte de la verificación.
