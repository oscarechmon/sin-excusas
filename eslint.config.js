import js from '@eslint/js'
import pluginVue from 'eslint-plugin-vue'
import prettier from 'eslint-plugin-prettier'

export default [
  {
    ignores: ['node_modules', 'dist', 'public'],
  },
  {
    files: ['resources/js/**/*.{js,ts,vue}'],
    languageOptions: {
      parser: 'vue-eslint-parser',
      parserOptions: {
        parser: '@typescript-eslint/parser',
        ecmaVersion: 2020,
        sourceType: 'module',
      },
    },
    plugins: {
      vue: pluginVue,
      prettier: prettier,
    },
    rules: {
      ...js.configs.recommended.rules,
      ...pluginVue.configs['vue3-recommended'].rules,
      'prettier/prettier': 'error',
      'vue/multi-word-component-names': 'off',
    },
  },
]
