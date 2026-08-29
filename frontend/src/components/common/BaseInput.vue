<script setup>
defineProps({
  modelLabel: { type: String, default: "" },
  modelType: { type: String, default: "text" },
  modelValue: { type: String, default: "" },
  modelPlaceholder: { type: String, default: "" },
});

defineEmits(["update:modelValue"]);
</script>

<template>
  <div class="space-y-1.5">
    <label
      v-if="modelLabel"
      :for="modelLabel.toLowerCase()"
      class="block text-sm font-medium text-slate-400"
    >
      {{ modelLabel }}
    </label>
    <input
      :id="modelLabel.toLowerCase()"
      :type="modelType"
      :value="modelValue"
      :placeholder="modelPlaceholder"
      class="w-full rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-sm text-slate-200 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/30 focus:outline-none"
      @input="$emit('update:modelValue', $event.target.value)"
    />
  </div>
</template>
