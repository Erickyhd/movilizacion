<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Lock, Mail, Eye, EyeOff, ArrowRight, Bus } from 'lucide-vue-next';

const showPassword = ref(false);

const form = useForm({
  email: 'admin@movilizacion.local',
  password: 'admin1234',
  remember: true,
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <div class="login-page">
    <!-- Animated orbs background -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="grid-overlay"></div>

    <div class="login-card">
      <!-- Glowing top accent -->
      <div class="card-accent"></div>

      <!-- Brand Header -->
      <div class="brand-header">
        <div class="brand-icon-ring">
          <div class="brand-icon">
            <Bus class="w-7 h-7" />
          </div>
        </div>
        <h1 class="brand-name">SERVICIOS GENERALES<br/><span class="brand-highlight">MAGORI</span> E.I.R.L.</h1>
        <div class="brand-subtitle">
          <span class="subtitle-line"></span>
          <span class="subtitle-text">PLATAFORMA DE CONTROL</span>
          <span class="subtitle-line"></span>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="login-form">
        <!-- Error -->
        <div v-if="form.errors.email" class="form-error">
          <span>{{ form.errors.email }}</span>
        </div>

        <!-- Email -->
        <div class="field">
          <label>Correo Electrónico</label>
          <div class="input-box">
            <Mail class="field-icon" />
            <input v-model="form.email" type="email" required placeholder="correo@empresa.com" />
          </div>
        </div>

        <!-- Password -->
        <div class="field">
          <label>Contraseña</label>
          <div class="input-box">
            <Lock class="field-icon" />
            <input v-model="form.password" :type="showPassword ? 'text' : 'password'" required placeholder="••••••••" />
            <button type="button" @click="showPassword = !showPassword" class="eye-btn">
              <component :is="showPassword ? EyeOff : Eye" class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Remember -->
        <label class="remember">
          <input v-model="form.remember" type="checkbox" />
          <span>Recordar sesión</span>
        </label>

        <!-- Submit -->
        <button type="submit" :disabled="form.processing" class="submit-btn group">
          <span>Ingresar al Sistema</span>
          <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-1" />
        </button>
      </form>

      <div class="card-footer">
        <p>Sistema de Control de Movilización de Personal &copy; 2025</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ═══ PAGE ═══ */
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: #030712;
  position: relative;
  overflow: hidden;
  font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
}

/* Grid pattern overlay */
.grid-overlay {
  position: absolute;
  inset: 0;
  background-image: 
    linear-gradient(rgba(59, 130, 246, 0.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(59, 130, 246, 0.03) 1px, transparent 1px);
  background-size: 60px 60px;
  pointer-events: none;
}

/* Floating orbs */
.orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  pointer-events: none;
  animation: orbit 25s infinite ease-in-out;
}
.orb-1 {
  width: 500px; height: 500px;
  background: radial-gradient(circle, rgba(37, 99, 235, 0.2), transparent 70%);
  top: -15%; left: -10%;
  animation-delay: 0s;
}
.orb-2 {
  width: 400px; height: 400px;
  background: radial-gradient(circle, rgba(124, 58, 237, 0.18), transparent 70%);
  bottom: -10%; right: -8%;
  animation-delay: -8s;
}
.orb-3 {
  width: 300px; height: 300px;
  background: radial-gradient(circle, rgba(6, 182, 212, 0.12), transparent 70%);
  top: 40%; left: 60%;
  animation-delay: -16s;
}
@keyframes orbit {
  0%, 100% { transform: translate(0, 0); }
  25% { transform: translate(40px, -50px); }
  50% { transform: translate(-30px, 30px); }
  75% { transform: translate(20px, 15px); }
}

/* ═══ CARD ═══ */
.login-card {
  width: 100%;
  max-width: 400px;
  position: relative;
  z-index: 1;
  background: rgba(15, 23, 42, 0.85);
  border: 1px solid rgba(71, 85, 105, 0.35);
  border-radius: 28px;
  backdrop-filter: blur(40px);
  box-shadow:
    0 0 80px rgba(59, 130, 246, 0.06),
    0 32px 64px -12px rgba(0, 0, 0, 0.6);
  overflow: hidden;
}

.card-accent {
  height: 2px;
  background: linear-gradient(90deg, transparent 5%, #2563eb 30%, #7c3aed 50%, #06b6d4 70%, transparent 95%);
  animation: shimmer 4s ease-in-out infinite;
}
@keyframes shimmer {
  0%, 100% { opacity: 0.7; }
  50% { opacity: 1; }
}

/* ═══ BRAND ═══ */
.brand-header {
  padding: 2.5rem 2rem 1.5rem;
  text-align: center;
}

.brand-icon-ring {
  width: 64px;
  height: 64px;
  margin: 0 auto 1.25rem;
  border-radius: 20px;
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.15), rgba(124, 58, 237, 0.15));
  border: 1px solid rgba(59, 130, 246, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}
.brand-icon-ring::before {
  content: '';
  position: absolute;
  inset: -4px;
  border-radius: 24px;
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), transparent, rgba(124, 58, 237, 0.08));
  z-index: -1;
}
.brand-icon {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  background: linear-gradient(135deg, #2563eb, #7c3aed);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 4px 16px rgba(37, 99, 235, 0.35);
}

.brand-name {
  font-size: 0.85rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  line-height: 1.5;
  color: #cbd5e1;
}
.brand-highlight {
  font-size: 1.6rem;
  font-weight: 900;
  letter-spacing: 0.08em;
  background: linear-gradient(135deg, #60a5fa, #a78bfa, #22d3ee);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  display: inline-block;
}

.brand-subtitle {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 1rem;
}
.subtitle-line {
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(100, 116, 139, 0.3), transparent);
}
.subtitle-text {
  font-size: 0.55rem;
  font-weight: 700;
  letter-spacing: 0.2em;
  color: rgba(148, 163, 184, 0.5);
}

/* ═══ FORM ═══ */
.login-form {
  padding: 0 2rem 2rem;
}

.form-error {
  padding: 10px 14px;
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.25);
  border-radius: 12px;
  color: #fca5a5;
  font-size: 0.78rem;
  margin-bottom: 1rem;
}

