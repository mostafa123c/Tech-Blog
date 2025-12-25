import axios from "axios";
import { API_BASE_URL } from "../config/api";

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem("token");
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const url = error.config?.url || "";
      const isAuthEndpoint =
        url.includes("/login") || url.includes("/register");

      if (!isAuthEndpoint) {
        localStorage.removeItem("token");
        localStorage.removeItem("user");
        window.location.href = "/login";
      }
    }
    return Promise.reject(error);
  }
);

export const authAPI = {
  register: (formData) => {
    const config = {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    };
    return api.post("/register", formData, config);
  },
  login: (credentials) => {
    const formData = new FormData();
    formData.append("email", credentials.email);
    formData.append("password", credentials.password);
    return api.post("/login", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
  },
  logout: () => api.post("/logout"),
  me: () => api.get("/me"),
};

export const postsAPI = {
  getAll: (page = 1) => api.get(`/posts?page=${page}`),
  getOne: (id) => api.get(`/posts/${id}`),
  create: (data) => api.post("/posts", data),
  update: (id, data) => api.put(`/posts/${id}`, data),
  delete: (id) => api.delete(`/posts/${id}`),
};

export const commentsAPI = {
  getByPost: (postId, page = 1) =>
    api.get(`/posts/${postId}/comments?page=${page}`),
  create: (postId, body) => api.post(`/posts/${postId}/comments`, { body }),
  update: (commentId, body) => api.put(`/comments/${commentId}`, { body }),
  delete: (commentId) => api.delete(`/comments/${commentId}`),
};

export default api;
