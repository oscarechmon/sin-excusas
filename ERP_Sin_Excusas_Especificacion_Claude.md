# ERP SIN EXCUSAS
## Especificación funcional y técnica para desarrollo con Claude

> Documento maestro para utilizar como contexto de desarrollo.
> El objetivo es construir una primera versión funcional, mantenible y escalable del ERP de **Sin Excusas**, evitando sobreingeniería y respetando principios SOLID.

---

# 1. OBJETIVO GENERAL

Desarrollar un ERP web para la gestión diaria de **Sin Excusas**, orientado principalmente a:

- Clientes.
- Agenda y citas.
- Servicios.
- Paquetes y sesiones.
- Registro de atenciones.
- Ventas.
- Pagos.
- Caja.
- Inventario.
- Insumos asociados a servicios.
- Personal.
- Comisiones.
- Reportes.
- Usuarios, roles y permisos.

La primera versión debe priorizar:

1. Facilidad de uso.
2. Simplicidad de desarrollo.
3. Código mantenible.
4. Buena separación de responsabilidades.
5. Escalabilidad futura.
6. Seguridad.
7. Interfaces simples y directas.
8. Evitar lógica duplicada.
9. Evitar controladores o componentes excesivamente grandes.
10. No agregar funcionalidades no solicitadas sin una necesidad técnica clara.

---

# 2. STACK TECNOLÓGICO

## Backend

Utilizar:

- Laravel.
- PHP compatible con la versión de Laravel seleccionada.
- MySQL o MariaDB.
- Laravel Sanctum para autenticación si se trabaja mediante API.
- Form Requests para validaciones.
- Policies / Gates para autorización.
- Events y Listeners cuando exista una acción de negocio que requiera ejecutar procesos secundarios.
- Jobs únicamente cuando sea realmente necesario ejecutar procesos en segundo plano.
- Services y Actions para lógica de negocio.
- Eloquent ORM.
- Migrations.
- Seeders.
- Factories.
- PHPUnit o Pest para pruebas.

No colocar lógica de negocio compleja directamente en controladores.

---

## Frontend

Utilizar:

- Vue 3.
- Composition API.
- PrimeVue.
- PrimeIcons.
- Vite.
- Vue Router.
- Pinia para estado global cuando sea necesario.
- Axios o una capa centralizada de servicios HTTP.
- SCSS.
- JavaScript o TypeScript de forma consistente en todo el proyecto.

Se recomienda TypeScript si el proyecto empieza desde cero.

---

# 3. PRIMEVUE

La interfaz debe aprovechar componentes de PrimeVue antes de crear componentes personalizados equivalentes.

Utilizar según corresponda:

- DataTable.
- Column.
- Dialog.
- Drawer.
- Button.
- InputText.
- InputNumber.
- InputMask.
- Textarea.
- Select.
- MultiSelect.
- DatePicker.
- Checkbox.
- RadioButton.
- ToggleSwitch.
- AutoComplete.
- Tabs.
- Accordion.
- Card.
- Tag.
- Badge.
- Toast.
- ConfirmDialog.
- Menu.
- TieredMenu.
- Breadcrumb.
- Toolbar.
- SplitButton.
- FileUpload.
- Skeleton.
- ProgressSpinner.
- Message.
- Stepper cuando un proceso realmente necesite varios pasos.

No recrear manualmente tablas, modales, selects, datepickers o componentes que PrimeVue ya resuelva correctamente.

---

# 4. DISEÑO GENERAL

La interfaz debe ser:

- Minimalista.
- Profesional.
- Clara.
- Modo claro.
- Responsive.
- Optimizada principalmente para computadora y tablet.
- Fácil de utilizar por personas sin conocimientos técnicos.
- Con pocas acciones visibles por pantalla.
- Con botones principales claramente identificables.
- Con navegación lateral simple.

## Layout principal

Debe incluir:

- Sidebar.
- Header.
- Breadcrumb.
- Área principal.
- Sistema de notificaciones mediante Toast.
- Confirmaciones mediante ConfirmDialog.

## Menú sugerido

- Dashboard
- Clientes
- Agenda
- Atenciones
- Servicios
- Paquetes
- Ventas
- Caja
- Inventario
- Personal
- Comisiones
- Reportes
- Configuración
- Usuarios y permisos

Los elementos visibles dependerán del rol del usuario.

---

# 5. SCSS

El SCSS debe estar correctamente organizado.

No colocar todos los estilos en un único archivo.

Estructura sugerida:

