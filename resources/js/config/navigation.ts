/**
 * Definición única del menú lateral.
 *
 * Se mantiene aquí (y no dentro del layout) para que agregar un módulo sea
 * tocar un solo archivo, y para que los roles no queden repartidos por varios
 * componentes.
 *
 * - `route`: nombre de la ruta en vue-router. Si falta, el módulo aún no existe
 *   y el item se muestra deshabilitado en lugar de enlazar a la nada.
 * - `roles`: roles que ven el item. Vacío u omitido = visible para todos.
 */

export const RoleName = {
  ADMINISTRADOR: 'Administrador',
  RECEPCION: 'Recepción',
  ESPECIALISTA: 'Especialista',
} as const

export type RoleValue = (typeof RoleName)[keyof typeof RoleName]

export interface NavItem {
  label: string
  icon: string
  route?: string
  roles?: RoleValue[]
}

export interface NavSection {
  label: string
  items: NavItem[]
}

const ALL = [RoleName.ADMINISTRADOR, RoleName.RECEPCION, RoleName.ESPECIALISTA]

export const navigation: NavSection[] = [
  {
    label: 'Principal',
    items: [
      { label: 'Dashboard', icon: 'pi pi-home', route: 'dashboard', roles: ALL },
    ],
  },
  {
    label: 'Gestión',
    items: [
      {
        label: 'Clientes',
        icon: 'pi pi-users',
        route: 'clients',
        roles: [RoleName.ADMINISTRADOR, RoleName.RECEPCION],
      },
      { label: 'Agenda', icon: 'pi pi-calendar', route: 'appointments', roles: ALL },
      {
        label: 'Servicios',
        icon: 'pi pi-star',
        route: 'services',
        roles: [RoleName.ADMINISTRADOR],
      },
      {
        label: 'Categorías',
        icon: 'pi pi-tags',
        route: 'service-categories',
        roles: [RoleName.ADMINISTRADOR],
      },
    ],
  },
  {
    label: 'Operaciones',
    items: [
      { label: 'Atenciones', icon: 'pi pi-check-square', roles: ALL },
      {
        label: 'Ventas',
        icon: 'pi pi-shopping-cart',
        roles: [RoleName.ADMINISTRADOR, RoleName.RECEPCION],
      },
      {
        label: 'Caja',
        icon: 'pi pi-wallet',
        roles: [RoleName.ADMINISTRADOR, RoleName.RECEPCION],
      },
      { label: 'Paquetes', icon: 'pi pi-box', roles: [RoleName.ADMINISTRADOR, RoleName.RECEPCION] },
    ],
  },
  {
    label: 'Administración',
    items: [
      { label: 'Inventario', icon: 'pi pi-database', roles: [RoleName.ADMINISTRADOR] },
      { label: 'Personal', icon: 'pi pi-id-card', roles: [RoleName.ADMINISTRADOR] },
      { label: 'Comisiones', icon: 'pi pi-percentage', roles: [RoleName.ADMINISTRADOR] },
      { label: 'Reportes', icon: 'pi pi-chart-bar', roles: [RoleName.ADMINISTRADOR] },
      { label: 'Usuarios', icon: 'pi pi-lock', roles: [RoleName.ADMINISTRADOR] },
    ],
  },
]

/** Títulos por nombre de ruta, usados por el breadcrumb. */
export const routeTitles: Record<string, string> = {
  dashboard: 'Dashboard',
  clients: 'Clientes',
  'client-detail': 'Ficha de cliente',
  appointments: 'Agenda',
  services: 'Servicios',
  'service-categories': 'Categorías',
}
