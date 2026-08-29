<script setup>
defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: "Are you sure?" },
  message: { type: String, default: "" },
  confirmText: { type: String, default: "Delete" },
  busy: { type: Boolean, default: false },
  destructive: { type: Boolean, default: true },
});

const emit = defineEmits(["confirm", "cancel"]);
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        aria-modal="true"
        role="dialog"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="!busy && emit('cancel')"></div>

        <!-- Panel -->
        <div
          class="relative w-full max-w-sm rounded-2xl border border-slate-700/60 bg-slate-900 shadow-2xl shadow-black/50 p-6"
        >
          <div class="flex items-start gap-4">
            <div
              :class="[
                'w-11 h-11 rounded-xl flex items-center justify-center shrink-0',
                destructive
                  ? 'bg-red-500/10 border border-red-500/20'
                  : 'bg-slate-800 border border-slate-700'
              ]"
            >
              <svg
                class="w-5 h-5"
                :class="destructive ? 'text-red-400' : 'text-slate-400'"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-base font-semibold text-white">{{ title }}</h3>
              <p v-if="message" class="text-sm text-slate-400 mt-1.5 leading-relaxed">{{ message }}</p>
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <button
              type="button"
              :disabled="busy"
              class="inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-slate-300 bg-slate-800 hover:bg-slate-700 border border-slate-700 transition-colors cursor-pointer disabled:opacity-40"
              @click="emit('cancel')"
            >
              Cancel
            </button>
            <button
              type="button"
              :disabled="busy"
              :class="[
                'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed',
                destructive
                  ? 'bg-red-600 text-white hover:bg-red-500 shadow-lg shadow-red-600/20'
                  : 'bg-emerald-600 text-white hover:bg-emerald-500 shadow-lg shadow-emerald-600/20'
              ]"
              @click="emit('confirm')"
            >
              <div v-if="busy" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
              {{ confirmText }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
