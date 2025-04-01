import api from './api';
import errorService from './errorService';

const articleService = {
  getAll: async (params = {}) => {
    try {
      const response = await api.get('/articles', { params });
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  getById: async (id) => {
    try {
      const response = await api.get(`/articles/${id}`);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  create: async (data) => {
    try {
      const response = await api.post('/articles', data);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  update: async (id, data) => {
    try {
      const response = await api.put(`/articles/${id}`, data);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  delete: async (id) => {
    try {
      const response = await api.delete(`/articles/${id}`);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  getByCategory: async (categoryId, params = {}) => {
    try {
      const response = await api.get(`/categories/${categoryId}/articles`, { params });
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  search: async (query, params = {}) => {
    try {
      const response = await api.get('/articles/search', {
        params: { q: query, ...params }
      });
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  getLatest: async (limit = 6) => {
    try {
      const response = await api.get('/articles/latest', { params: { limit } });
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  getPopular: async (limit = 10) => {
    try {
      const response = await api.get('/articles/popular', {
        params: { limit }
      });
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  getRelated: async (articleId, limit = 5) => {
    try {
      const response = await api.get(`/articles/${articleId}/related`, {
        params: { limit }
      });
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  getByAuthor: async (authorId, params = {}) => {
    try {
      const response = await api.get(`/authors/${authorId}/articles`, { params });
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  getByTag: async (tag, params = {}) => {
    try {
      const response = await api.get(`/articles/tag/${tag}`, { params });
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  getByDate: async (date, params = {}) => {
    try {
      const response = await api.get(`/articles/date/${date}`, { params });
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  getByMonth: async (year, month, params = {}) => {
    try {
      const response = await api.get(`/articles/month/${year}/${month}`, { params });
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  getByYear: async (year, params = {}) => {
    try {
      const response = await api.get(`/articles/year/${year}`, { params });
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  getArchives: async () => {
    try {
      const response = await api.get('/articles/archives');
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  getStats: async () => {
    try {
      const response = await api.get('/articles/stats');
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  // Quản lý bài viết đã viết lại
  getRewrittenArticles: async (page = 1) => {
    const response = await api.get(`/admin/articles/rewritten?page=${page}`);
    return response.data;
  },

  getRewrittenArticle: async (id) => {
    const response = await api.get(`/admin/articles/rewritten/${id}`);
    return response.data;
  },

  approveArticle: async (id) => {
    const response = await api.post(`/admin/articles/rewritten/${id}/approve`);
    return response.data;
  },

  rejectArticle: async (id) => {
    const response = await api.post(`/admin/articles/rewritten/${id}/reject`);
    return response.data;
  },

  // Quản lý bài viết đã duyệt
  getApprovedArticles: async (page = 1) => {
    const response = await api.get(`/admin/articles/approved?page=${page}`);
    return response.data;
  },

  getApprovedArticle: async (id) => {
    const response = await api.get(`/admin/articles/approved/${id}`);
    return response.data;
  },

  archiveArticle: async (id) => {
    const response = await api.post(`/admin/articles/approved/${id}/archive`);
    return response.data;
  },

  deleteArticle: async (id) => {
    const response = await api.delete(`/admin/articles/approved/${id}`);
    return response.data;
  },

  // Public Articles
  getPublicArticles: async (params = {}) => {
    try {
      const response = await api.get('/articles', { params });
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  getPublicArticle: async (id) => {
    try {
      const response = await api.get(`/articles/${id}`);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  }
};

export default articleService; 