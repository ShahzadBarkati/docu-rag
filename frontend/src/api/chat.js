import http, { httpLong } from "./http";

function getCookie(name) {
  const match = document.cookie.match(
    new RegExp("(?:^|; )" + name.replace(/([.$?*|{}()[\]\\/+^])/g, "\\$1") + "=([^;]*)")
  );
  return match ? decodeURIComponent(match[1]) : null;
}

export const chatApi = {
  async send(message, conversationId = null) {
    const payload = { message };
    if (conversationId) {
      payload.conversation_id = conversationId;
    }
    return httpLong.post("/api/chat", payload);
  },
  async streamSend(message, conversationId = null, onDelta, documentId = null) {
    const token = localStorage.getItem("docu-rag-token");
    const xsrfToken = getCookie("XSRF-TOKEN");
    const baseURL = import.meta.env.VITE_API_URL || "http://localhost:8000";
    const payload = { message };
    if (conversationId) {
      payload.conversation_id = conversationId;
    }
    if (documentId) {
      payload.document_id = documentId;
    }

    const headers = {
      "Content-Type": "application/json",
      Accept: "text/event-stream",
    };
    if (token) headers.Authorization = `Bearer ${token}`;
    if (xsrfToken) headers["X-XSRF-TOKEN"] = xsrfToken;

    const res = await fetch(`${baseURL}/api/chat`, {
      method: "POST",
      headers,
      credentials: "include",
      body: JSON.stringify(payload),
    });

    if (!res.ok) {
      let message = "Failed to generate response";
      try {
        const data = await res.json();
        message = data.message || message;
      } catch {
        // ignore parse errors, keep default message
      }
      throw message;
    }

    const reader = res.body.getReader();
    const decoder = new TextDecoder();
    let buffer = "";
    let resultConversationId = null;
    let fullText = "";
    let done = false;

    while (!done) {
      const { done: streamDone, value } = await reader.read();
      if (streamDone) break;
      buffer += decoder.decode(value, { stream: true });
      const frames = buffer.split("\n\n");
      buffer = frames.pop();
      for (const frame of frames) {
        const line = frame.replace(/^data:\s*/, "").trim();
        if (!line) continue;
        if (line === "[DONE]") {
          done = true;
          break;
        }
        let evt;
        try {
          evt = JSON.parse(line);
        } catch {
          continue;
        }
        if (evt.type === "conversation") {
          resultConversationId = evt.conversation_id;
        } else if (evt.type === "text_delta") {
          fullText += evt.delta;
          if (onDelta) onDelta(evt.delta, fullText);
        }
      }
    }

    return { conversationId: resultConversationId, message: fullText };
  },
  async conversations() {
    return http.get("/api/chat/conversations");
  },
  async conversation(id) {
    return http.get(`/api/chat/conversations/${id}`);
  },
  async rename(id, title) {
    return http.patch(`/api/chat/conversations/${id}`, { title });
  },
  async remove(id) {
    return http.delete(`/api/chat/conversations/${id}`);
  },
};
