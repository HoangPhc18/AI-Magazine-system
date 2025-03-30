import axios from './axios';

export const articleService = {
  // Public APIs
  getAllArticles: async (params) => {
    const response = await axios.get('/api/articles', { params });
    return response.data;
  },

  getArticleById: async (id) => {
    const response = await axios.get(`/api/articles/${id}`);
    return response.data;
  },

  searchArticles: async (params) => {
    const response = await axios.get('/api/articles/search', { params });
    return response.data;
  },

  // User APIs
  getUserArticles: async (params) => {
    const response = await axios.get('/api/user/articles', { params });
    return response.data;
  },

  createArticle: async (articleData) => {
    const response = await axios.post('/api/user/articles', articleData);
    return response.data;
  },

  updateArticle: async (id, articleData) => {
    const response = await axios.put(`/api/user/articles/${id}`, articleData);
    return response.data;
  },

  deleteArticle: async (id) => {
    const response = await axios.delete(`/api/user/articles/${id}`);
    return response.data;
  },

  // Admin APIs
  adminGetAllArticles: async (params) => {
    const response = await axios.get('/api/admin/articles', { params });
    return response.data;
  },

  adminUpdateArticle: async (id, articleData) => {
    const response = await axios.put(`/api/admin/articles/${id}`, articleData);
    return response.data;
  },

  adminDeleteArticle: async (id) => {
    const response = await axios.delete(`/api/admin/articles/${id}`);
    return response.data;
  },
}; 