<script setup>
import { ref, nextTick, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAuth } from "../composables/useAuth";
import { chatApi } from "../api/chat";
import { documentsApi } from "../api/documents";
import BaseButton from "../components/common/BaseButton.vue";
import MarkdownRenderer from "../components/common/MarkdownRenderer.vue";
import ConfirmModal from "../components/common/ConfirmModal.vue";

const router = useRouter();
const route = useRoute();
const auth = useAuth();
const { logout } = auth;

const messages = ref([]);
const input = ref("");
const sending = ref(false);
const conversations = ref([]);
const currentConversationId = ref(null);
const inputRef = ref(null);
const renamingId = ref(null);
const renameDraft = ref("");
const renameInputRef = ref(null);
const deleteTarget = ref(null);
const deleteBusy = ref(false);
const documents = ref([]);
const selectedDocumentId = ref(null);

async function loadDocuments() {
  try {
    const res = await documentsApi.list();
    documents.value = res.data || [];
  } catch (err) {
    console.error("Failed to load documents", err);
  }
}

function openDeleteModal(conv) {
  deleteTarget.value = conv;
}

function cancelDelete() {
  if (deleteBusy.value) return;
  deleteTarget.value = null;
}

async function confirmDeleteConversation() {
  const conv = deleteTarget.value;
  if (!conv) return;
  deleteBusy.value = true;
  try {
    await chatApi.remove(conv.id);
    if (currentConversationId.value === conv.id) {
      newConversation();
    }
    conversations.value = conversations.value.filter((c) => c.id !== conv.id);
    deleteTarget.value = null;
  } catch (err) {
    console.error("Failed to delete conversation", err);
  } finally {
    deleteBusy.value = false;
  }
}

async function loadConversations() {
  try {
    const res = await chatApi.conversations();
    conversations.value = res.data;
  } catch (err) {
    console.error("Failed to load conversations", err);
  }
}

async function loadConversation(id) {
  try {
    const res = await chatApi.conversation(id);
    messages.value = res.data.messages || [];
    currentConversationId.value = id;
    await nextTick();
    scrollToBottom();
  } catch (err) {
    console.error("Failed to load conversation", err);
  }
}

function newConversation() {
  messages.value = [];
  currentConversationId.value = null;
  cancelRename();
}

function startRename(conv) {
  renamingId.value = conv.id;
  renameDraft.value = conv.title;
  nextTick(() => {
    if (renameInputRef.value) {
      renameInputRef.value.focus();
      renameInputRef.value.select();
    }
  });
}

function cancelRename() {
  renamingId.value = null;
  renameDraft.value = "";
}

async function saveRename(conv) {
  const title = renameDraft.value.trim();
  if (!title || renamingId.value !== conv.id) {
    cancelRename();
    return;
  }
  try {
    await chatApi.rename(conv.id, title);
    conv.title = title;
  } catch (err) {
    console.error("Failed to rename conversation", err);
  } finally {
    cancelRename();
  }
}

function onRenameKeydown(e, conv) {
  if (e.key === "Enter") {
    e.preventDefault();
    saveRename(conv);
  } else if (e.key === "Escape") {
    cancelRename();
  }
}

async function send() {
  const text = input.value.trim();
  if (!text || sending.value) return;

  messages.value.push({ role: "user", content: text });
  input.value = "";
  sending.value = true;
  const assistantMsg = { role: "assistant", content: "" };
  messages.value.push(assistantMsg);
  await nextTick();
  scrollToBottom();

  try {
    const res = await chatApi.streamSend(
      text,
      currentConversationId.value,
      (delta, fullText) => {
        assistantMsg.content = fullText;
        scrollToBottom();
      },
      selectedDocumentId.value
    );
    if (res.conversationId) {
      currentConversationId.value = res.conversationId;
    }
    await loadConversations();
  } catch (err) {
    assistantMsg.content =
      typeof err === "string" ? err : "Sorry, something went wrong. Please try again.";
  } finally {
    sending.value = false;
    await nextTick();
    scrollToBottom();
  }
}

