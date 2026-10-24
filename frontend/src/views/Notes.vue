<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api';

const router = useRouter();

const notes = ref([]);
const loading = ref(false);
const submitting = ref(false);
const deletingId = ref(null);
const errorMessage = ref('');
const successMessage = ref('');
const currentUser = ref(null);

// Form state
const isEditing = ref(false);
const editingId = ref(null);
const formTitle = ref('');
const formContent = ref('');
const formError = ref('');

const loadUser = () => {
  const cached = localStorage.getItem('user');
  if (cached) {
    try {
      currentUser.value = JSON.parse(cached);
    } catch {
      currentUser.value = null;
    }
  }
};

const fetchNotes = async () => {
  loading.value = true;
  errorMessage.value = '';
  try {
    const res = await api.get('/notes');
    notes.value = Array.isArray(res.data) ? res.data : [];
  } catch (err) {
    if (err.response && err.response.data && err.response.data.message) {
      errorMessage.value = err.response.data.message;
    } else {
      errorMessage.value = 'Gagal memuat catatan keluhan dari server.';
    }
  } finally {
    loading.value = false;
  }
};

const resetForm = () => {
  isEditing.value = false;
  editingId.value = null;
  formTitle.value = '';
  formContent.value = '';
  formError.value = '';
};

const startEdit = (note) => {
  isEditing.value = true;
  editingId.value = note.id;
  formTitle.value = note.title;
  formContent.value = note.content;
  formError.value = '';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const handleSubmit = async () => {
  formError.value = '';
  successMessage.value = '';

  if (!formTitle.value.trim() || !formContent.value.trim()) {
    formError.value = 'Judul keluhan dan uraian tidak boleh kosong.';
    return;
  }

  submitting.value = true;

  try {
    if (isEditing.value) {
      const res = await api.put(`/notes/${editingId.value}`, {
        title: formTitle.value.trim(),
        content: formContent.value.trim(),
      });
      // Replace updated item in local list
      const idx = notes.value.findIndex((n) => n.id === editingId.value);
      if (idx !== -1) {
        notes.value[idx] = res.data;
      }
      successMessage.value = 'Revisi keluhan berhasil diarsipkan!';
    } else {
      const res = await api.post('/notes', {
        title: formTitle.value.trim(),
        content: formContent.value.trim(),
      });
      // Prepend newly created item
      notes.value.unshift(res.data);
      successMessage.value = 'Keluhan baru berhasil dicatat dalam berkas!';
    }
    resetForm();
  } catch (err) {
    if (err.response && err.response.status === 422 && err.response.data.errors) {
      const errors = err.response.data.errors;
      formError.value = Object.values(errors).flat().join(' ');
    } else if (err.response && err.response.data && err.response.data.message) {
      formError.value = err.response.data.message;
    } else {
      formError.value = 'Operasi gagal. Silakan coba lagi.';
    }
  } finally {
    submitting.value = false;
  }
};

const handleDelete = async (note) => {
  const confirmed = window.confirm(
    `Apakah Anda yakin ingin memusnahkan keluhan berkas #${note.id}: "${note.title}"? Tindakan ini tidak dapat dibatalkan.`
  );
  if (!confirmed) return;

  deletingId.value = note.id;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    await api.delete(`/notes/${note.id}`);
    notes.value = notes.value.filter((n) => n.id !== note.id);
    successMessage.value = `Berkas #${note.id} berhasil dimusnahkan.`;
    if (isEditing.value && editingId.value === note.id) {
      resetForm();
    }
  } catch (err) {
    if (err.response && err.response.data && err.response.data.message) {
      errorMessage.value = err.response.data.message;
    } else {
      errorMessage.value = 'Gagal menghapus keluhan.';
    }
  } finally {
    deletingId.value = null;
  }
};

