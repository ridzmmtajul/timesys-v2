<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import useAuth from '../../composables/auth.js';

const router = useRouter();
const { login, is_loading, errors } = useAuth();

const logoUrl = '/images/logo.png';

const form = ref({
    username: '',
    password: '',
});

const showPassword = ref(false);

const handleLogin = async () => {
    const user = await login(form.value);
    if (user) {
        router.push(user.isNew ? '/set-password' : '/biometric');
    }
};
</script>

<template>
    <div class="login-page">
        <div class="login-card">
            <div class="logo-body">
                <img :src="logoUrl" alt="Logo" class="login-logo" />
            </div>

            <div class="login-header">
                <h2 class="login-title">Sign in to your account</h2>
                <!-- <p class="login-sub">Enter your credentials to continue</p> -->
            </div>

            <form class="login-form" @submit.prevent="handleLogin">
                <div class="login-field" :class="{ 'is-error': errors.username }">
                    <label class="login-label">Username</label>
                    <div class="login-input-wrap">
                        <i class="mdi mdi-account-outline login-input-icon"></i>
                        <input
                            v-model="form.username"
                            type="text"
                            class="login-input"
                            placeholder="Enter your username"
                            autocomplete="username"
                            required
                        />
                    </div>
                    <span v-if="errors.username" class="login-error">{{ errors.username[0] ?? errors.username }}</span>
                </div>

                <div class="login-field" :class="{ 'is-error': errors.password }">
                    <label class="login-label">Password</label>
                    <div class="login-input-wrap">
                        <i class="mdi mdi-lock-outline login-input-icon"></i>
                        <input
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            class="login-input login-input--with-toggle"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        />
                        <button
                            type="button"
                            class="login-toggle"
                            @click="showPassword = !showPassword"
                            tabindex="-1"
                        >
                            <i :class="showPassword ? 'mdi mdi-eye-off-outline' : 'mdi mdi-eye-outline'"></i>
                        </button>
                    </div>
                    <span v-if="errors.password" class="login-error">{{ errors.password[0] ?? errors.password }}</span>
                </div>

                <div v-if="errors.credentials" class="login-alert">
                    <i class="mdi mdi-alert-circle-outline"></i>
                    {{ errors.credentials }}
                </div>

                <button
                    type="submit"
                    class="login-btn"
                    :disabled="is_loading || !form.username || !form.password"
                >
                    <span v-if="is_loading" class="login-spinner"></span>
                    <span v-else>Sign In</span>
                </button>
            </form>
        </div>
    </div>
</template>

<style scoped>
.login-page {
    width: 100vw;
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background:
        radial-gradient(circle at top left, rgba(31, 191, 184, 0.16), transparent 28%),
        radial-gradient(circle at bottom right, rgba(63, 109, 199, 0.2), transparent 32%),
        linear-gradient(135deg, #071029 0%, #091737 45%, #0c1730 100%);
}

.login-card {
    width: 100%;
    max-width: 420px;
    background: linear-gradient(160deg, #0e1c3a 0%, #0a1228 100%);
    border: 1px solid rgba(108, 143, 214, 0.15);
    border-radius: 20px;
    padding: 40px 36px 36px;
    box-shadow: 0 24px 64px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(31, 191, 184, 0.06);
}

.logo-body {
    display: flex;
    justify-content: center;
    margin-bottom: 24px;
}

.login-brand {
    display: flex;
    text-align: center;
    gap: 12px;
    margin-bottom: 32px;
}

.login-logo {
    width: 80px;
    height: 80px;
    object-fit: contain;
}

.login-brand-name {
    display: block;
    font-weight: 700;
    font-size: 20px;
    letter-spacing: -0.3px;
    color: white;
}

.login-brand-sub {
    display: block;
    margin-top: 1px;
    font-size: 12px;
    color: #8aa0d7;
}

.login-header {
    margin-bottom: 65px;
    text-align: center;
}

.login-title {
    font-size: 20px;
    font-weight: 600;
    color: #e2e8f0;
    margin-bottom: 4px;
}

.login-sub {
    font-size: 13px;
    color: #8aa0d7;
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.login-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.login-label {
    font-size: 13px;
    font-weight: 500;
    color: #c8d6f0;
}

.login-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.login-input-icon {
    position: absolute;
    left: 13px;
    font-size: 16px;
    color: #5a78b0;
    pointer-events: none;
}

.login-input {
    width: 100%;
    height: 44px;
    padding: 0 14px 0 38px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(108, 143, 214, 0.2);
    border-radius: 10px;
    color: #e2e8f0;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
}

.login-input--with-toggle {
    padding-right: 42px;
}

.login-input::placeholder {
    color: #4a638f;
}

.login-input:focus {
    border-color: rgba(31, 191, 184, 0.5);
    background: rgba(31, 191, 184, 0.04);
}

.login-field.is-error .login-input {
    border-color: rgba(248, 113, 113, 0.5);
}

.login-toggle {
    position: absolute;
    right: 11px;
    background: none;
    border: none;
    cursor: pointer;
    color: #5a78b0;
    font-size: 16px;
    padding: 4px;
    display: flex;
    align-items: center;
    transition: color 0.2s;
}

.login-toggle:hover {
    color: #8aa0d7;
}

.login-error {
    font-size: 12px;
    color: #f87171;
}

.login-alert {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: rgba(248, 113, 113, 0.08);
    border: 1px solid rgba(248, 113, 113, 0.2);
    border-radius: 10px;
    color: #f87171;
    font-size: 13px;
}

.login-alert i {
    font-size: 16px;
    flex-shrink: 0;
}

.login-btn {
    width: 100%;
    height: 46px;
    margin-top: 4px;
    background: linear-gradient(135deg, #1fbfb8 0%, #05716c 100%);
    border: none;
    border-radius: 10px;
    color: white;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s, transform 0.15s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.login-btn:hover:not(:disabled) {
    opacity: 0.9;
    transform: translateY(-1px);
}

.login-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.login-spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
