import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import axios from 'axios';
import Swal from 'sweetalert2';

// Components
import ActivityForm from './components/ActivityForm.vue';
import ActivityList from './components/ActivityList.vue';
import WeatherForecast from './components/WeatherForecast.vue';
import Dashboard from './components/Dashboard.vue';
import LoginForm from './components/auth/LoginForm.vue';
import RegisterForm from './components/auth/RegisterForm.vue';

// Set up axios
axios.defaults.baseURL = '/api';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Add auth token to requests
const token = localStorage.getItem('auth_token');
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// Add request interceptor to handle auth errors
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user_data');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

const app = createApp({});
const pinia = createPinia();

app.use(pinia);

// Register components globally
app.component('activity-form', ActivityForm);
app.component('activity-list', ActivityList);
app.component('weather-forecast', WeatherForecast);
app.component('dashboard-component', Dashboard);
app.component('login-form', LoginForm);
app.component('register-form', RegisterForm);

// Make utilities available globally
app.config.globalProperties.$http = axios;
app.config.globalProperties.$swal = Swal;

app.mount('#app');