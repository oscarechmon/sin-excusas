# Sin Excusas · Centro Estético — sitio web

Réplica estática de `Sin Excusas - Web v2.dc.html` construida con **HTML5 + CSS + Bootstrap 5.3**.
El diseño original era una sola página con vistas conmutadas por JavaScript; aquí se separó en
cuatro páginas reales, con URLs propias, indexables y navegables sin JavaScript.

## Estructura

```
voroz/
├── index.html            Inicio: hero, pilares, líneas de servicio, Nutricost, CTA
├── nosotros.html         Presentación del centro y valores
├── servicios.html        Catálogo por categoría (faciales / corporales / podología)
├── suplementos.html      Nutricost y productos complementarios
├── assets/
│   ├── css/main.css      Único archivo de estilos, organizado por secciones
│   ├── js/main.js        Comportamientos opcionales (pestañas, año, menú móvil)
│   └── img/              Logotipo y fotografías del sitio
└── README.md
```

## Cómo verlo

Basta con abrir `index.html` en el navegador. Para trabajar con rutas limpias,
levantar un servidor local desde esta carpeta:

```bash
# Python
python -m http.server 8000

# Node
npx serve .
```

Y visitar <http://localhost:8000>.

## Decisiones de implementación

| Original (`.dc.html`) | Esta versión |
|---|---|
| Vistas conmutadas con `sc-if` y estado en JS | Cuatro archivos HTML independientes |
| Pestañas de categoría por estado del componente | `nav-tabs` de Bootstrap con enlace profundo por hash |
| Estilos en línea en cada elemento | Clases con prefijo `se-` en `assets/css/main.css` |
| Alturas fijas en píxeles para las fotos | `aspect-ratio` mediante `.se-media--*`, adaptable |
| Rejillas `grid-template-columns` fijas | Sistema de rejilla de Bootstrap con puntos de corte |
| `<x-import image-slot>` | `.se-media` + `.se-placeholder` (marcador visible) |

### Convención de nombres

Se usa un BEM ligero con prefijo `se-` (Sin Excusas) para no chocar con las clases de Bootstrap:

- Bloque: `.se-tile`
- Elemento: `.se-tile__title`
- Modificador: `.se-btn--gold`

Las utilidades de Bootstrap (`row`, `col-*`, `d-flex`, `g-4`, `mt-*`) se usan tal cual;
`main.css` sólo aporta lo que Bootstrap no cubre: la paleta, la tipografía y los componentes de marca.

### Paleta

Definida como variables CSS en `:root` (sección 1 de `main.css`). Cambiar un token
actualiza todo el sitio:

| Token | Valor | Uso |
|---|---|---|
| `--se-ink` | `#14110C` | Titulares y fondos oscuros |
| `--se-gold` | `#B08D4B` | Color de acento y enlaces |
| `--se-gold-light` | `#D8B879` | Degradados y filetes |
| `--se-cream` | `#FAF6EE` | Fondos de sección alternos |
| `--se-muted` | `#7C7263` | Texto secundario |

## Pendientes antes de publicar

1. **Fotografías.** Cada bloque `.se-placeholder` marca dónde va una imagen y describe
   cuál corresponde. Para sustituirlo:

   ```html
   <!-- Antes -->
   <div class="se-media se-media--card">
     <div class="se-placeholder">Foto: limpieza facial</div>
   </div>

   <!-- Después -->
   <div class="se-media se-media--card">
     <img src="assets/img/limpieza-facial.jpg" alt="Sesión de limpieza facial profunda" loading="lazy">
   </div>
   ```

   Añadir `loading="lazy"` a todas las imágenes salvo la del hero.

2. **Número de WhatsApp.** Actualmente es el marcador `51999999999`. Aparece en la barra
   superior, la cabecera, el hero, cada tarjeta, la CTA y el botón flotante. Reemplazar en
   los cuatro archivos:

   ```bash
   sed -i 's/51999999999/51XXXXXXXXX/g' *.html
   ```

3. **Datos de contacto.** El pie tiene «Dirección / Teléfono / Instagram por confirmar».

4. **Peso del logotipo.** `assets/img/logo-sin-excusas.png` pesa ~730 KB para mostrarse a
   64 px y 150 px. Conviene exportar dos versiones optimizadas (o un WebP/SVG) antes de publicar.

## Accesibilidad

- Enlace «Ir al contenido» para navegación por teclado.
- Jerarquía de encabezados de un solo `h1` por página.
- Foco visible en todos los elementos interactivos.
- Pestañas con `role="tab"` / `role="tabpanel"` y `aria-*` gestionados por Bootstrap.
- Se respeta `prefers-reduced-motion`.
- Textos alternativos en las imágenes; el logotipo de la cabecera lleva `alt=""` porque
  el nombre ya está en texto al lado.

## Navegadores

Bootstrap 5.3 y CSS moderno (`aspect-ratio`, `backdrop-filter`, variables CSS): todos los
navegadores estables actuales. Sin soporte para Internet Explorer.
