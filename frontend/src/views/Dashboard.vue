<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { useAuth } from "../composables/useAuth";
import { documentsApi } from "../api/documents";
import BaseButton from "../components/common/BaseButton.vue";
import ConfirmModal from "../components/common/ConfirmModal.vue";

const router = useRouter();
const auth = useAuth();
const { user } = auth;
const userName = computed(() => user.value?.name || user.value?.email);

const documents = ref([]);
const uploading = ref(false);
const message = ref("");
const fileInput = ref(null);
const dragOver = ref(false);

const searchQuery = ref("");
const searchResults = ref([]);
const searching = ref(false);
const showSearch = ref(false);
const deletingId = ref(null);
const deleteTarget = ref(null);
const deleteBusy = ref(false);
let pollInterval = null;

async function loadDocuments() {
  try {
    const res = await documentsApi.list();
    documents.value = res.data;
  } catch (err) {
    message.value = typeof err === "string" ? err : "Unable to load documents";
  }
}

function openDeleteModal(doc) {
  deleteTarget.value = doc;
}

function cancelDelete() {
  if (deleteBusy.value) return;
  deleteTarget.value = null;
}

async function removeDocument() {
  const doc = deleteTarget.value;
  if (!doc) return;
  deleteBusy.value = true;
  message.value = "";
  try {
    await documentsApi.remove(doc.id);
    await loadDocuments();
    message.value = "Document deleted";
    deleteTarget.value = null;
  } catch (err) {
    message.value = typeof err === "string" ? err : "Failed to delete document";
  } finally {
    deleteBusy.value = false;
  }
}

async function upload() {
  const file = fileInput.value?.files?.[0];
  if (!file) {
    message.value = "Choose a file";
    return;
  }

  uploading.value = true;
  message.value = "";

  try {
    const res = await documentsApi.upload(file);
    message.value = `Uploaded: ${res.data.name} — processing...`;
    await documentsApi.process(res.data.id);
    message.value = `Ready: ${res.data.name}`;
    fileInput.value.value = "";
    await loadDocuments();
  } catch (err) {
    message.value =
      typeof err === "string" ? err : "Upload failed. Try again.";
  } finally {
    uploading.value = false;
  }
}

function onFileChange(e) {
  if (e.target.files?.[0]) {
    upload();
  }
}

function onDrop(e) {
  dragOver.value = false;
  const file = e.dataTransfer.files?.[0];
  if (file) {
    fileInput.value.files = e.dataTransfer.files;
    upload();
  }
}

async function search() {
  if (!searchQuery.value.trim()) return;
  searching.value = true;
  try {
    const res = await documentsApi.search(searchQuery.value);
    searchResults.value = res.data;
  } catch (err) {
    message.value = typeof err === "string" ? err : "Search failed";
  } finally {
    searching.value = false;
  }
}

function formatSize(bytes) {
  if (!bytes) return "0 B";
  const units = ["B", "KB", "MB", "GB"];
  let i = 0;
  let size = bytes;
  while (size >= 1024 && i < units.length - 1) {
    size /= 1024;
    i++;
  }
  return `${size.toFixed(i > 0 ? 1 : 0)} ${units[i]}`;
}

function statusColor(status) {
  switch (status) {
    case "ready":
    case "completed":
      return "text-emerald-400 bg-emerald-400/10 border-emerald-400/20";
    case "processing":
      return "text-amber-400 bg-amber-400/10 border-amber-400/20";
    case "failed":
      return "text-red-400 bg-red-400/10 border-red-400/20";
    default:
      return "text-slate-400 bg-slate-400/10 border-slate-400/20";
  }
}

async function logout() {
  await auth.logout();
  router.push({ name: "login" });
}

onMounted(() => {
  loadDocuments();
  pollInterval = setInterval(async () => {
    const pending = documents.value.some((d) => d.status === "processing" || d.status === "uploaded");
    if (pending) await loadDocuments();
  }, 3000);
});

onBeforeUnmount(() => {
  if (pollInterval) clearInterval(pollInterval);
});
</script>