.field { margin-bottom: 1.25rem; }
.field label {
  display: block;
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: #64748b;
  margin-bottom: 8px;
}

.input-box {
  position: relative;
  display: flex;
  align-items: center;
}
.field-icon {
  position: absolute;
  left: 14px;
  width: 17px;
  height: 17px;
  color: #334155;
  pointer-events: none;
  transition: color 0.25s;
}
.input-box:focus-within .field-icon { color: #3b82f6; }

.input-box input {
  width: 100%;
  background: rgba(2, 6, 23, 0.7);
  border: 1px solid rgba(51, 65, 85, 0.5);
  border-radius: 14px;
  padding: 13px 48px 13px 44px;
  color: #e2e8f0;
  font-size: 0.85rem;
  font-weight: 500;
  outline: none;
  transition: all 0.25s;
}
.input-box input::placeholder { color: rgba(71, 85, 105, 0.6); }
.input-box input:focus {
  border-color: rgba(59, 130, 246, 0.5);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), 0 0 24px rgba(59, 130, 246, 0.05);
}

.eye-btn {
  position: absolute;
  right: 12px;
  padding: 6px;
  color: #475569;
  background: none;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}
.eye-btn:hover { color: #94a3b8; background: rgba(71, 85, 105, 0.3); }

.remember {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  user-select: none;
  margin-bottom: 0.25rem;
}
.remember input {
  width: 16px;
  height: 16px;
  border-radius: 5px;
  accent-color: #3b82f6;
}
.remember span {
  font-size: 0.78rem;
  font-weight: 500;
  color: #64748b;
}

.submit-btn {
  width: 100%;
  margin-top: 1.25rem;
  padding: 14px 20px;
  background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
  color: white;
  font-weight: 700;
  font-size: 0.88rem;
  border: none;
  border-radius: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 20px rgba(37, 99, 235, 0.25), 0 0 40px rgba(124, 58, 237, 0.08);
  position: relative;
  overflow: hidden;
}
.submit-btn::after {
  content: '';
  position: absolute;
  top: 0; left: -100%;
  width: 100%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.08), transparent);
  transition: left 0.6s;
}
.submit-btn:hover::after { left: 100%; }
.submit-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(37, 99, 235, 0.35), 0 0 60px rgba(124, 58, 237, 0.12);
}
.submit-btn:active { transform: translateY(0); }
.submit-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

.card-footer {
  padding: 1rem 2rem 1.5rem;
  text-align: center;
  border-top: 1px solid rgba(51, 65, 85, 0.2);
}
.card-footer p {
  font-size: 0.6rem;
  color: rgba(100, 116, 139, 0.4);
  letter-spacing: 0.04em;
}
</style>