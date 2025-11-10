import { createApp } from 'vue'
import App from './App.vue'
import router from './router'

import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import 'remixicon/fonts/remixicon.css'

import 'swiper/css'
import 'swiper/css/pagination';
import './styles/app.css'



const app = createApp(App)

app.use(router)

app.mount('#app')