const handleLogout = async () => {
  try {
    await api.post('/logout');
  } catch {
    // Revocation error can be ignored on client-side logout
  } finally {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    router.push({ name: 'Login' });
  }
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

onMounted(() => {
  loadUser();
  fetchNotes();
});
</script>

<template>
  <div class="notebook-container">
    <header class="notebook-header">
      <div class="header-branding">
        <span class="stamp-badge">KLASIFIKASI: KELUHAN RESMI</span>
        <h1 class="header-title">Buku Catatan Keluhan Birokrasi</h1>
        <p class="header-tagline">
          Arsip keluhan satir pribadi warga terhadap tata kelola negara.
        </p>
      </div>

      <div class="user-controls">
        <div class="user-info" v-if="currentUser">
          <span class="user-label">Warga Pelapor:</span>
          <strong>{{ currentUser.name }}</strong>
          <span class="user-email">({{ currentUser.email }})</span>
        </div>
        <button @click="handleLogout" class="btn btn-secondary btn-sm" aria-label="Keluar dari sistem">
          Log Keluar Arsip
        </button>
      </div>
    </header>

    <div v-if="successMessage" class="alert alert-success" role="status">
      {{ successMessage }}
    </div>

    <div v-if="errorMessage" class="alert alert-danger" role="alert">
      {{ errorMessage }}
    </div>

    <main class="notebook-grid">
      <!-- Left Column: Form -->
      <section class="panel editor-panel" aria-labelledby="form-heading">
        <div class="panel-header">
          <h2 id="form-heading" class="panel-title">
            {{ isEditing ? `Revisi Berkas Keluhan #${editingId}` : 'Catat Keluhan Baru' }}
          </h2>
          <span class="panel-hint">
            {{ isEditing ? 'Mode sunting aktif' : 'Wajib mencantumkan data' }}
          </span>
        </div>

        <div v-if="formError" class="alert alert-danger" role="alert">
          {{ formError }}
        </div>

        <form @submit.prevent="handleSubmit" class="note-form">
          <div class="form-group">
            <label for="note-title" class="form-label">Subjek Keluhan / Pokok Perkara</label>
            <input
              id="note-title"
              v-model="formTitle"
              type="text"
              class="form-input"
              placeholder="Contoh: Aspal Jalan Menguap Setelah Hujan Pertama"
              required
              minlength="3"
              maxlength="255"
            />
          </div>

          <div class="form-group">
            <label for="note-content" class="form-label">Uraian Satir & Kronologi Kejadian</label>
            <textarea
              id="note-content"
              v-model="formContent"
              class="form-textarea"
              rows="6"
              placeholder="Jelaskan secara mendalam ironi birokrasi yang Anda temukan di lapangan..."
              required
              minlength="3"
            ></textarea>
          </div>

          <div class="form-actions">
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="submitting"
            >
              <span v-if="submitting">Mengarsipkan...</span>
              <span v-else>{{ isEditing ? 'Simpan Revisi' : 'Arsipkan ke Berkas' }}</span>
            </button>
            <button
              v-if="isEditing"
              type="button"
              @click="resetForm"
              class="btn btn-ghost"
              :disabled="submitting"
            >
              Batalkan Revisi
            </button>
          </div>
        </form>
      </section>

      <!-- Right Column: Notes List -->
      <section class="panel list-panel" aria-labelledby="list-heading">
        <div class="panel-header">
          <h2 id="list-heading" class="panel-title">
            Daftar Berkas Keluhan Anda
            <span class="count-badge" v-if="!loading">({{ notes.length }})</span>
          </h2>
          <button
            @click="fetchNotes"
            class="btn btn-ghost btn-sm"
            :disabled="loading"
            title="Muat ulang catatan"
          >
            Segarkan
          </button>
        </div>

        <div v-if="loading" class="state-placeholder">
          <div class="spinner"></div>
          <p>Membuka lemari arsip negara...</p>
        </div>

        <div v-else-if="notes.length === 0" class="empty-placeholder">
          <div class="empty-icon">📂</div>
          <h3>Belum ada berkas keluhan tersimpan.</h3>
          <p>
            Semua urusan tampak berjalan lancar, atau warga belum sempat mengeluh?
            Gunakan formulir di sebelah kiri untuk menulis keluhan pertama Anda.
          </p>
        </div>

        <div v-else class="notes-feed">
          <article
            v-for="note in notes"
            :key="note.id"
            class="note-card"
            :class="{ 'note-card-editing': isEditing && editingId === note.id }"
          >
            <div class="note-card-header">
              <span class="file-tag">BERKAS #{{ note.id }}</span>
              <time class="note-date" :datetime="note.created_at">
                {{ formatDate(note.created_at) }}
              </time>
            </div>

            <h3 class="note-title">{{ note.title }}</h3>
            <p class="note-content">{{ note.content }}</p>

            <div class="note-footer">
              <span class="note-author-hint">Status: Disimpan aman (User ID {{ note.user_id }})</span>
              <div class="note-actions">
                <button
                  @click="startEdit(note)"
                  class="btn btn-outline btn-sm"
                  :disabled="deletingId === note.id"
                  aria-label="Sunting catatan"
                >
                  Sunting
                </button>
                <button
                  @click="handleDelete(note)"
                  class="btn btn-danger btn-sm"
                  :disabled="deletingId === note.id"
                  aria-label="Hapus catatan"
                >
                  <span v-if="deletingId === note.id">Memusnahkan...</span>
                  <span v-else>Pemusnahan</span>
                </button>
              </div>
            </div>
          </article>
        </div>
      </section>
    </main>
  </div>
</template>
