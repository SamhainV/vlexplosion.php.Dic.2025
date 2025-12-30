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
