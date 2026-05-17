<template>
  <div class="login-bg min-h-screen flex items-center justify-center py-12 px-4">

    <!-- Ambient blobs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="max-w-md w-full relative z-10">
      <!-- Logo / title -->
      <div class="text-center mb-8">
        <div class="logo-ring mx-auto mb-4">
          <img v-if="branding.logo_url" :src="branding.logo_url" alt="Logo"
            class="h-14 w-14 rounded-full object-contain" />
          <span v-else class="text-4xl leading-none">🔧</span>
        </div>
        <h2 class="text-3xl font-bold text-white tracking-tight">{{ branding.shop_name }}</h2>
        <p class="mt-1.5 text-gray-400 text-sm">Sign in to your account</p>
      </div>

      <!-- Animated border card -->
      <div class="card-border-wrap">
        <div class="card-inner">
          <form @submit.prevent="submit" class="space-y-5">
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-1.5">Email</label>
              <input v-model="form.email" type="email" required
                class="login-input" placeholder="you@sirilmotors.com" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-1.5">Password</label>
              <input v-model="form.password" type="password" required
                class="login-input" placeholder="••••••••" />
            </div>
            <p v-if="error" class="text-sm text-red-400 bg-red-500/10 border border-red-500/20 px-3 py-2 rounded-lg">
              {{ error }}
            </p>
            <button type="submit" :disabled="loading" class="login-btn">
              <span v-if="loading" class="btn-spinner"></span>
              {{ loading ? 'Signing in…' : 'Sign in' }}
            </button>
          </form>
        </div>
      </div>

      <p class="text-center text-xs text-gray-600 mt-6">
        Siril Motors &copy; {{ new Date().getFullYear() }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const auth   = useAuthStore()
const router = useRouter()
const form   = reactive({ email: '', password: '' })
const error  = ref('')
const loading = ref(false)
const branding = ref({
  shop_name: import.meta.env.VITE_APP_NAME ?? 'Siril Motors',
  logo_url: '',
})

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/shop-branding')
    if (data?.shop_name) branding.value.shop_name = data.shop_name
    if (data?.logo_url) branding.value.logo_url = data.logo_url
  } catch {
    // Keep fallback branding if API is unavailable.
  }
})

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(form.email, form.password)
    router.push('/')
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Login failed'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* ── Background ─────────────────────────────────────────── */
.login-bg {
  background: radial-gradient(ellipse at 60% 20%, #0f172a 0%, #0a0f1e 100%);
  position: relative;
  overflow: hidden;
}

/* Ambient glow blobs */
.blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.18;
  pointer-events: none;
}
.blob-1 {
  width: 500px; height: 500px;
  background: #3b82f6;
  top: -120px; left: -100px;
  animation: drift 12s ease-in-out infinite alternate;
}
.blob-2 {
  width: 400px; height: 400px;
  background: #6366f1;
  bottom: -80px; right: -80px;
  animation: drift 15s ease-in-out infinite alternate-reverse;
}
@keyframes drift {
  from { transform: translate(0, 0) scale(1); }
  to   { transform: translate(40px, 30px) scale(1.08); }
}

/* ── Logo ring ──────────────────────────────────────────── */
.logo-ring {
  width: 72px; height: 72px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  backdrop-filter: blur(8px);
  box-shadow: 0 0 0 6px rgba(99,102,241,0.12), 0 0 32px rgba(99,102,241,0.2);
}

/* ── Animated border card ───────────────────────────────── */
.card-border-wrap {
  position: relative;
  border-radius: 1.25rem;
  padding: 2px;
  overflow: hidden;
}

/* The spinning gradient that forms the border */
.card-border-wrap::before {
  content: '';
  position: absolute;
  inset: -60%;
  background: conic-gradient(
    from 0deg,
    transparent 0deg,
    #3b82f6 60deg,
    #818cf8 120deg,
    #6366f1 180deg,
    #a78bfa 240deg,
    transparent 300deg
  );
  animation: spin-border 4s linear infinite;
  border-radius: 50%;
}

/* Soft glow behind the card */
.card-border-wrap::after {
  content: '';
  position: absolute;
  inset: 2px;
  border-radius: 1.125rem;
  background: #0f172a;
}

@keyframes spin-border {
  to { transform: rotate(360deg); }
}

/* Card inner content (sits above the gradient) */
.card-inner {
  position: relative;
  z-index: 1;
  background: rgba(15, 23, 42, 0.85);
  backdrop-filter: blur(16px);
  border-radius: 1.125rem;
  padding: 2rem;
}

/* ── Form elements ──────────────────────────────────────── */
.login-input {
  width: 100%;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  color: #f1f5f9;
  border-radius: 0.625rem;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.login-input::placeholder { color: #4b5563; }
.login-input:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.2);
}

/* ── Submit button ──────────────────────────────────────── */
.login-btn {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: #fff;
  font-weight: 600;
  font-size: 0.9rem;
  letter-spacing: 0.01em;
  padding: 0.7rem 1.5rem;
  border-radius: 0.625rem;
  border: none;
  cursor: pointer;
  transition: opacity 0.2s, box-shadow 0.2s;
  box-shadow: 0 4px 24px rgba(99,102,241,0.35);
}
.login-btn:hover:not(:disabled) {
  opacity: 0.92;
  box-shadow: 0 6px 32px rgba(99,102,241,0.5);
}
.login-btn:disabled { opacity: 0.55; cursor: not-allowed; }

/* Loading spinner dot */
.btn-spinner {
  width: 14px; height: 14px;
  border: 2px solid rgba(255,255,255,0.35);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
