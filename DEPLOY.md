# Despliegue en Hostinger (sinexcusas.org.pe)

Cada `git push origin main` dispara `.github/workflows/deploy.yml`, que compila
el proyecto, lo empaqueta en **un solo `release.zip`**, lo sube por FTP y le
pide al servidor que lo descomprima, migre y regenere las cachés.

**Por qué un zip.** Subir el proyecto archivo por archivo son más de doce mil
transferencias FTP (`vendor/` es casi todo eso) y Hostinger cierra la sesión a
los 3600 segundos con `421 Session Timeout`, así que la subida no llegaba nunca
al final. Un único archivo tarda un par de minutos.

**Lo que el zip no toca:** el `.env` del servidor, las fotos subidas
(`storage/app/public`), los logs y las sesiones. La lista está en
[`.deployignore`](.deployignore).

**El `.htaccess` de la raíz viaja en el repositorio** ([`.htaccess`](.htaccess)).
Es el que manda las peticiones de `public_html` a `public/index.php`. Si falta,
el dominio responde 403; ya pasó una vez, cuando un despliegue reescribió la
carpeta y se lo llevó por delante.

**Ojo:** publicar sobrescribe y agrega, pero no borra. Si eliminas un archivo
del repositorio, sigue existiendo en el servidor hasta que lo borres a mano.

## Estructura en el servidor

```
/home/u367943235/domains/sinexcusas.org.pe/public_html/   ← aquí se descomprime
├── app/  config/  routes/  vendor/  storage/ …
├── .env                                        ← se crea a mano, nunca se pisa
└── public/                                     ← raíz del dominio
```

En hPanel → **Sitios web → sinexcusas.org.pe → Avanzado**, la carpeta raíz del
dominio debe apuntar a `public_html/public`. Si apunta a `public_html` a secas,
quedan expuestos el `.env`, el código y el propio `release.zip`.

## Secretos en GitHub (Settings → Secrets and variables → Actions)

Los cinco son obligatorios; sin alguno, el workflow falla en el primer paso con
el nombre del que falta.

| Secreto        | Valor                                             |
|----------------|---------------------------------------------------|
| `FTP_SERVER`   | `br-asc-web1445.hstgr.io` (ver nota)              |
| `FTP_USERNAME` | `u367943235.sinexcusas`                           |
| `FTP_PASSWORD` | la contraseña de esa cuenta FTP                   |
| `DEPLOY_URL`   | `https://sinexcusas.org.pe`                       |
| `DEPLOY_TOKEN` | una cadena larga al azar, la misma que en el `.env` |

**El nombre del servidor, no `ftp.sinexcusas.org.pe` ni la IP.** El dominio
resuelve al CDN de Hostinger, que no habla FTP, y el nombre `ftp.` no conecta
desde fuera (`curl: (6) Could not resolve host`). Con la IP pelada la conexión
cifrada tampoco valida, porque el certificado está emitido para `*.hstgr.io`
(`curl: (60)`). El nombre del servidor cumple las dos cosas: conecta y el
certificado es válido.

Sale del banner de SSH (`u367943235@br-asc-web1445`) o de hPanel, y se le añade
`.hstgr.io`. Si algún día cambian de servidor, ese es el valor a actualizar.

La cuenta FTP debe estar creada apuntando a
`/home/u367943235/domains/sinexcusas.org.pe/public_html`, porque el workflow
sube el paquete a la raíz de esa cuenta.

## Puesta en marcha (una sola vez)

La ruta que descomprime vive dentro de la propia aplicación, así que el primer
despliegue hay que sembrarlo a mano:

1. Lanza el workflow (**Actions → Deploy → Run workflow**). Subirá el
   `release.zip` y fallará al publicar: todavía no hay código que lo atienda.
2. hPanel → **Administrador de archivos** → entra a `public_html`, selecciona
   `release.zip` y usa **Extraer**.
3. Crea el `.env` en `public_html` (cópialo del local y cambia lo de abajo).
4. hPanel → **Avanzado → Terminal SSH** (o Acceso SSH):

   ```bash
   cd ~/domains/sinexcusas.org.pe/public_html
   php artisan key:generate          # solo si el .env no trae APP_KEY
   php artisan migrate --force
   php artisan db:seed --class=RolePermissionSeeder --force
   php artisan storage:link
   php artisan optimize
   ```

5. Borra el `release.zip` que quedó y vuelve a lanzar el workflow: a partir de
   aquí todo es automático.

### Lo que cambia en el `.env` del servidor

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sinexcusas.org.pe
SESSION_SECURE_COOKIE=true
DEPLOY_TOKEN=<la misma cadena que el secreto de GitHub>
```

El panel usa tokens Bearer, no cookies entre dominios, así que
`SANCTUM_STATEFUL_DOMAINS` se deja vacío.

## Servicios externos que apuntan al dominio

Al cambiar de `erp.noaspamassage.com` a `sinexcusas.org.pe` hay que actualizar:

- **Google Cloud → Credenciales → URI de redirección autorizado**:
  `https://sinexcusas.org.pe/cuenta/google/callback`
- **Izipay → Reglas de notificaciones → URL de notificación al final del pago**:
  `https://sinexcusas.org.pe/pagos/izipay/notificacion`
- **Google Search Console**: verificar el dominio nuevo.

## Cómo funciona la publicación

Terminada la subida, GitHub Actions llama a `POST /deploy/release` con la
cabecera `X-Deploy-Token`. El servidor descomprime **por tandas** (1200
entradas por llamada, configurable con `DEPLOY_CHUNK`) para no pasarse del
tiempo máximo de ejecución de PHP, y responde por dónde va. Cuando termina:

```
php artisan optimize:clear   → tira las cachés del código viejo
php artisan migrate --force  → aplica las migraciones nuevas
php artisan optimize         → cachea config, rutas y vistas
```

Con config en caché Laravel deja de leer el `.env` y todos los archivos de
`config/` en cada visita, que es buena parte del tiempo de respuesta en un
hosting compartido. Por lo mismo, **cada cambio del `.env` en el servidor exige
volver a cachear**: `php artisan optimize` por SSH, o un push nuevo.

## Si algo falla

| Síntoma | Causa y arreglo |
|---|---|
| `Faltan estos secretos …` | Crea los que nombra el mensaje en Settings → Secrets. |
| `curl: (67)` al subir | Usuario o contraseña FTP mal: el usuario lleva el sufijo `.sinexcusas` y la contraseña distingue mayúsculas. |
| `curl: (6)` al subir | `FTP_SERVER` no resuelve: usa el nombre del servidor, `algo.hstgr.io`. |
| `curl: (60)` al subir | `FTP_SERVER` tiene una IP; el certificado es para `*.hstgr.io`. |
| 403 en todo el dominio | Falta el `.htaccess` en `public_html`. |
| `404` al publicar | El `DEPLOY_TOKEN` del `.env` está vacío o el servidor tiene la config vieja en caché. |
| `403` al publicar | El token del `.env` y el secreto de GitHub no coinciden. |
| `No hay release.zip que publicar` | La subida FTP no llegó a la carpeta de la aplicación: revisa a qué directorio apunta la cuenta FTP. |
| `Este PHP no tiene la extensión zip` | hPanel → Configuración PHP → activar `zip`. |
| Se queda extrayendo y no termina | Baja `DEPLOY_CHUNK` en el `.env` (por ejemplo 400) y vuelve a lanzar. |
