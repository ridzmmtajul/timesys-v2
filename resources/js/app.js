import './bootstrap';
import { createApp } from 'vue';
import App from './components/App.vue';

// Import Font Awesome if needed (or use CDN in your layout)
// import '@fortawesome/fontawesome-free/css/all.css';

const app = createApp(App);
app.mount('#app');