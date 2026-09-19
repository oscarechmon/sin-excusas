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

## Migrar y cachear automáticamente en cada deploy

El FTP no ejecuta comandos, así que al terminar la subida GitHub Actions llama a
`POST /deploy/optimize`, que corre `optimize:clear`, `migrate --force` y
`optimize` (config, rutas y vistas en caché: Laravel deja de leer el `.env` y
todos los archivos de configuración en cada visita).

1. Genera un token largo y aleatorio (por ejemplo, 64 caracteres).
2. En el `.env` del servidor: `DEPLOY_TOKEN=<ese token>`.
3. En GitHub → Settings → Secrets → Actions:
   - `DEPLOY_TOKEN` = el mismo token
   - `DEPLOY_URL` = `https://erp.noaspamassage.com`

Sin esos secretos el paso se omite y el deploy funciona igual que antes. Con
config en caché, **cada cambio del `.env` del servidor exige volver a cachear**:
`php artisan optimize` por SSH (o hacer un nuevo push).
