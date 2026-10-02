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
├── .htaccess                                   ← manda todo a public/
├── app/  config/  routes/  vendor/  storage/ …
├── .env                                        ← se crea a mano, nunca se pisa
├── public/                                     ← lo único que se sirve
└── sistema/                                    ← OTRO proyecto (sistema.sinexcusas.org.pe)
```

La carpeta raíz del dominio en hPanel (**Sitios web → sinexcusas.org.pe →
Avanzado**) es `public_html`, y el [`.htaccess`](.htaccess) de la raíz manda
cada petición a `public/`: así el `.env`, el código y el `release.zip` quedan
fuera de alcance.

**`public_html/sistema` no es de este proyecto.** Es la carpeta del subdominio
`sistema.sinexcusas.org.pe` (el ERP, dueño del catálogo y del stock: ver
"Conexión con el sistema"), con su propio repositorio, base de datos,
cuenta FTP (`u367943235.sistema`) y despliegue (ver su `DEPLOY.md`). Trae su
propio `.htaccess`, y Apache aplica el de la carpeta más profunda, así que las
reglas de aquí no lo alcanzan. Este despliegue no la toca porque el zip no la
incluye y publicar no borra nada; no la borres al limpiar a mano.

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

## Conexión con el sistema (ERP)

Con el sistema conectado (`sistema.sinexcusas.org.pe`), **el catálogo y el stock
se administran allá** y esta aplicación guarda una copia para la web:

| Se administra en el sistema | Se sigue administrando aquí |
|---|---|
| Productos, insumos y servicios: crear, borrar, nombre, categoría, precio, costo | Imagen, descripción, publicar en la web, "se vende al cliente" |
| Stock: compras, ajustes, kardex | Duración del servicio y quién lo atiende |
| Ventas de productos (en su POS) | Agenda, atenciones, paquetes, comisiones; ventas de servicios y paquetes |

- Cada cambio en el sistema llega aquí solo, al instante. Lo nuevo llega **sin
  publicar**: tú decides qué se muestra.
- Un pedido pagado en la tienda se registra allá como venta (canal web) y es
  esa venta la que descuenta el stock. Si el sistema no contesta, el pedido
  sigue pagado, queda una nota interna y se reintenta al sincronizar.
- Anular un pedido pagado anula su venta allá. Si el sistema no contesta, la
  anulación no se guarda: reintenta.
- Los insumos de una atención salen del stock del sistema. Si allá no alcanza,
  la atención no se registra (igual que antes).
- En **Inventario** y **Servicios** aparece un aviso con el botón
  **Sincronizar ahora**, que trae todo el catálogo y reintenta los pedidos
  pendientes. Lo mismo hace `php artisan erp:sincronizar`.

Sin `ERP_URL` ni `ERP_TOKEN` en el `.env`, todo funciona como antes.

### Conectarlas por primera vez

Requisito: el sistema ya desplegado y funcionando (ver su `DEPLOY.md`), y este
proyecto desplegado con la versión que trae la integración (la migración agrega
el enlace `erp_id`).

1. Genera una cadena al azar (por ejemplo `php -r "echo bin2hex(random_bytes(32));"`).
2. En el `.env` del **sistema** (`public_html/sistema`):

   ```env
   INTEGRATION_TOKEN=<la cadena>
   INTEGRATION_WEB_URL=https://sinexcusas.org.pe
   ```

   y por SSH, en esa carpeta: `php artisan optimize`.
3. En el `.env` de **esta** aplicación (`public_html`):

   ```env
   ERP_URL=https://sistema.sinexcusas.org.pe
   ERP_TOKEN=<la misma cadena>
   ```

   y **en la misma sesión SSH**, sin dejar pasar tiempo:

   ```bash
   cd ~/domains/sinexcusas.org.pe/public_html
   php artisan optimize
   php artisan erp:vincular
   ```

   `erp:vincular` da de alta en el sistema los productos, insumos y servicios
   que ya existen aquí, **con su stock actual** (entra como saldo inicial en el
   kardex), y los deja enlazados. Mientras no termina, la tienda podría vender
   algo que el sistema aún no conoce; por eso va justo después de configurar.
   Si se corta, vuelve a ejecutarlo: no duplica nada.
4. Revisa en el sistema **Productos y servicios**: tiene que estar todo. En la
   web nada cambia: lo publicado sigue publicado.

**Opcional: sincronización periódica.** Los avisos llegan solos, pero si la web
estuvo caída alguno se pierde. hPanel → **Avanzado → Cron jobs**, cada 15
minutos:

```
cd /home/u367943235/domains/sinexcusas.org.pe/public_html && php artisan erp:sincronizar
```

(Usa la ruta de PHP que proponga hPanel si `php` a secas no la encuentra.)

**Para desconectar**, vacía `ERP_URL` y `ERP_TOKEN` y ejecuta
`php artisan optimize`: el panel vuelve a administrar el inventario con la
última copia que llegó del sistema.

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
| "El catálogo … se administra en el sistema" al crear o ajustar | Es lo esperado con el sistema conectado: hazlo allá. |
| `No se pudo conectar con el sistema` | `ERP_URL` mal escrita o el sistema caído. Al volver, **Sincronizar ahora**. |
| `403` al sincronizar o en el log del sistema | `ERP_TOKEN` (aquí) e `INTEGRATION_TOKEN` (allá) no coinciden; tras corregir, `php artisan optimize` en ambos. |
| Un cambio del sistema no aparece en la web | Pulsa **Sincronizar ahora** en Inventario. Si se repite, revisa `INTEGRATION_WEB_URL` en el sistema. |
| Nota interna "No se pudo registrar la venta en el sistema" en un pedido | El sistema no contestó al pagar. **Sincronizar ahora** (o el cron) lo registra. |
| Se queda extrayendo y no termina | Baja `DEPLOY_CHUNK` en el `.env` (por ejemplo 400) y vuelve a lanzar. |
