const API_BASE_URL =
  import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api";
const STORAGE_URL =
  import.meta.env.VITE_STORAGE_URL || "http://127.0.0.1:8000/storage";

export { API_BASE_URL, STORAGE_URL };