```text
resources/
└── scss/
    ├── app.scss
    ├── abstracts/
    │   ├── _variables.scss
    │   ├── _mixins.scss
    │   ├── _functions.scss
    │   └── _breakpoints.scss
    ├── base/
    │   ├── _reset.scss
    │   ├── _typography.scss
    │   └── _utilities.scss
    ├── layout/
    │   ├── _sidebar.scss
    │   ├── _header.scss
    │   ├── _content.scss
    │   └── _responsive.scss
    ├── components/
    │   ├── _forms.scss
    │   ├── _tables.scss
    │   ├── _dialogs.scss
    │   ├── _cards.scss
    │   └── _buttons.scss
    └── pages/
        ├── _dashboard.scss
        ├── _clients.scss
        ├── _appointments.scss
        └── _inventory.scss
```

## Reglas SCSS

- Utilizar variables globales.
- Evitar valores repetidos.
- Evitar `!important` salvo casos justificados.
- Evitar estilos inline.
- No modificar indiscriminadamente clases internas de PrimeVue.
- Crear overrides centralizados cuando PrimeVue necesite personalización.
- Mantener nombres de clases consistentes.
- Aplicar metodología similar a BEM cuando resulte útil.
- Mantener estilos específicos dentro del componente cuando realmente pertenezcan solo a ese componente.
- Mantener estilos globales en SCSS global.

---

# 6. PRINCIPIOS SOLID

El desarrollo debe respetar SOLID.

## S — Single Responsibility Principle

Cada clase debe tener una única responsabilidad.

Ejemplo:

Incorrecto:

```php
class SaleController
{
    public function store()
    {
        // validar
        // registrar venta
        // registrar pago
        // actualizar caja
        // actualizar inventario
        // calcular comisión
        // enviar notificación
    }
}
```

Correcto:

```text
SaleController
    ↓
CreateSaleAction
    ↓
PaymentService
CashService
InventoryService
CommissionService
```

---

## O — Open/Closed Principle

Las reglas deben poder ampliarse sin modificar excesivamente código existente.

Ejemplo:

Los métodos de pago no deben estar quemados dentro de múltiples componentes.

Deben obtenerse desde configuración o base de datos.

---

## L — Liskov Substitution Principle

Las implementaciones que utilicen interfaces deben mantener contratos predecibles.

---

## I — Interface Segregation Principle

No crear interfaces gigantes.

Ejemplo:

```text
InventoryReaderInterface
InventoryWriterInterface
InventoryAdjustmentInterface
```

en lugar de una interfaz con decenas de métodos sin relación directa.

---

## D — Dependency Inversion Principle

La lógica principal debe depender de abstracciones cuando exista una razón real para ello.

No crear interfaces innecesarias para cada clase.

Aplicar abstracciones principalmente en:

- Servicios externos.
- Repositorios complejos.
- Generación de documentos.
- Notificaciones.
- Almacenamiento de archivos.
- Integraciones futuras.

---

# 7. ARQUITECTURA BACKEND

Estructura sugerida:

```text
app/
├── Actions/
│   ├── Appointments/
│   ├── Attendances/
│   ├── Cash/
│   ├── Clients/
│   ├── Commissions/
│   ├── Inventory/
│   ├── Packages/
│   ├── Payments/
│   └── Sales/
├── DTOs/
├── Enums/
├── Events/
├── Exceptions/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Listeners/
├── Models/
├── Policies/
├── Services/
├── Support/
└── ValueObjects/
```

No es obligatorio crear todas las carpetas desde el inicio.

Crear únicamente las que aporten valor real.

---

# 8. ARQUITECTURA FRONTEND

Estructura sugerida:

```text
resources/js/
├── api/
│   ├── client.ts
│   ├── clients.api.ts
│   ├── appointments.api.ts
│   ├── attendances.api.ts
│   ├── sales.api.ts
│   └── inventory.api.ts
├── components/
│   ├── common/
│   ├── clients/
│   ├── appointments/
│   ├── sales/
│   └── inventory/
├── composables/
├── layouts/
├── pages/
│   ├── dashboard/
│   ├── clients/
│   ├── appointments/
│   ├── attendances/
│   ├── services/
│   ├── packages/
│   ├── sales/
│   ├── cash/
│   ├── inventory/
│   ├── staff/
│   ├── commissions/
│   ├── reports/
│   └── settings/
├── router/
├── stores/
├── types/
├── utils/
└── app.ts
```

---

# 9. REGLAS DE COMPONENTES VUE

Los componentes deben ser pequeños y reutilizables.

Evitar componentes de cientos o miles de líneas.

Ejemplo:

```text
ClientsPage.vue
├── ClientFilters.vue
├── ClientTable.vue
├── ClientFormDialog.vue
└── ClientStatusTag.vue
```

## Reglas

