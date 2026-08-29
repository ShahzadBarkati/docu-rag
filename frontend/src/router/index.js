import { createRouter, createWebHistory } from "vue-router";
import Dashboard from "../views/Dashboard.vue";
import Chat from "../views/Chat.vue";
import Login from "../views/Login.vue";
import Register from "../views/Register.vue";
import { useAuth } from "../composables/useAuth.js";
const routes = [
  { path: "/", name: "dashboard", component: Dashboard },
  { path: "/chat", name: "chat", component: Chat },
  { path: "/chat/:id", name: "chat-conversation", component: Chat },
  { path: "/login", name: "login", component: Login },
  { path: "/register", name: "register", component: Register },
];
const router = createRouter({
  history: createWebHistory(),
  routes,
});
router.beforeEach(async (to) => {
  const auth = useAuth();
  await auth.check();
  const publicPages = ["login", "register"];
  if (!auth.isLoggedIn.value && !publicPages.includes(to.name)) {
    return { name: "login" };
  }
  if (auth.isLoggedIn.value && publicPages.includes(to.name)) {
    return { name: "dashboard" };
  }
});

export default router;
