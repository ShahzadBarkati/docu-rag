<script setup>
import { computed } from "vue";
import { marked } from "marked";
import DOMPurify from "dompurify";

const props = defineProps({
  content: { type: String, required: true },
});

const html = computed(() => {
  const raw = marked.parse(props.content || "");
  return DOMPurify.sanitize(raw);
});
</script>

<template>
  <div class="markdown-body" v-html="html"></div>
</template>

<style scoped>
.markdown-body {
  line-height: 1.7;
  word-break: break-word;
  color: #e2e8f0;
}

.markdown-body :deep(p) {
  margin: 0.5em 0;
}

.markdown-body :deep(p:first-child) {
  margin-top: 0;
}

.markdown-body :deep(p:last-child) {
  margin-bottom: 0;
}

.markdown-body :deep(pre) {
  background: #0f172a;
  border: 1px solid #1e293b;
  color: #e2e8f0;
  padding: 14px 18px;
  border-radius: 10px;
  overflow-x: auto;
  font-size: 13px;
  margin: 10px 0;
  font-family: "JetBrains Mono", ui-monospace, monospace;
}

.markdown-body :deep(code) {
  font-family: "JetBrains Mono", ui-monospace, monospace;
  font-size: 0.88em;
}

.markdown-body :deep(:not(pre) > code) {
  background: rgba(16, 185, 129, 0.1);
  color: #34d399;
  padding: 2px 7px;
  border-radius: 5px;
  border: 1px solid rgba(16, 185, 129, 0.15);
}

.markdown-body :deep(ul),
.markdown-body :deep(ol) {
  padding-left: 1.5em;
  margin: 8px 0;
}

.markdown-body :deep(li) {
  margin: 3px 0;
}

.markdown-body :deep(blockquote) {
  border-left: 3px solid #10b981;
  margin: 10px 0;
  padding: 6px 14px;
  color: #94a3b8;
  background: rgba(16, 185, 129, 0.05);
  border-radius: 0 8px 8px 0;
}

.markdown-body :deep(table) {
  border-collapse: collapse;
  margin: 10px 0;
  width: 100%;
}

.markdown-body :deep(th),
.markdown-body :deep(td) {
  border: 1px solid #1e293b;
  padding: 8px 12px;
  text-align: left;
}

.markdown-body :deep(th) {
  background: #1e293b;
  font-weight: 600;
  color: #e2e8f0;
}

.markdown-body :deep(td) {
  background: #0f172a;
}

.markdown-body :deep(h1),
.markdown-body :deep(h2),
.markdown-body :deep(h3),
.markdown-body :deep(h4) {
  font-weight: 600;
  color: #f1f5f9;
  margin: 1em 0 0.5em;
}

.markdown-body :deep(h1) { font-size: 1.4em; }
.markdown-body :deep(h2) { font-size: 1.2em; }
.markdown-body :deep(h3) { font-size: 1.1em; }

.markdown-body :deep(a) {
  color: #34d399;
  text-decoration: none;
}

.markdown-body :deep(a:hover) {
  text-decoration: underline;
}

.markdown-body :deep(hr) {
  border: none;
  border-top: 1px solid #1e293b;
  margin: 1em 0;
}

.markdown-body :deep(strong) {
  color: #f1f5f9;
  font-weight: 600;
}
</style>