- Separar formularios complejos.
- Separar tablas.
- Separar filtros.
- Separar dialogs.
- Utilizar composables para lógica reutilizable.
- No repetir llamadas HTTP.
- Centralizar acceso al backend en `/api`.
- Evitar llamadas Axios directamente distribuidas en muchos componentes.
- Mantener estado local cuando no sea necesario Pinia.
- Utilizar Pinia solamente para estado realmente compartido.

---

# 10. RESPUESTAS API

Mantener una estructura consistente.

Ejemplo exitoso:

```json
{
  "success": true,
  "message": "Cliente registrado correctamente.",
  "data": {}
}
```

Ejemplo error:

```json
{
  "success": false,
  "message": "No se pudo registrar el cliente.",
  "errors": {}
}
```

Utilizar Laravel API Resources para serializar entidades cuando corresponda.

---

# 11. MANEJO DE TRANSACCIONES

Toda operación que modifique varias entidades relacionadas debe ejecutarse dentro de una transacción.

Ejemplos:

- Confirmar atención.
- Registrar una venta.
- Registrar venta + pagos.
- Registrar venta + movimiento de caja.
- Descontar inventario.
- Consumir una sesión de paquete.
- Generar comisión.

Ejemplo:

```php
DB::transaction(function () {
    // operación completa
});
```

Si una parte falla, no debe quedar información parcial.

---

# 12. ENUMS

Utilizar Enums para estados internos importantes.

Ejemplos:

```text
AppointmentStatus
- pending
- confirmed
- attended
- cancelled
- postponed
- no_show

PackageStatus
- active
- completed

CommissionStatus
- pending
- paid

UserStatus
- active
- inactive

CashSessionStatus
- open
- closed
```

Evitar strings repetidos directamente en diferentes partes del código.

---

# 13. DASHBOARD

El dashboard debe mostrar información operacional.

## Indicadores

- Ventas del día.
- Cantidad de citas del día.
- Citas pendientes.
- Sesiones pendientes de paquetes.
- Productos o insumos con stock bajo.
- Resumen de caja del día.

## Accesos rápidos

- Nueva cita.
- Nuevo cliente.
- Nueva venta.
- Registrar atención.

No desarrollar inicialmente un sistema avanzado de Business Intelligence.

No incluir múltiples gráficos si no son necesarios.

---

# 14. CLIENTES

## Información

Cada cliente debe permitir registrar:

- Código único automático.
- Nombre completo.
- DNI o documento de identificación.
- Fecha de nacimiento.
- Sexo.
- Celular.
- WhatsApp.
- Correo electrónico.
- Distrito.
- Dirección.
- Cómo conoció el centro.
- Fecha de registro.
- Estado activo/inactivo.
- Observaciones.
- Alergias.
- Restricciones.
- Contraindicaciones.
- Medicamentos.
- Información relevante para la atención.
- Fotografías.
- Documentos básicos.

## Ficha del cliente

Desde una única ficha debe poder consultarse:

- Datos generales.
- Historial de citas.
- Historial de atenciones.
- Historial de compras.
- Historial de pagos.
- Paquetes contratados.
- Sesiones utilizadas.
- Sesiones pendientes.
- Archivos adjuntos.

No obligar al usuario a ingresar a múltiples módulos para consultar el historial de un cliente.

---

# 15. HISTORIAL DE ATENCIONES

Registrar:

- Fecha.
- Cliente.
- Servicio.
- Especialista.
- Paquete utilizado si corresponde.
- Sesión.
- Observaciones.
- Medidas si aplican.
- Insumos utilizados.
- Usuario que registró la atención.
- Fecha de creación.

No desarrollar inicialmente un módulo clínico avanzado independiente.

---

# 16. AGENDA Y CITAS

La agenda debe ofrecer:

- Vista diaria.
- Vista semanal.
- Crear citas.
- Modificar citas.
- Cancelar citas.
- Reprogramar citas.
- Seleccionar cliente.
- Seleccionar servicio.
- Seleccionar especialista.
- Fecha.
- Hora.
- Duración aproximada.
- Observaciones.
- Estado.

Estados:

- Pendiente.
- Confirmada.
- Atendida.
- Cancelada.
- Postergada.
- No asistió.

Debe ser posible visualizar la agenda por especialista.

No agregar inicialmente:

- Inteligencia artificial.
- Optimización automática de horarios.
- Automatizaciones externas.
- Integraciones complejas de calendario.

---

# 17. SERVICIOS

Campos:

- Nombre.
- Categoría.
- Precio.
- Duración.
- Estado.
- Especialistas habilitados.
- Insumos asociados.

Categorías iniciales posibles:

- Facial.
- Corporal.
- Podología.
- Otras configurables.

El administrador debe poder modificar servicios sin intervención del programador.

---

# 18. PAQUETES

