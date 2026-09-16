# Despliegue en Hostinger (erp.noaspamassage.com)

Cada `git push origin main` dispara `.github/workflows/deploy.yml`, que compila y
sube por FTP a `public_html/erp/`. El subdominio apunta a `public_html/erp/public`.
El `.env` del servidor se crea a mano (Administrador de archivos) y el deploy nunca lo toca.

## Secretos en GitHub (Settings → Secrets and variables → Actions)

| Secreto        | Valor                              |
|----------------|------------------------------------|
| `FTP_SERVER`   | `156.67.74.59`                     |
| `FTP_USERNAME` | `u257283941.spa` (con el `.spa`)   |
| `FTP_PASSWORD` | la contraseña de esa cuenta FTP    |

## Una sola vez, por SSH (hPanel → Avanzado → Acceso SSH)

```bash
cd ~/domains/noaspamassage.com/public_html/erp

# 1. Variables de entorno (copiar desde .env.example y completar).
cp .env.example .env && nano .env
#    APP_ENV=production  APP_DEBUG=false  APP_URL=https://erp.noaspamassage.com
#    DB_* de la base creada en hPanel, MAIL_*, GOOGLE_*, IZIPAY_*, SITE_WHATSAPP

# 2. Carpetas que el FTP no toca y clave de la app.
mkdir -p storage/app/public storage/logs storage/framework/{cache/data,sessions,views}
php artisan key:generate

# 3. Base de datos y enlace de fotos.
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan storage:link
```

## Después de cada deploy que cambie migraciones o config

```bash
php artisan migrate --force && php artisan optimize:clear
```

## Comprobaciones

- hPanel → Avanzado → Configuración PHP: versión **8.2** o superior.
- Google Cloud: agregar `https://erp.noaspamassage.com/cuenta/google/callback`.
- Izipay → Reglas de notificaciones: `https://erp.noaspamassage.com/pagos/izipay/notificacion`.
