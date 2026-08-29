import http from "./http";

export const documentsApi = {
  async list() {
    return http.get("/api/documents");
  },

  async upload(file) {
    const form = new FormData();
    form.append("file", file);
    return http.post("/api/documents", form);
  },

  async process(id) {
    return http.post(`/api/documents/${id}/process`);
  },

  async remove(id) {
    return http.delete(`/api/documents/${id}`);
  },

  async search(query) {
    return http.post("/api/documents/search", { query });
  },
};