Los paquetes permiten vender múltiples sesiones.

Campos:

- Nombre.
- Cliente.
- Servicios incluidos.
- Cantidad total de sesiones.
- Precio.
- Fecha de compra.
- Sesiones utilizadas.
- Sesiones restantes.
- Estado.

Estados:

- Activo.
- Terminado.

Al confirmar una atención perteneciente a un paquete:

```text
sesiones_restantes = sesiones_restantes - 1
```

La operación debe formar parte de la misma transacción de confirmación de atención.

No permitir que las sesiones restantes sean negativas.

---

# 19. REGISTRO DE ATENCIÓN

Este es uno de los procesos principales.

Flujo:

```text
1. Seleccionar cliente
2. Seleccionar servicio o paquete
3. Seleccionar especialista
4. Registrar sesión cuando corresponda
5. Registrar observaciones
6. Cargar insumos asociados
7. Permitir modificar cantidades realmente utilizadas
8. Confirmar atención
```

Al confirmar:

```text
Confirmar atención
    ↓
Crear historial de atención
    ↓
Descontar sesión de paquete, si corresponde
    ↓
Crear movimientos de inventario
    ↓
Actualizar stock
    ↓
Generar comisión, si corresponde
```

Todo dentro de una transacción.

---

# 20. INSUMOS POR SERVICIO

Cada servicio podrá tener insumos asociados.

Ejemplo:

```text
Carboxiterapia

Aguja     2 unidades
Jeringa   1 unidad
Gasa      2 unidades
Guantes   2 unidades
```

Al iniciar el registro de atención:

1. Consultar insumos configurados.
2. Mostrar cantidades referenciales.
3. Permitir modificar cantidades.
4. Confirmar.
5. Descontar únicamente cantidades confirmadas.

La relación servicio-insumo debe guardar la cantidad referencial.

Ejemplo de tabla:

```text
service_supplies
- id
- service_id
- inventory_item_id
- default_quantity
```

---

# 21. VENTAS

Permitir:

- Venta de servicios.
- Venta de paquetes.
- Venta de productos.
- Descuentos.
- Adelantos.
- Separaciones.
- Saldo pendiente.
- Uno o varios métodos de pago.

Una venta puede contener múltiples conceptos.

Ejemplo:

```text
Venta
├── Servicio
├── Producto
└── Paquete
```

Considerar cabecera y detalle.

Ejemplo:

```text
sales
sale_items
payments
```

---

# 22. PAGOS

Métodos iniciales:

- Efectivo.
- Yape.
- Plin.
- Transferencia.
- POS.

Los métodos deben ser configurables desde administración.

Debe existir pago mixto.

Ejemplo:

```text
Venta: S/ 250

Yape       S/ 100
Efectivo   S/ 150
```

El total pagado no debe exceder el saldo de forma incorrecta.

---

# 23. CAJA

Funcionalidades:

- Apertura.
- Cierre.
- Ingresos automáticos por ventas.
- Egresos manuales.
- Descripción.
- Método de pago.
- Responsable.
- Total esperado.
- Total registrado.
- Diferencia.

Modelo sugerido:

```text
cash_sessions
cash_movements
```

Ejemplo de movimientos:

```text
SALE
EXPENSE
ADJUSTMENT
```

Nunca calcular el saldo únicamente modificando un campo sin conservar movimientos históricos.

---

# 24. INVENTARIO

Campos:

- Nombre.
- Categoría.
- Unidad de medida.
- Stock actual.
- Stock mínimo.
- Costo.
- Precio de venta.
- Proveedor.
- Estado.

Operaciones:

- Entrada.
- Salida.
- Consumo por atención.
- Venta.
- Ajuste manual.

Todo cambio de inventario debe generar un movimiento.

Modelo sugerido:

```text
inventory_items
inventory_movements
```

Tipos:

```text
PURCHASE
SERVICE_USAGE
SALE
MANUAL_IN
MANUAL_OUT
ADJUSTMENT
```

No modificar stock sin mantener trazabilidad.

---

# 25. PERSONAL

Campos:

- Nombre.
- Cargo.
- Celular.
- Estado.
- Usuario asociado cuando corresponda.
- Servicios que puede realizar.

Separar conceptualmente:

```text
employees
users
```

Un trabajador puede existir sin necesariamente tener acceso al sistema.

---

# 26. COMISIONES

Permitir reglas de comisión configurables.

Campos:

- Personal.
- Atención o venta relacionada.
- Servicio o producto.
- Monto base.
- Tipo de comisión.
- Porcentaje o monto fijo.
- Comisión generada.
- Estado.
- Fecha.

Estados:

- Pendiente.
- Pagada.

El cálculo debe ser automático de acuerdo con la regla configurada.

