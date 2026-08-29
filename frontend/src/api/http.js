import axios from "axios";
const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL || "http://localhost:8000",
  withCredentials: true,
  withXSRFToken: true,
  timeout: 15000,
});
const httpLong = axios.create({
  baseURL: import.meta.env.VITE_API_URL || "http://localhost:8000",
  withCredentials: true,
  withXSRFToken: true,
  timeout: 90000,
});
http.interceptors.request.use((config) => {
  const token = localStorage.getItem("docu-rag-token");
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});
httpLong.interceptors.request.use((config) => {
  const token = localStorage.getItem("docu-rag-token");
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});
const responseHandler = (response) => response.data;
const errorHandler = (error) => {
  const message =
    error.response?.data?.message ||
    error.response?.data?.errors ||
    error.message;
  return Promise.reject(message);
};
http.interceptors.response.use(responseHandler, errorHandler);
httpLong.interceptors.response.use(responseHandler, errorHandler);
export default http;
export { httpLong };
