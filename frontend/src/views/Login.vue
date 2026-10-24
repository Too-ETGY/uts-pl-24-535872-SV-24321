<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api';

const router = useRouter();

const email = ref('');
const password = ref('');
const errorMessage = ref('');
const loading = ref(false);

const handleLogin = async () => {
  errorMessage.value = '';
  loading.value = true;

  try {
    const response = await api.post('/login', {
      email: email.value,
      password: password.value,
    });

    if (response.data && response.data.token) {
      localStorage.setItem('token', response.data.token);
      if (response.data.user) {
        localStorage.setItem('user', JSON.stringify(response.data.user));
      }
      router.push({ name: 'Notes' });
    } else {
      errorMessage.value = 'Respons login tidak valid dari server.';
    }
  } catch (error) {
    if (error.response && error.response.data && error.response.data.message) {
      errorMessage.value = error.response.data.message;
    } else if (error.response && error.response.status === 422) {
      errorMessage.value = 'Data masukan tidak valid. Periksa email dan kata sandi.';
    } else if (error.message) {
      errorMessage.value = error.message;
    } else {
      errorMessage.value = 'Gagal masuk. Periksa jaringan atau server.';
    }
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="auth-container">
    <div class="auth-card">
      <div class="auth-badge">DOKUMEN RESMI // RAHASIA UMUM</div>
      <h1 class="auth-title">Buku Keluhan Rakyat</h1>
      <p class="auth-subtitle">
        Portal pengaduan satire & unek-unek birokrasi. Masuk untuk mencatat keluhan Anda sebelum disensor.
      </p>

      <div v-if="errorMessage" class="alert alert-danger" role="alert">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="handleLogin" class="auth-form">
        <div class="form-group">
          <label for="email" class="form-label">Email Warga Terdaftar</label>
          <input
            id="email"
            v-model="email"
            type="email"
            class="form-input"
            placeholder="nama@warga.id"
            required
            autocomplete="email"
          />
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Kata Sandi Rahasia</label>
          <input
            id="password"
            v-model="password"
            type="password"
            class="form-input"
            placeholder="••••••••"
            required
            autocomplete="current-password"
          />
        </div>

        <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
          <span v-if="loading">Memeriksa Arsip...</span>
          <span v-else>Masuk ke Buku Catatan</span>
        </button>
      </form>

      <div class="auth-hint">
        <small>Akun demo: <code>critic@example.com</code> / <code>password</code></small>
      </div>
    </div>
  </div>
</template>