No almacenar únicamente el porcentaje.

Guardar también el monto calculado al momento de generar la comisión para mantener histórico.

---

# 27. REPORTES

Primera versión:

- Ventas por día.
- Ventas por semana.
- Ventas por mes.
- Ventas por servicio.
- Ventas por producto.
- Ventas por especialista.
- Ingresos de caja.
- Egresos de caja.
- Comisiones.
- Stock actual.
- Stock bajo.
- Clientes registrados.
- Paquetes activos.
- Sesiones pendientes.

Aplicar filtros por fechas cuando corresponda.

La exportación a Excel o PDF puede implementarse como funcionalidad adicional si se decide incluirla dentro del alcance.

---

# 28. BUSCADOR

Permitir búsqueda rápida de clientes mediante:

- Nombre.
- DNI.
- Celular.
- Código de cliente.

Utilizar búsquedas indexadas y paginación.

No cargar todos los clientes en memoria.

---

# 29. ROLES Y PERMISOS

Roles iniciales:

## Administrador

Acceso general.

Puede configurar:

- Servicios.
- Categorías.
- Paquetes.
- Inventario.
- Stock mínimo.
- Métodos de pago.
- Especialistas.
- Comisiones.
- Usuarios.
- Roles.

## Recepción

Según permisos:

- Clientes.
- Agenda.
- Ventas.
- Caja.

## Especialista

Según permisos:

- Su agenda.
- Información necesaria del cliente.
- Registro de atención.

Utilizar Policies / Gates en Laravel.

No depender únicamente de ocultar botones en frontend.

La seguridad debe validarse también en backend.

---

# 30. SEGURIDAD

Implementar:

- Login.
- Contraseñas hasheadas.
- Protección CSRF cuando corresponda.
- Validación backend.
- Autorización backend.
- Sanitización adecuada.
- Rate limiting en endpoints sensibles.
- Usuarios activos/inactivos.
- Roles y permisos.
- Logs de errores.
- Timestamps.
- Manejo seguro de archivos.
- Restricción de tipos y tamaños de archivos.
- Evitar exponer datos sensibles innecesariamente.

Nunca confiar únicamente en validaciones frontend.

---

# 31. AUDITORÍA BÁSICA

Como mínimo conservar:

- created_at.
- updated_at.
- created_by cuando sea necesario.
- updated_by cuando sea necesario.

Para operaciones sensibles se recomienda guardar historial.

Ejemplos:

- Ajustes de stock.
- Cierre de caja.
- Cambios de estado importantes.
- Pago de comisiones.
- Anulación de ventas.

No implementar inicialmente un sistema de auditoría excesivamente complejo.

---

# 32. ENTIDADES PRINCIPALES

Modelo inicial sugerido:

```text
users
roles
permissions

employees

clients
client_files

appointments

service_categories
services
service_employee

packages
package_services
client_packages
client_package_sessions

attendances
attendance_supplies

inventory_categories
inventory_items
inventory_movements
service_supplies

sales
sale_items
payments

payment_methods

cash_sessions
cash_movements

commission_rules
commissions
```

Claude debe revisar relaciones y normalización antes de crear las migraciones definitivas.

---

# 33. RELACIONES PRINCIPALES

```text
Client
├── hasMany Appointments
├── hasMany Attendances
├── hasMany Sales
└── hasMany ClientPackages

Service
├── belongsTo Category
├── belongsToMany Employees
└── belongsToMany InventoryItems

Appointment
├── belongsTo Client
├── belongsTo Service
└── belongsTo Employee

Attendance
├── belongsTo Client
├── belongsTo Service
├── belongsTo Employee
├── belongsTo Appointment
└── hasMany AttendanceSupplies

Sale
├── belongsTo Client
├── hasMany SaleItems
└── hasMany Payments

InventoryItem
└── hasMany InventoryMovements
```

---

# 34. REGLAS DE BASE DE DATOS

- Utilizar foreign keys.
- Utilizar índices.
- Indexar campos utilizados en búsquedas frecuentes.
- Evitar información duplicada.
- Utilizar `decimal` para dinero.
- Nunca utilizar `float` para montos monetarios.
- Manejar fechas correctamente.
- No utilizar JSON cuando una relación normalizada sea claramente mejor.
- Utilizar soft deletes únicamente cuando exista una razón funcional.
- No eliminar movimientos financieros o de inventario que deban conservar trazabilidad.

Campos recomendados para indexar:

```text
clients.document_number
clients.phone
clients.code
clients.full_name

appointments.date
appointments.employee_id
appointments.status

inventory_items.name
inventory_items.stock

sales.created_at
sales.client_id

commissions.employee_id
commissions.status
```

---

