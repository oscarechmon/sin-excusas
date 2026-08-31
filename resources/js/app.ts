import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import ConfirmationService from 'primevue/confirmationservice'
import Tooltip from 'primevue/tooltip'
import 'primeicons/primeicons.css'

import App from './App.vue'
import router from './router'
import { primeVueOptions } from './plugins/primevue-theme'
import '../scss/app.scss'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(PrimeVue, primeVueOptions)
app.use(ToastService)
app.use(ConfirmationService)
app.directive('tooltip', Tooltip)

app.mount('#app')
