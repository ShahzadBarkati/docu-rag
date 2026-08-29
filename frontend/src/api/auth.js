import http from "./http";

export async function csrf() {
  await http.get("/sanctum/csrf-cookie");
}

export const authApi = {
  async register(credentials) {
    return http.post("/api/auth/register", credentials);
  },

  async login(credentials) {
    return http.post("/api/auth/login", credentials);
  },

  async logout() {
    return http.post("/api/auth/logout");
  },

  async me() {
    return http.get("/api/auth/me");
  },
};
