/**
 * Formato de moneda y fechas para toda la interfaz.
 *
 * Centralizado para que el símbolo, los decimales y la localidad se definan
 * una sola vez: repartir `toLocaleString` por cada tabla termina produciendo
 * formatos distintos en pantallas distintas.
 */
const CURRENCY = 'PEN'
const LOCALE = 'es-PE'

const currencyFormatter = new Intl.NumberFormat(LOCALE, {
  style: 'currency',
  currency: CURRENCY,
  minimumFractionDigits: 2,
})

const dateFormatter = new Intl.DateTimeFormat(LOCALE, {
  day: '2-digit',
  month: '2-digit',
  year: 'numeric',
})

const dateTimeFormatter = new Intl.DateTimeFormat(LOCALE, {
  day: '2-digit',
  month: '2-digit',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
})

export function useFormat() {
  const money = (value: number | string | null | undefined): string =>
    currencyFormatter.format(Number(value ?? 0))

  const date = (value: string | Date | null | undefined): string =>
    value ? dateFormatter.format(new Date(value)) : '—'

  const dateTime = (value: string | Date | null | undefined): string =>
    value ? dateTimeFormatter.format(new Date(value)) : '—'

  /** Cantidades de inventario: sin decimales cuando son enteras. */
  const quantity = (value: number | string | null | undefined, unit?: string): string => {
    const number = Number(value ?? 0)
    const text = Number.isInteger(number) ? String(number) : number.toFixed(2)
    return unit ? `${text} ${unit}` : text
  }

  /** Fecha en el formato que espera el backend (YYYY-MM-DD). */
  const toIsoDate = (value: Date | string | null | undefined): string | null => {
    if (!value) return null
    const parsed = value instanceof Date ? value : new Date(value)
    if (Number.isNaN(parsed.getTime())) return null
    const month = String(parsed.getMonth() + 1).padStart(2, '0')
    const day = String(parsed.getDate()).padStart(2, '0')
    return `${parsed.getFullYear()}-${month}-${day}`
  }

  return { money, date, dateTime, quantity, toIsoDate }
}