# 35. VALIDACIONES

Todas las operaciones deben tener Form Requests.

Ejemplos:

```text
StoreClientRequest
UpdateClientRequest

StoreAppointmentRequest
UpdateAppointmentRequest

StoreAttendanceRequest

StoreSaleRequest

OpenCashSessionRequest
CloseCashSessionRequest

AdjustInventoryRequest
```

No colocar arrays grandes de reglas directamente en controladores.

---

# 36. CONTROLADORES

Los controladores deben ser delgados.

Ejemplo:

```php
public function store(
    StoreClientRequest $request,
    CreateClientAction $action
) {
    $client = $action->execute($request->validated());

    return new ClientResource($client);
}
```

Evitar controladores con cientos de líneas.

---

# 37. ACTIONS

Utilizar Actions para casos de uso.

Ejemplos:

```text
CreateClientAction
CreateAppointmentAction
ConfirmAttendanceAction
CreateSaleAction
RegisterPaymentAction
OpenCashSessionAction
CloseCashSessionAction
AdjustInventoryAction
GenerateCommissionAction
```

Una Action representa una operación de negocio.

---

# 38. SERVICES

Utilizar Services para lógica reutilizable entre diferentes casos de uso.

Ejemplos:

```text
InventoryService
CommissionService
CashService
PackageSessionService
FileStorageService
```

No crear un único `ERPService` gigante.

---

# 39. EVENTOS DE DOMINIO

Cuando ayude a desacoplar procesos, utilizar Events.

Ejemplo:

```text
AttendanceConfirmed
SaleCompleted
PaymentRegistered
InventoryLow
```

Posibles listeners:

```text
ConsumePackageSession
RegisterInventoryConsumption
GenerateEmployeeCommission
```

Sin embargo:

**No utilizar eventos si hacen más difícil entender una operación que puede resolverse claramente dentro de una Action.**

La claridad tiene prioridad.

---

# 40. MANEJO DE ERRORES

Crear excepciones de negocio claras.

Ejemplos:

```text
InsufficientStockException
PackageWithoutRemainingSessionsException
CashSessionAlreadyOpenException
CashSessionNotOpenException
InvalidPaymentAmountException
```

Mostrar al usuario mensajes comprensibles.

No mostrar stack traces en producción.

---

# 41. PAGINACIÓN

Todas las tablas con crecimiento continuo deben utilizar paginación backend.

Ejemplos:

- Clientes.
- Ventas.
- Atenciones.
- Movimientos.
- Comisiones.

PrimeVue DataTable deberá trabajar preferentemente con lazy loading.

Ejemplo conceptual:

```text
DataTable lazy
    ↓
GET /api/clients?page=1&search=...
```

---

# 42. FILTROS

Crear filtros reutilizables.

Ejemplo backend:

```text
ClientFilter
SaleFilter
AppointmentFilter
CommissionFilter
```

Evitar concatenar múltiples `if` directamente en controladores.

---

# 43. FORMULARIOS

Todos los formularios deben:

- Mostrar errores backend.
- Mostrar loading.
- Bloquear doble envío.
- Mostrar mensaje de éxito.
- Limpiar estados correctamente.
- Pedir confirmación en acciones destructivas.

PrimeVue:

```text
Toast
ConfirmDialog
Message
```

---

# 44. TABLAS

Las tablas deben ofrecer solamente funciones necesarias.

Según el módulo:

- Paginación.
- Búsqueda.
- Ordenamiento.
- Filtros.
- Acciones.
- Estados visuales.

Evitar llenar cada tabla de botones.

Preferir menú de acciones cuando existan muchas opciones.

---

# 45. CONFIGURACIÓN INICIAL

El administrador debe configurar sin intervención del programador:

- Servicios.
- Categorías.
- Paquetes.
- Productos.
- Insumos.
- Stock mínimo.
- Métodos de pago.
- Especialistas.
- Porcentajes de comisión.
- Usuarios.
- Roles.

---

# 46. PROCESOS PRINCIPALES

## Nuevo cliente

```text
Nuevo cliente
    ↓
Validar información
    ↓
Crear cliente
    ↓
Mostrar ficha
```

## Nueva cita

```text
Cliente
    +
Servicio
    +
Especialista
    +
Fecha/Hora
    ↓
Crear cita
```

## Atención

```text
Cliente
    +
Servicio/Paquete
    +
Especialista
    +
Observaciones
    +
Insumos
    ↓
Confirmación
    ↓
Historial
    +
Sesión utilizada
    +
Descuento inventario
    +
Comisión
```

## Venta

```text
Producto/Servicio/Paquete
    ↓
Venta
    ↓
Pago
    ↓
Caja
```

---

# 47. PRUEBAS

