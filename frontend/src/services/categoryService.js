import api from './api';
import errorService from './errorService';

const categoryService = {
  // Lấy danh sách danh mục
  getAll: async (params = {}) => {
    try {
      const response = await api.get('/categories', { params });
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  // Lấy thông tin chi tiết danh mục
  getById: async (id) => {
    try {
      const response = await api.get(`/categories/${id}`);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  // Tạo danh mục mới
  create: async (data) => {
    try {
      const response = await api.post('/categories', data);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  // Cập nhật danh mục
  update: async (id, data) => {
    try {
      const response = await api.put(`/categories/${id}`, data);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  // Xóa danh mục
  delete: async (id) => {
    try {
      const response = await api.delete(`/categories/${id}`);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  // Chuyển đổi trạng thái danh mục
  toggleCategoryStatus: async (id) => {
    try {
      const response = await api.put(`/admin/categories/${id}/toggle-status`);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  // Public categories
  getPublicCategories: async () => {
    try {
      const response = await api.get('/categories');
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  getArticles: async (id, params = {}) => {
    try {
      const response = await api.get(`/categories/${id}/articles`, { params });
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },
};

export default categoryService; 