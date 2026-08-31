import { definePreset } from '@primeuix/themes'
import Aura from '@primeuix/themes/aura'

/**
 * Preset del ERP. Solo redefine la paleta primaria y la superficie;
 * el resto se hereda de Aura para no mantener tokens que no cambiamos.
 * La paleta coincide con $color-primary de abstracts/_variables.scss.
 */
export const SinExcusasPreset = definePreset(Aura, {
  semantic: {
    primary: {
      50: '#eef1fd',
      100: '#dde3fb',
      200: '#bbc7f7',
      300: '#99abf3',
      400: '#8b9ff4',
      500: '#667eea',
      600: '#4f66d1',
      700: '#3f51a8',
      800: '#2f3d7e',
      900: '#1f2955',
      950: '#10142b',
    },
    colorScheme: {
      light: {
        surface: {
          0: '#ffffff',
          50: '#f9fafb',
          100: '#f3f4f6',
          200: '#e5e7eb',
          300: '#d1d5db',
          400: '#9ca3af',
          500: '#6b7280',
          600: '#4b5563',
          700: '#374151',
          800: '#1f2937',
          900: '#111827',
          950: '#030712',
        },
      },
    },
  },
})

export const primeVueOptions = {
  ripple: true,
  theme: {
    preset: SinExcusasPreset,
    options: {
      // La spec pide modo claro: usamos un selector que nunca coincide
      // para que el tema no siga el prefers-color-scheme del sistema.
      darkModeSelector: '.app-dark-mode-disabled',
      cssLayer: {
        name: 'primevue',
        order: 'primevue, app',
      },
    },
  },
}