Crear pruebas para procesos críticos.

Prioridad:

## Unitarias

- Cálculo de comisión.
- Consumo de sesiones.
- Cálculo de saldos.
- Validación de stock.

## Feature

- Crear cliente.
- Crear cita.
- Confirmar atención.
- Crear venta.
- Registrar pago.
- Abrir caja.
- Cerrar caja.
- Ajustar inventario.
- Validar permisos.

Casos críticos:

```text
No consumir dos sesiones accidentalmente.
No permitir stock negativo cuando la regla lo prohíba.
No registrar dos pagos por doble clic.
No generar doble comisión.
No registrar venta parcialmente si falla un pago.
```

---

# 48. ESTÁNDARES DE CÓDIGO

Backend:

- PSR-12.
- Laravel Pint.
- Nombres claros.
- Métodos pequeños.
- Clases pequeñas.
- Tipado cuando corresponda.
- Evitar código muerto.
- Evitar comentarios que expliquen código confuso; mejorar primero el código.
- Documentar únicamente decisiones no evidentes.

Frontend:

- ESLint.
- Prettier.
- Componentes reutilizables.
- Props tipadas.
- Emits definidos.
- Composables.
- No duplicar lógica.

---

# 49. GIT

Trabajar mediante ramas.

Ejemplo:

```text
main
develop

feature/clients
feature/appointments
feature/attendances
feature/inventory
feature/sales
feature/cash
```

Commits pequeños y descriptivos.

Ejemplo:

```text
feat(clients): add client registration
feat(appointments): add weekly calendar
fix(inventory): prevent negative stock
refactor(sales): move payment logic to action
```

---

# 50. ORDEN DE DESARROLLO

Desarrollar por etapas.

## Fase 1 — Base

- Proyecto Laravel.
- Vue.
- PrimeVue.
- SCSS.
- Autenticación.
- Layout.
- Roles.
- Permisos.

## Fase 2 — Clientes

- CRUD.
- Buscador.
- Ficha.
- Archivos.

## Fase 3 — Servicios y personal

- Servicios.
- Categorías.
- Especialistas.
- Relaciones.

## Fase 4 — Agenda

- Citas.
- Estados.
- Vista diaria.
- Vista semanal.

## Fase 5 — Inventario

- Productos.
- Insumos.
- Movimientos.
- Stock.

## Fase 6 — Paquetes

- Paquetes.
- Compra.
- Sesiones.
- Control de saldo de sesiones.

## Fase 7 — Atenciones

- Registro.
- Consumo de paquete.
- Consumo de inventario.
- Historial.

## Fase 8 — Ventas

- Venta.
- Detalle.
- Pago.
- Pago mixto.
- Saldos.

## Fase 9 — Caja

- Apertura.
- Movimientos.
- Egresos.
- Cierre.

## Fase 10 — Comisiones

- Configuración.
- Generación.
- Estado.
- Pago.

## Fase 11 — Dashboard y reportes

- Indicadores.
- Reportes.
- Filtros.

---

# 51. CRITERIO DE FINALIZACIÓN DE CADA MÓDULO

Un módulo no se considera terminado únicamente porque la pantalla funcione.

Debe incluir:

- Migration.
- Model.
- Relationships.
- Requests.
- Controller.
- Action/Service cuando corresponda.
- Policy.
- Routes.
- API Resource.
- Frontend.
- Loading.
- Manejo de errores.
- Permisos.
- Validaciones.
- Tests críticos.

---

# 52. RESTRICCIONES DE LA PRIMERA VERSIÓN

No desarrollar todavía:

- BI avanzado.
- Dashboard con decenas de gráficos.
- Inteligencia artificial.
- Predicción de consumo.
- Automatización inteligente de agenda.
- Integraciones externas complejas.
- Historia clínica avanzada independiente.
- Aplicación móvil nativa.
- Microservicios.
- Event sourcing.
- Arquitectura distribuida.
- Kubernetes.

Construir primero un **monolito modular mantenible**.

---

# 53. REGLA DE SIMPLICIDAD

Ante dos soluciones técnicamente correctas:

1. Elegir la más fácil de mantener.
2. Elegir la que necesite menos piezas.
3. Evitar abstracciones prematuras.
4. Evitar patrones por moda.
5. Aplicar SOLID donde realmente mejore el código.

El objetivo no es tener la arquitectura más compleja.

El objetivo es tener una arquitectura clara y sostenible.

---

# 54. INSTRUCCIONES PARA CLAUDE

Actúa como arquitecto de software y desarrollador senior especializado en:

- Laravel.
- Vue 3.
- PrimeVue.
- MySQL/MariaDB.
- SCSS.
- Arquitectura limpia.
- SOLID.

