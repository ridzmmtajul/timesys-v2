import { ref } from 'vue';
import axios from 'axios';

export default function useAuth() {
    const is_loading = ref(false);
    const errors = ref({});

    const login = async (credentials) => {
        is_loading.value = true;
        errors.value = {};

        try {
            const response = await axios.post('/api/login', credentials);
            const { token, user } = response.data;

            localStorage.setItem('timesys_token', token);
            localStorage.setItem('timesys_user', JSON.stringify(user));
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

            is_loading.value = false;
            return user;
        } catch (e) {
            is_loading.value = false;
            if (e.response?.status === 401) {
                errors.value = { credentials: e.response.data.message };
            } else if (e.response?.status === 422) {
                errors.value = e.response.data;
            } else {
                errors.value = { credentials: 'Something went wrong. Please try again.' };
            }
            return null;
        }
    };

    const logout = async () => {
        try {
            await axios.post('/api/logout');
        } catch (_) {
            // ignore — clear local state regardless
        } finally {
            localStorage.removeItem('timesys_token');
            localStorage.removeItem('timesys_user');
            delete axios.defaults.headers.common['Authorization'];
        }
    };

    const setPassword = async (data) => {
        is_loading.value = true;
        errors.value = {};

        try {
            const response = await axios.post('/api/set-password', data);
            const user = response.data.user;
            localStorage.setItem('timesys_user', JSON.stringify(user));
            is_loading.value = false;
            return true;
        } catch (e) {
            is_loading.value = false;
            if (e.response?.status === 422) {
                errors.value = e.response.data;
            } else {
                errors.value = { password: ['Something went wrong. Please try again.'] };
            }
            return false;
        }
    };

    const getAuthUser = () => {
        const stored = localStorage.getItem('timesys_user');
        return stored ? JSON.parse(stored) : null;
    };

    return { is_loading, errors, login, logout, setPassword, getAuthUser };
}