function scrollToBottom() {
  const container = document.getElementById("chat-messages");
  if (container) {
    container.scrollTop = container.scrollHeight;
  }
}

function handleKeydown(e) {
  if (e.key === "Enter" && !e.shiftKey) {
    e.preventDefault();
    send();
  }
}

onMounted(() => {
  loadConversations();
  loadDocuments();
  if (route.params.id) {
    loadConversation(route.params.id);
  }
});
</script>

<template>
  <div class="h-screen flex bg-slate-950">
    <!-- Sidebar -->
    <aside class="w-72 flex flex-col border-r border-slate-800 bg-slate-950/50">
      <!-- Sidebar Header -->
      <div class="p-4 border-b border-slate-800">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-9 h-9 rounded-xl bg-emerald-600/10 border border-emerald-600/20 flex items-center justify-center">
            <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
            </svg>
          </div>
          <span class="text-base font-semibold text-white tracking-tight">DocuRAG</span>
        </div>
        <BaseButton @click="newConversation" class="w-full" variant="secondary">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
          New Chat
        </BaseButton>
      </div>

      <!-- Conversation List -->
      <div class="flex-1 overflow-y-auto p-3 space-y-1">
        <div
          v-for="conv in conversations"
          :key="conv.id"
          :class="[
            'group relative flex items-center rounded-xl text-sm cursor-pointer transition-all duration-150',
            currentConversationId === conv.id
              ? 'bg-slate-800 text-white border border-slate-700'
              : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50 border border-transparent'
          ]"
          @click="renamingId !== conv.id && loadConversation(conv.id)"
        >
          <!-- Rename input -->
          <input
            v-if="renamingId === conv.id"
            ref="renameInputRef"
            v-model="renameDraft"
            @keydown.enter.prevent="saveRename(conv)"
            @keydown.esc.prevent="cancelRename"
            @click.stop
            class="flex-1 min-w-0 mx-3 my-2 bg-slate-900/80 border border-slate-600 rounded-lg px-2.5 py-1.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/30 focus:outline-none"
          />
          <!-- Normal state -->
          <template v-else>
            <span class="flex-1 min-w-0 px-3 py-2.5 truncate">{{ conv.title }}</span>
            <div class="flex items-center gap-0.5 pr-2 opacity-0 group-hover:opacity-100 transition-opacity">
              <button
                @click.stop="startRename(conv)"
                class="p-1.5 rounded-md text-slate-400 hover:text-white hover:bg-slate-700/60 cursor-pointer"
                title="Rename"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.862 4.487z" />
                </svg>
              </button>
              <button
                @click.stop="openDeleteModal(conv)"
                class="p-1.5 rounded-md text-slate-400 hover:text-red-400 hover:bg-red-500/10 cursor-pointer"
                title="Delete"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
              </button>
            </div>
          </template>
        </div>
        <div v-if="conversations.length === 0" class="text-center py-8">
          <p class="text-xs text-slate-600">No conversations yet</p>
        </div>
      </div>

      <!-- Sidebar Footer -->
      <div class="p-3 border-t border-slate-800 space-y-1">
        <button
          @click="router.push({ name: 'dashboard' })"
          class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-slate-400 hover:text-slate-200 hover:bg-slate-800/50 transition-colors cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
          </svg>
          Dashboard
        </button>
        <button
          @click="logout"
          class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-slate-400 hover:text-red-400 hover:bg-red-500/5 transition-colors cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
          </svg>
          Sign out
        </button>
      </div>
    </aside>

    <!-- Main Chat Area -->
    <main class="flex-1 flex flex-col min-w-0">
      <!-- Messages -->
      <div
        id="chat-messages"
        class="flex-1 overflow-y-auto px-6 py-6 space-y-4"
      >
        <!-- Empty State -->
        <div
          v-if="messages.length === 0"
          class="h-full flex flex-col items-center justify-center text-center"
        >
          <div class="w-16 h-16 rounded-2xl bg-emerald-600/10 border border-emerald-600/20 flex items-center justify-center mb-5">
            <svg class="w-8 h-8 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
            </svg>
          </div>
          <h2 class="text-xl font-semibold text-white mb-2">Ask anything</h2>
          <p class="text-slate-400 text-sm max-w-sm">
            Search through your documents with AI-powered semantic search.
          </p>
        </div>

        <!-- Messages -->
        <template v-for="(msg, i) in messages" :key="i">
          <!-- User Message -->
          <div v-if="msg.role === 'user'" class="flex justify-end">
            <div class="max-w-[70%] bg-emerald-600 text-white rounded-2xl rounded-br-md px-5 py-3 text-sm leading-relaxed">
              {{ msg.content }}
            </div>
          </div>

          <!-- Assistant Message -->
          <div v-else class="flex justify-start">
            <div class="max-w-[75%] bg-slate-800/80 border border-slate-700/50 text-slate-200 rounded-2xl rounded-bl-md px-5 py-3 text-sm leading-relaxed">
              <MarkdownRenderer v-if="msg.content" :content="msg.content" />
              <div v-else class="flex items-center gap-1.5 py-1">
                <div class="w-2 h-2 bg-slate-500 rounded-full animate-bounce"></div>
                <div class="w-2 h-2 bg-slate-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                <div class="w-2 h-2 bg-slate-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- Input Bar -->
      <div class="border-t border-slate-800 bg-slate-950/80 backdrop-blur-xl p-4">
        <div class="flex items-center gap-2 mb-3 overflow-x-auto">
          <button
            @click="selectedDocumentId = null"
            :class="[
              'px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-colors cursor-pointer',
              selectedDocumentId === null
                ? 'bg-emerald-600 text-white'
                : 'bg-slate-800/70 text-slate-400 hover:text-slate-200 border border-slate-700'
            ]"
          >
            All documents
          </button>
          <button
            v-for="doc in documents"
            :key="doc.id"
            @click="selectedDocumentId = doc.id === selectedDocumentId ? null : doc.id"
            :class="[
              'px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-colors cursor-pointer',
              selectedDocumentId === doc.id
                ? 'bg-emerald-600 text-white'
                : 'bg-slate-800/70 text-slate-400 hover:text-slate-200 border border-slate-700'
            ]"
            :title="doc.name"
          >
            {{ doc.name }}
          </button>
          <span v-if="selectedDocumentId" class="ml-auto text-xs text-emerald-400 flex items-center gap-1 whitespace-nowrap">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Searching inside this document only
          </span>
        </div>

        <form @submit.prevent="send" class="flex items-end gap-3">
          <div class="flex-1 relative">
            <textarea
              ref="inputRef"
              v-model="input"
              @keydown="handleKeydown"
              :disabled="sending"
              placeholder="Ask about your documents..."
              rows="1"
              class="w-full resize-none rounded-xl border border-slate-700 bg-slate-800/50 px-4 py-3 pr-12 text-sm text-slate-200 placeholder-slate-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/30 focus:outline-none transition-colors"
              style="min-height: 48px; max-height: 200px;"
            ></textarea>
          </div>
          <button
            type="submit"
            :disabled="sending || !input.trim()"
            :class="[
              'w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-200 shrink-0 cursor-pointer',
              sending || !input.trim()
                ? 'bg-slate-800 text-slate-600 border border-slate-700'
                : 'bg-emerald-600 text-white hover:bg-emerald-500 shadow-lg shadow-emerald-600/20'
            ]"
          >
            <svg v-if="!sending" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
            </svg>
            <div v-else class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
          </button>
        </form>
        <p class="text-center text-xs text-slate-600 mt-2">
          Press Enter to send &middot; Shift+Enter for new line
        </p>
      </div>
    </main>

    <ConfirmModal
      :open="!!deleteTarget"
      title="Delete conversation?"
      :message="deleteTarget ? `This will permanently delete '${deleteTarget.title}' and its message history.` : ''"
      confirm-text="Delete"
      :busy="deleteBusy"
      @confirm="confirmDeleteConversation"
      @cancel="cancelDelete"
    />
  </div>
</template>
