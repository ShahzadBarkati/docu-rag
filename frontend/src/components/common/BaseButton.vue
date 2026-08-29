<script setup>
defineProps({
  disabled: { type: Boolean, default: false },
  variant: { type: String, default: "primary" },
});

defineEmits(["click"]);
</script>

<template>
  <button
    :disabled="disabled"
    :class="[
      'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 font-medium text-sm transition-all duration-200 cursor-pointer',
      'disabled:opacity-40 disabled:cursor-not-allowed',
      variant === 'primary' && 'bg-emerald-600 text-white hover:bg-emerald-500 shadow-lg shadow-emerald-600/20 hover:shadow-emerald-500/30',
      variant === 'secondary' && 'bg-slate-800 text-slate-300 hover:bg-slate-700 border border-slate-700 hover:border-slate-600',
      variant === 'ghost' && 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50',
      variant === 'danger' && 'bg-red-600/10 text-red-400 hover:bg-red-600/20 border border-red-600/20',
    ]"
    @click="$emit('click')"
  >
    <slot />
  </button>
</template>
