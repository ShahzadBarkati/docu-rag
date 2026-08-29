<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuth } from "../composables/useAuth";
import BaseButton from "../components/common/BaseButton.vue";
import BaseInput from "../components/common/BaseInput.vue";

const router = useRouter();
const auth = useAuth();

const name = ref("");
const email = ref("");
const password = ref("");
const message = ref("");
const saving = ref(false);

async function register() {
  saving.value = true;
  message.value = "";
  try {
    await auth.register({
      name: name.value,
      email: email.value,
      password: password.value,
    });
    router.push({ name: "dashboard" });
  } catch (err) {
    message.value = err;
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center px-4 relative overflow-hidden">
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-emerald-900/20 via-slate-950 to-slate-950"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-emerald-500/5 rounded-full blur-3xl"></div>

    <div class="w-full max-w-md relative z-10">
      <div class="text-center mb-10">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-600/10 border border-emerald-600/20 mb-5">
          <svg class="w-7 h-7 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-white tracking-tight">Create account</h1>
        <p class="text-slate-400 mt-2">Start using DocuRAG</p>
      </div>

      <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-2xl p-8 shadow-2xl shadow-black/20">
        <form @submit.prevent="register" class="space-y-5">
          <div v-if="message" class="rounded-lg bg-red-500/10 border border-red-500/20 px-4 py-3 text-sm text-red-400">
            {{ message }}
          </div>

          <BaseInput
            v-model="name"
            model-label="Name"
            model-placeholder="Your full name"
          />

          <BaseInput
            v-model="email"
            model-label="Email"
            model-placeholder="you@example.com"
          />

          <BaseInput
            v-model="password"
            model-type="password"
            model-label="Password"
            model-placeholder="Min 5 characters"
          />

          <BaseButton :disabled="saving" class="w-full">
            {{ saving ? "Creating account..." : "Create account" }}
          </BaseButton>
        </form>
      </div>

      <p class="text-center text-sm text-slate-500 mt-6">
        Already have an account?
        <RouterLink :to="{ name: 'login' }" class="text-emerald-400 hover:text-emerald-300 font-medium transition-colors">
          Sign in
        </RouterLink>
      </p>
    </div>
  </div>
</template>