Debes desarrollar este ERP de forma incremental.

## Antes de generar código

Para cada módulo:

1. Analiza el requerimiento.
2. Identifica las entidades.
3. Define las relaciones.
4. Define las reglas de negocio.
5. Define permisos.
6. Define validaciones.
7. Define endpoints.
8. Define componentes Vue.
9. Define componentes PrimeVue necesarios.
10. Explica brevemente la estructura antes de programar.

## Al generar código

Debes:

- Entregar archivos completos cuando se soliciten.
- Indicar la ruta exacta de cada archivo.
- Evitar pseudocódigo si se está implementando.
- No omitir imports.
- No dejar fragmentos incompletos.
- No inventar campos sin explicar por qué son necesarios.
- Mantener nombres consistentes.
- Evitar lógica duplicada.
- Aplicar transacciones.
- Aplicar validaciones backend.
- Aplicar autorización backend.
- Crear código mantenible.

## No debes

- Crear controladores gigantes.
- Crear componentes Vue gigantes.
- Colocar consultas SQL directamente en Vue.
- Colocar lógica de negocio en templates.
- Colocar toda la lógica en Models.
- Utilizar `any` indiscriminadamente si se usa TypeScript.
- Hardcodear estados en múltiples archivos.
- Hardcodear métodos de pago.
- Hardcodear roles en múltiples lugares.
- Duplicar reglas.
- Hacer cambios fuera del módulo solicitado sin explicar el motivo.

---

# 55. FORMA DE TRABAJO SOLICITADA A CLAUDE

Trabajaremos módulo por módulo.

Cuando se solicite un módulo, responde en este orden:

```text
1. Análisis
2. Modelo de datos
3. Migraciones
4. Models
5. Enums
6. Requests
7. Actions / Services
8. Controllers
9. Policies
10. Routes
11. API Resources
12. Componentes Vue
13. PrimeVue utilizado
14. SCSS
15. Tests
16. Pasos para probarlo
```

Si una sección no aplica, indicarlo.

---

# 56. PRIMER OBJETIVO DE IMPLEMENTACIÓN

Empezar por:

```text
FASE 1
Configuración base del proyecto

Laravel
+
Vue 3
+
PrimeVue
+
SCSS
+
Autenticación
+
Layout
+
Roles y permisos
```

Después continuar con:

```text
FASE 2
Clientes
```

No comenzar implementando simultáneamente todos los módulos.

---

# 57. RESULTADO ESPERADO

El resultado final debe ser un ERP que permita operar el negocio diariamente mediante un flujo integrado:

```text
CLIENTE
   ↓
CITA
   ↓
ATENCIÓN
   ↓
SERVICIO / PAQUETE
   ↓
CONSUMO DE INSUMOS
   ↓
VENTA / PAGO
   ↓
CAJA
   ↓
COMISIÓN
   ↓
REPORTES
```

La arquitectura debe permitir incorporar posteriormente nuevas funcionalidades sin necesitar reescribir completamente los módulos existentes.

---

# 58. PRIORIDAD GLOBAL

En caso de duda, priorizar en este orden:

```text
1. Integridad de datos
2. Seguridad
3. Reglas de negocio
4. Facilidad de uso
5. Mantenibilidad
6. Rendimiento
7. Diseño visual
8. Funcionalidades adicionales
```

No sacrificar integridad de datos para simplificar una pantalla.

No sacrificar seguridad por velocidad de desarrollo.

No agregar complejidad técnica sin una razón clara.

---

# 59. DEFINICIÓN DEL MVP

El MVP incluye:

| Módulo | Incluido |
|---|---|
| Dashboard operacional | Sí |
| Clientes | Sí |
| Agenda y citas | Sí |
| Servicios | Sí |
| Paquetes y sesiones | Sí |
| Registro de atención | Sí |
| Insumos asociados a servicios | Sí |
| Descuento de inventario por atención | Sí |
| Ventas y pagos | Sí |
| Caja | Sí |
| Inventario básico | Sí |
| Personal | Sí |
| Comisiones | Sí |
| Reportes básicos | Sí |
| Usuarios | Sí |
| Roles y permisos | Sí |

---

# 60. NOTA FINAL PARA CLAUDE

No trates este proyecto como un ejemplo académico.

El código deberá quedar preparado para utilizarse en producción.

Antes de proponer una solución compleja, evalúa si existe una alternativa más simple.

Cuando existan decisiones arquitectónicas con varias opciones válidas:

1. Explica brevemente las opciones.
2. Recomienda una.
3. Indica por qué.
4. Continúa con la opción recomendada salvo que se indique lo contrario.

El objetivo es construir progresivamente un ERP profesional, simple de utilizar y sencillo de mantener.
