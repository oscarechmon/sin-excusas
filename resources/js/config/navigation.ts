/**
 * Definición única del menú lateral.
 *
 * Se mantiene aquí (y no dentro del layout) para que agregar un módulo sea
 * tocar un solo archivo, y para que los permisos no queden repartidos por
 * varios componentes.
 *
 * - `route`: nombre de la ruta en vue-router. Si falta, el módulo aún no
 *   existe y el item se muestra deshabilitado en lugar de enlazar a la nada.
 * - `permission`: mismo nombre que protege el endpoint en el backend. El menú
 *   solo oculta lo que el usuario no podría usar; la seguridad real está en la
 *   API (§29).
 * - `erpManaged`: con el sistema (ERP) conectado esto se opera allá, así que
 *   el menú lo oculta (y la API lo rechaza).
 */

export interface NavItem {
  label: string
  icon: string
  route?: string
  permission?: string
  erpManaged?: boolean
}

export interface NavSection {
  label: string
  items: NavItem[]
}

export const navigation: NavSection[] = [
  {
    label: 'Principal',
    items: [{ label: 'Dashboard', icon: 'pi pi-home', route: 'dashboard' }],
  },
  {
    label: 'Gestión',
    items: [
      { label: 'Clientes', icon: 'pi pi-users', route: 'clients', permission: 'clients.view', erpManaged: true },
      { label: 'Agenda', icon: 'pi pi-calendar', route: 'appointments', permission: 'appointments.view', erpManaged: true },
      { label: 'Atenciones', icon: 'pi pi-check-square', route: 'attendances', permission: 'attendances.view', erpManaged: true },
    ],
  },
  {
    label: 'Operaciones',
    items: [
      { label: 'Ventas', icon: 'pi pi-shopping-cart', route: 'sales', permission: 'sales.view', erpManaged: true },
      { label: 'Caja', icon: 'pi pi-wallet', route: 'cash', permission: 'cash.view', erpManaged: true },
      { label: 'Paquetes', icon: 'pi pi-box', route: 'packages', permission: 'packages.view' },
      { label: 'Inventario', icon: 'pi pi-database', route: 'inventory', permission: 'inventory.view' },
    ],
  },
  {
    label: 'Tienda online',
    items: [
      { label: 'Ventas online', icon: 'pi pi-globe', route: 'online-sales', permission: 'online_sales.view' },
    ],
  },
  {
    label: 'Catálogo',
    items: [
      { label: 'Servicios', icon: 'pi pi-star', route: 'services', permission: 'services.view' },
      { label: 'Categorías', icon: 'pi pi-tags', route: 'service-categories', permission: 'services.manage' },
      { label: 'Contenido web', icon: 'pi pi-images', route: 'site-content', permission: 'settings.manage' },
      { label: 'Datos de la web', icon: 'pi pi-at', route: 'site-settings', permission: 'settings.manage' },
    ],
  },
  {
    label: 'Administración',
    items: [
      { label: 'Personal', icon: 'pi pi-id-card', route: 'staff', permission: 'employees.view', erpManaged: true },
      { label: 'Comisiones', icon: 'pi pi-percentage', route: 'commissions', permission: 'commissions.view', erpManaged: true },
      { label: 'Reportes', icon: 'pi pi-chart-bar', route: 'reports', permission: 'reports.view', erpManaged: true },
      { label: 'Usuarios', icon: 'pi pi-lock', route: 'users', permission: 'users.view' },
    ],
  },
]

/** Títulos por nombre de ruta, usados por el breadcrumb. */
export const routeTitles: Record<string, string> = {
  dashboard: 'Dashboard',
  clients: 'Clientes',
  'client-detail': 'Ficha de cliente',
  appointments: 'Agenda',
  attendances: 'Atenciones',
  services: 'Servicios',
  'service-categories': 'Categorías',
  staff: 'Personal',
  inventory: 'Inventario',
  packages: 'Paquetes',
  sales: 'Ventas',
  'online-sales': 'Ventas online',
  'site-content': 'Contenido web',
  'site-settings': 'Datos de la web',
  cash: 'Caja',
  commissions: 'Comisiones',
  reports: 'Reportes',
  users: 'Usuarios y roles',
  forbidden: 'Sin acceso',
}
