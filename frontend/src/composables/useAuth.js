import { computed, ref } from "vue";
import { authApi, csrf } from "../api/auth";

const TOKEN_KEY = "docu-rag-token";
const user = ref(null);

function saveSession(res) {
  localStorage.setItem(TOKEN_KEY, res.data.token);
  user.value = res.data.user;
}

async function check() {
  try {
    const res = await authApi.me();
    user.value = res.data.user ?? res.data;
  } catch (err) {
    user.value = null;
    // console.log("useAuth check error:: ", err);
  }
}

async function login(credentials) {
  await csrf();
  const res = await authApi.login(credentials);
  await saveSession(res);
}

async function register(payload) {
  await csrf();
  const res = await authApi.register(payload);
  await saveSession(res);
}

async function logout() {
  try {
    await authApi.logout();
  } finally {
    localStorage.removeItem(TOKEN_KEY);
    user.value = null;
  }
}

const isLoggedIn = computed(() => user.value !== null);

export function useAuth() {
  return { user, isLoggedIn, check, login, register, logout };
}