<template>
  <div class="min-h-screen bg-slate-950">
    <!-- Top Nav -->
    <nav class="border-b border-slate-800 bg-slate-950/80 backdrop-blur-xl sticky top-0 z-30">
      <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-emerald-600/10 border border-emerald-600/20 flex items-center justify-center">
            <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
            </svg>
          </div>
          <span class="text-lg font-semibold text-white tracking-tight">DocuRAG</span>
        </div>
        <div class="flex items-center gap-3">
          <span class="text-sm text-slate-400">{{ userName }}</span>
          <BaseButton variant="ghost" @click="router.push({ name: 'chat' })">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
            </svg>
            Chat
          </BaseButton>
          <BaseButton variant="ghost" @click="logout">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
            </svg>
          </BaseButton>
        </div>
      </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-10">
      <!-- Welcome -->
      <div class="mb-10">
        <h1 class="text-3xl font-bold text-white">Dashboard</h1>
        <p class="text-slate-400 mt-1">Upload documents and search your knowledge base.</p>
      </div>

      <!-- Toast -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div
          v-if="message"
          class="mb-6 rounded-xl border px-4 py-3 text-sm"
          :class="message.includes('fail') || message.includes('Failed')
            ? 'bg-red-500/10 border-red-500/20 text-red-400'
            : 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400'"
        >
          {{ message }}
        </div>
      </Transition>

      <!-- Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Upload Card -->
        <div class="lg:col-span-1">
          <div class="bg-slate-900 border border-slate-700/60 shadow-xl shadow-black/20 rounded-2xl p-6">
            <h2 class="text-lg font-semibold text-white mb-4">Upload Document</h2>
            <div
              @dragover.prevent="dragOver = true"
              @dragleave.prevent="dragOver = false"
              @drop.prevent="onDrop"
              :class="[
                'relative border-2 border-dashed rounded-xl p-8 text-center transition-all duration-200 cursor-pointer',
                dragOver
                  ? 'border-emerald-500 bg-emerald-500/5'
                  : 'border-slate-700 hover:border-slate-600 bg-slate-800/30'
              ]"
              @click="$refs.fileInput.click()"
            >
              <input
                ref="fileInput"
                type="file"
                accept=".pdf, .docx, .txt, .html, .csv, .jpg, .jpeg, .png, .webp"
                class="hidden"
                @change="onFileChange"
              />
              <div class="flex flex-col items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center">
                  <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                  </svg>
                </div>
                <div>
                  <p class="text-sm text-slate-300 font-medium">
                    {{ uploading ? "Uploading..." : "Drop a file or click to upload" }}
                  </p>
                  <p class="text-xs text-slate-500 mt-1">PDF, DOCX, TXT, HTML, CSV, JPG, PNG — photos & scans are OCR'd</p>
                </div>
              </div>
              <div v-if="uploading" class="absolute inset-0 bg-slate-900/60 rounded-xl flex items-center justify-center">
                <div class="w-6 h-6 border-2 border-emerald-400 border-t-transparent rounded-full animate-spin"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Documents List -->
        <div class="lg:col-span-2">
          <div class="bg-slate-900 border border-slate-700/60 shadow-xl shadow-black/20 rounded-2xl p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-white">Documents</h2>
              <span class="text-xs text-slate-500 bg-slate-800 px-2.5 py-1 rounded-full">{{ documents.length }}</span>
            </div>

            <div v-if="documents.length === 0" class="text-center py-12">
              <div class="w-14 h-14 rounded-2xl bg-slate-800/50 border border-slate-700/50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
              </div>
              <p class="text-slate-500 text-sm">No documents yet</p>
              <p class="text-slate-600 text-xs mt-1">Upload your first document to get started</p>
            </div>

            <div v-else class="space-y-2">
              <div
                v-for="doc in documents"
                :key="doc.id"
                class="flex items-center gap-4 p-4 rounded-xl bg-slate-900 border border-slate-800 hover:border-slate-700/80 transition-colors"
              >
                <div class="w-10 h-10 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center shrink-0">
                  <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-slate-200 truncate">{{ doc.name }}</p>
                  <p class="text-xs text-slate-500 mt-0.5">{{ formatSize(doc.size) }}</p>
                </div>
                <span
                  :class="['text-xs font-medium px-2.5 py-1 rounded-full border capitalize', statusColor(doc.status)]"
                >
                  {{ doc.status }}
                </span>
                <button
                  v-if="doc.user_id === user?.id"
                  @click="openDeleteModal(doc)"
                  title="Delete document"
                  class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 hover:text-red-400 hover:bg-red-500/10 transition-colors cursor-pointer"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Search Section -->
      <div class="mt-6">
        <div class="bg-slate-900 border border-slate-700/60 shadow-xl shadow-black/20 rounded-2xl p-6">
          <button
            @click="showSearch = !showSearch"
            class="flex items-center gap-2 text-sm text-slate-400 hover:text-slate-200 transition-colors mb-4 cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <span class="font-medium">Semantic Search</span>
            <svg
              :class="['w-4 h-4 transition-transform duration-200', showSearch ? 'rotate-180' : '']"
              fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
          </button>

          <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
          >
            <div v-if="showSearch">
              <form @submit.prevent="search" class="flex gap-3">
                <input
                  v-model="searchQuery"
                  placeholder="Ask about your documents..."
                  class="flex-1 rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-sm text-slate-200 placeholder-slate-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/30 focus:outline-none"
                />
                <BaseButton :disabled="searching || !searchQuery.trim()">
                  {{ searching ? "Searching..." : "Search" }}
                </BaseButton>
              </form>

              <div v-if="searchResults.length" class="mt-4 space-y-2">
                <div
                  v-for="(result, i) in searchResults"
                  :key="i"
                  class="p-4 rounded-xl bg-slate-800/30 border border-slate-800"
                >
                  <div class="flex items-center gap-2 mb-2">
                    <span class="text-sm font-medium text-slate-200">{{ result.document_name }}</span>
                    <span class="text-xs text-emerald-400 bg-emerald-400/10 px-2 py-0.5 rounded-full">
                      {{ (result.similarity * 100).toFixed(1) }}%
                    </span>
                  </div>
                  <p class="text-sm text-slate-400 line-clamp-3">{{ result.content }}</p>
                </div>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </div>

    <ConfirmModal
      :open="!!deleteTarget"
      title="Delete document?"
      :message="deleteTarget ? `This will permanently delete '${deleteTarget.name}' and all of its content. This action cannot be undone.` : ''"
      confirm-text="Delete"
      :busy="deleteBusy"
      @confirm="removeDocument"
      @cancel="cancelDelete"
    />
  </div>
</template>
